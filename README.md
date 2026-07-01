# Módulo de Gestão de Pedidos (afiliados)

Sistema de gestão de pedidos de e-commerce com múltiplos afiliados, construído
sobre **Laravel 13 + Vue 3 + MySQL 8 + Redis**, consumindo a API externa
[fakestoreapi](https://fakestoreapi.com) (cart → pedido, user → afiliado,
product do cart → item de pedido).

O projeto nasceu da refatoração de um sistema de controle de chamados,
reaproveitando autenticação (Sanctum), layout (sidebar/topbar) e a biblioteca
de componentes base do frontend.

---

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.3, Laravel 13, Sanctum, arquitetura Service/Repository |
| Filas & Cache | Redis (via `predis/predis`, cliente PHP puro) |
| Frontend | Vue 3 (Composition API), Vite, Tailwind 4, Pinia |
| Banco | MySQL 8 |
| Automação | N8N |
| Infra | Docker Compose |

---

## Pré-requisitos

- **Docker** e **Docker Compose** (caminho recomendado), ou
- PHP 8.3+, Composer 2, Node 20+, MySQL 8 e Redis para rodar localmente.

---

## Subindo com Docker (recomendado)

```bash
cp .env.example .env         # ajuste as senhas CHANGE_ME_IN_PRODUCTION
docker compose up -d --build
```

Sobem 6 serviços:

| Serviço | Porta | Descrição |
|---|---|---|
| `app` | 8000 | API Laravel (`php artisan serve`) — roda migrations + seed no start |
| `worker` | — | `php artisan queue:work --tries=3` (filas e jobs) |
| `mysql` | 3306 | MySQL 8 |
| `redis` | 6379 | Cache e filas |
| `n8n` | 5678 | Automação — http://localhost:5678 |
| `frontend` | 5173 | Vite dev server — http://localhost:5173 |

Depois de subir, **importe os dados** da fakestoreapi:

```bash
docker compose exec app php artisan orders:sync
```

O comando enfileira os jobs; o `worker` os processa. Acompanhe:

```bash
docker compose logs -f worker
```

- App: http://localhost:5173  ·  API: http://localhost:8000/api
- Health: http://localhost:8000/api/health
- N8N: http://localhost:5678 (usuário/senha do `.env`)

Credenciais de acesso padrão (seed): `admin@sistema.com` / `Admin@2024`.

---

## Rodando localmente (sem Docker)

```bash
# Backend
cd backend
cp .env.example .env         # DB_HOST=127.0.0.1, REDIS_HOST=127.0.0.1
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve            # :8000
php artisan queue:work --tries=3   # em outro terminal
php artisan orders:sync

# Frontend
cd frontend
npm install
npm run dev                  # :5173
```

---

## Testes

```bash
# Docker
docker compose exec app php artisan test

# Local
cd backend && php artisan test
```

Os testes usam SQLite em memória (`phpunit.xml`) — requer a extensão
`pdo_sqlite` habilitada. Cobrem a máquina de estados, o endpoint de métricas
(com e sem cache) e a integração do comando `orders:sync`.

---

## Endpoints da API

Todas as respostas seguem o formato `{ data, meta, errors }`.

| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/orders` | Lista paginada (20/pág). Filtros: `affiliate_id`, `status`, `date_from`, `date_to`, `min_value`, `max_value`; ordenação `sort_by`/`sort_dir` |
| GET | `/api/orders/{id}` | Detalhe com itens e histórico de status |
| GET | `/api/orders/metrics` | Métricas agregadas (cache Redis 5 min) |
| POST | `/api/orders/{id}/status` | Atualiza status (máquina de estados; 422 se inválido) |
| GET | `/api/affiliates` | Lista de afiliados (dropdown de filtro) |
| GET | `/api/affiliates/{id}/summary` | Total, receita, ticket médio, taxa de cancelamento |
| GET | `/api/health` | Status de MySQL, Redis e worker |

---

## Decisões técnicas e trade-offs

- **`created_at` = data de negócio do pedido.** Durante o sync, o `created_at`
  do pedido recebe a data do cart. Assim o índice composto
  `(affiliate_id, status, created_at)` e as métricas/cohort por período ficam
  diretamente úteis, sem coluna redundante `ordered_at`.
- **Índices pensados para 500k pedidos.** Além das FKs, há índice composto
  `(affiliate_id, status, created_at)` (padrão de filtro previsto) e
  `(status, created_at)` (métricas/cohort globais).
- **Total persistido em `orders.total`.** Evita recalcular `SUM(qty*price)` a
  cada leitura; recalculado no sync e mantido pela aplicação. As queries de
  auditoria (duplicados) recomputam a partir dos itens, conforme o enunciado.
- **Idempotência do sync.** Upsert por chave natural (`external_id` em
  orders/affiliates/products; `order_id+product_id` em itens). Reexecutar não
  duplica nem sobrescreve o status de domínio dos pedidos existentes.
- **Um Job por página + rate limiting.** `orders:sync` usa `Bus::chain`
  (afiliados → produtos → dispara N jobs de pedidos), garantindo as FKs. O
  `FakeStoreClient` combina `Http::retry` (falhas transitórias) com
  `RateLimiter` (Redis) limitando req/s à API externa. A fakestoreapi não tem
  paginação real por offset, então a "página" é um slice sobre a lista de
  carts — a arquitetura (1 job/página) não muda para uma API real.
- **Máquina de estados isolada.** `OrderStatusStateMachine` centraliza as
  transições válidas; transição inválida lança exceção que renderiza 422 no
  formato padrão. Zero `if/else` de status espalhado no service.
- **Cache de métricas invalidado por evento.** TTL de 5 min **e**
  `Cache::forget` a cada mudança de status (não confia só no TTL).
- **Notificação desacoplada.** Transição → evento `OrderStatusChanged` →
  listener → **job** com backoff que faz POST ao N8N. Falha no N8N não afeta a
  atualização do pedido.
- **Redis via `predis`.** Cliente PHP puro: não exige extensão nativa, simplifica
  imagem Docker e ambiente Windows.
- **Controllers finos.** Recebem Request (Form Request) e devolvem Response;
  regra de negócio no Service, queries no Repository.

---

## N8N — importar e testar os workflows

Os workflows ficam em [`n8n/workflows/`](n8n/workflows/) e são importados pelo
painel:

1. Acesse http://localhost:5678 e faça login (credenciais do `.env`).
2. **Workflows → Import from File** e selecione cada `.json`.
3. Ative os workflows. Os webhooks ficam disponíveis em
   `http://localhost:5678/webhook/order-approved` e `.../order-cancelled`.
4. Para testar ponta a ponta, altere o status de um pedido pela aplicação — o
   job de notificação faz POST ao `N8N_WEBHOOK_URL`.

Os workflows usam [webhook.site](https://webhook.site) como mock de
Slack/Discord/e-mail (troque a URL pela sua no nó de saída). Detalhes em
[`n8n/README.md`](n8n/README.md).

---

## Fora do escopo / próximos passos

- **Autenticação real no fluxo de status pela UI** já usa o usuário logado
  (Sanctum); faltaria RBAC mais granular por ação.
- **Paginação real da API externa**: a fakestoreapi não suporta offset; com uma
  API real, trocar o slice por requests `?_page=&_limit=` por job.
- **Observabilidade**: métricas de fila (Horizon) e tracing ficariam num
  próximo passo.
- **Testes E2E de frontend** (Playwright) e mais cobertura de componentes.
- **Índices adicionais** conforme padrões de consulta reais em produção.
