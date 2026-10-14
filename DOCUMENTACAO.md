# Documentação do projeto — Gestão de Pedidos de Afiliados

Um passeio didático pelo sistema: o que ele faz, como o backend e o frontend
funcionam, cada tela, e como o DevOps amarra tudo. Pensado para quem vai abrir o
projeto pela primeira vez.

---

## 1. Qual é o objetivo

O sistema é um **painel de gestão de pedidos de e-commerce com múltiplos
afiliados**. Ele importa dados de uma API externa fake (a
[fakestoreapi](https://fakestoreapi.com)) e transforma esses dados no nosso
domínio de negócio:

| Na fakestoreapi | No nosso sistema |
|---|---|
| `cart` (carrinho) | **pedido** (order) |
| `user` (usuário) | **afiliado** (affiliate) |
| `product` dentro do cart | **item do pedido** (order_item) |

A partir daí, o operador logado consegue: ver **métricas** no dashboard, **listar
e filtrar pedidos**, abrir o **detalhe de um pedido** e **mudar o status** dele
(respeitando regras de negócio), e ver o **resumo de cada afiliado**. Cada
mudança de status ainda dispara uma **notificação automática** via N8N.

O sistema foi pensado para uma base grande (até ~500 mil pedidos), então há
cuidado com índices, paginação, cache e processamento em segundo plano.

---

## 2. Visão geral da arquitetura

```
┌─────────────┐        HTTP/JSON        ┌──────────────────────────┐
│  Frontend    │  ───────────────────▶  │  Backend (API Laravel)    │
│  Vue 3 + Vite│  ◀───────────────────  │  Controllers → Services   │
└─────────────┘   { data, meta, errors} │        → Repositories     │
                                         └───────────┬──────────────┘
                                                     │
                     ┌───────────────┬───────────────┼───────────────┐
                     ▼               ▼               ▼               ▼
                 ┌────────┐     ┌────────┐     ┌──────────┐    ┌──────────┐
                 │ MySQL  │     │ Redis  │     │  Worker   │    │  N8N     │
                 │ (dados)│     │(cache/ │     │(processa  │    │(automação│
                 │        │     │ filas) │     │ os jobs)  │    │ /alertas)│
                 └────────┘     └────────┘     └──────────┘    └──────────┘
                                                     ▲
                                              ┌──────┴───────┐
                                              │ fakestoreapi │  (fonte externa)
                                              └──────────────┘
```

**A ideia central:** o backend é uma API REST. O frontend só consome essa API.
Tarefas pesadas ou lentas (importar dados, notificar o N8N) não rodam na hora do
request — elas viram **jobs** que o **worker** processa em segundo plano.

---

## 3. Backend (Laravel)

O backend segue o padrão **Controller → Service → Repository**, cada camada com
uma responsabilidade só:

- **Controller** — recebe o request, valida (via Form Request) e devolve a
  resposta. Não tem regra de negócio.
- **Service** — onde mora a regra de negócio (mudar status, calcular métricas,
  orquestrar o sync).
- **Repository** — isola as queries do banco (Eloquent). Ninguém mais escreve
  query fora dele.

### 3.1 As tabelas (modelagem)

- **`affiliates`** — os afiliados (vindos dos `users` da API).
- **`products`** — o catálogo de produtos.
- **`orders`** — os pedidos. Tem `status` (enum), `total` (valor já calculado e
  guardado) e `affiliate_id`. Usa *soft delete* (não apaga de verdade).
- **`order_items`** — os itens de cada pedido (produto + quantidade + preço).
- **`order_status_logs`** — o histórico: registra cada mudança de status, de
  onde veio, para onde foi e **quem fez** (`changed_by`).

Detalhe importante: há um **índice composto** em `orders(affiliate_id, status,
created_at)`, porque é exatamente assim que a tela de pedidos filtra. Índice
pensado para a consulta real, não só para as chaves estrangeiras.

### 3.2 A máquina de estados (regra de negócio central)

Um pedido não pode ir de qualquer status para qualquer outro. As transições
válidas são:

```
pending ──▶ approved ──▶ refunded
   │
   └──────▶ cancelled
```

Isso fica isolado numa classe (`OrderStatusStateMachine`). Se alguém tentar uma
transição inválida (ex.: `pending → refunded`), a API responde **422** com uma
mensagem clara e a lista de transições que *seriam* permitidas. Não há `if/else`
de status espalhado pelo código.

### 3.3 Importação de dados — o comando `orders:sync`

Rodando `php artisan orders:sync`, o sistema busca os dados na fakestoreapi e
grava no banco. Mas ele não faz tudo de uma vez no terminal — ele **enfileira
jobs**:

1. Primeiro sincroniza **afiliados**, depois **produtos** (precisam existir antes
   dos pedidos, por causa das chaves estrangeiras).
2. Depois dispara **um job por página de pedidos**.

Características importantes:
- **Idempotente**: rodar duas vezes não duplica nada (usa *upsert* por uma chave
  natural). E não sobrescreve o status de um pedido que você já mudou.
- **Rate limiting**: limita quantas chamadas por segundo faz à API externa, para
  não abusar dela.
- **Resiliente**: se um job falha, ele é registrado e pode ser reprocessado
  (retry automático até 3x com espera crescente entre tentativas).

### 3.4 A API REST

Todas as respostas seguem o mesmo formato: `{ data, meta, errors }`.

| Método | Rota | O que faz |
|---|---|---|
| GET | `/api/orders` | Lista paginada de pedidos, com filtros e ordenação |
| GET | `/api/orders/{id}` | Detalhe do pedido: itens + histórico de status |
| GET | `/api/orders/metrics` | Métricas do dashboard (cacheadas por 5 min) |
| POST | `/api/orders/{id}/status` | Muda o status (valida na máquina de estados) |
| GET | `/api/affiliates` | Lista de afiliados (para o filtro e a tela) |
| GET | `/api/affiliates/{id}/summary` | Resumo do afiliado (receita, ticket médio…) |
| GET | `/api/health` | Diz se MySQL, Redis e worker estão de pé |
| POST | `/api/login` | Autenticação (retorna um token Sanctum) |

### 3.5 Cache das métricas

Calcular métricas numa base enorme é caro. Então o resultado fica **cacheado por
5 minutos**. E, para os números nunca ficarem "velhos", o cache é **apagado
automaticamente sempre que um status muda** — não dependemos só do tempo expirar.

### 3.6 Notificação por eventos (integração com N8N)

Quando um status muda com sucesso, o `OrderService` **dispara um evento**. Um
*listener* escuta esse evento e **enfileira um job** que faz um `POST` para o
webhook do N8N. Por que tanta cerimônia?

- O envio ao N8N roda **em segundo plano** — se o N8N estiver fora do ar, o
  pedido é atualizado normalmente mesmo assim (a falha fica isolada no job).
- O job tenta reenviar até 3x com espera crescente.

---

## 4. Frontend (Vue 3)

Aplicação SPA (Vue 3 + Vite + Tailwind + Pinia). O layout é um **painel clássico**:
uma barra lateral (sidebar) com o menu e uma barra superior (topbar). Todo o
estado de dados fica na store do Pinia; a comunicação com o backend passa por um
cliente axios que injeta o token de login automaticamente.

### As páginas

**🔐 Login** — e-mail e senha. Ao logar, guarda o token e libera o painel.

**📊 Dashboard** — os cartões de métricas (total de pedidos, receita, ticket
médio, taxa de cancelamento) e a quebra por status. Ele:
- mostra *skeletons* enquanto carrega,
- se **atualiza sozinho a cada 60 segundos**,
- exibe "atualizado há X min" (aproveitando o timestamp do cache).

**📦 Pedidos** — a tela principal. Uma tabela com:
- **Filtros** (afiliado, status, período, faixa de valor) que aplicam com um
  pequeno atraso (*debounce* de 400ms) e ficam **salvos na URL** — se você
  copiar o link, a pessoa abre com os mesmos filtros.
- **Ordenação** clicando no cabeçalho da coluna.
- **Seleção múltipla** com uma barra de ação em lote (ex.: cancelar vários).
- **Estado vazio** com mensagem quando o filtro não retorna nada.
- No **celular**, a tabela vira uma lista de cards.

Ao clicar num pedido, abre um **drawer** (painel lateral) com o detalhe: itens,
histórico visual do status (a *timeline*) e um seletor que **só mostra as
transições permitidas** para aquele status. Se o backend recusar (422), o erro
aparece ali mesmo.

**👥 Afiliados** — lista de afiliados; ao clicar, abre um drawer com o resumo
(total de pedidos, receita, ticket médio, taxa de cancelamento).

**⚙️ Usuários** — gestão de usuários do painel (só admin).

---

## 5. DevOps

A ideia é: `docker compose up -d --build` sobe **tudo funcionando**. São 6
serviços:

| Serviço | Papel |
|---|---|
| **app** | a API Laravel; ao subir, espera o banco, migra e semeia |
| **worker** | processa os jobs das filas (`queue:work`) |
| **mysql** | o banco de dados |
| **redis** | cache + filas (ver seção abaixo) |
| **n8n** | automação, acessível em http://localhost:5678 |
| **frontend** | o Vite servindo a interface em http://localhost:5173 |

Cuidados de segurança e boas práticas:
- **Nenhuma senha no código** — tudo vem de variáveis de ambiente; o
  `.env.example` traz placeholders `CHANGE_ME_IN_PRODUCTION`.
- O container roda como **usuário não-root**.
- Existe o `/api/health` para checar se banco, Redis e worker estão de pé.

### Para que serve o Redis?

O Redis faz três coisas:
1. **Filas** — a "caixa de entrada" dos jobs (importação e notificações) que o
   worker consome.
2. **Cache** — guarda as métricas do dashboard por 5 min.
3. **Rate limiting** — controla a taxa de chamadas à API externa (e ao login).

> Em **dev local sem Docker** dá para trocar cache/filas para o driver
> `database` (usa tabelas do MySQL), evitando ter que instalar o Redis. No
> Docker, o Redis é usado de verdade.

### N8N (os workflows)

Há dois fluxos prontos (exportados em `n8n/workflows/`):
- **Pedido aprovado** → formata uma mensagem e envia para um canal
  (Slack/Discord/e-mail mockados) + registra num Google Sheets.
- **Pedido cancelado** → consulta o resumo do afiliado e, se a taxa de
  cancelamento passar de 30%, dispara um alerta de atenção.

---

## 6. Como rodar (resumo)

**Com Docker (recomendado):**
```bash
cp .env.example .env          # ajuste as senhas
docker compose up -d --build
docker compose exec app php artisan orders:sync   # importa os dados
```
Interface em http://localhost:5173 · Login: `admin@sistema.com` / `Admin@2024`

**Local sem Docker:** rode MySQL, ajuste o `backend/.env` (cache/filas em
`database` se não tiver Redis), `php artisan migrate --seed`, `php artisan serve`,
`php artisan queue:work` e `npm run dev` no frontend.

---

## 7. Resumo do fluxo completo (ponta a ponta)

1. `orders:sync` puxa afiliados, produtos e pedidos da fakestoreapi (via jobs).
2. O operador loga e vê as **métricas** no dashboard.
3. Ele filtra os **pedidos**, abre um e **muda o status**.
4. O backend valida a transição, grava o **log de auditoria** e **invalida o
   cache** de métricas.
5. Um **evento** dispara um **job** que **notifica o N8N**.
6. O N8N aplica as regras (mensagem de aprovado, alerta de cancelamento etc.).

Tudo isso desacoplado: telas leves, regras no lugar certo e o trabalho pesado
acontecendo em segundo plano.
