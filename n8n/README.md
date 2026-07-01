# Workflows N8N

Notificações orientadas a eventos. Após cada transição de status bem-sucedida,
o backend dispara o evento `OrderStatusChanged` → listener → **job assíncrono**
(`NotifyN8nJob`, retry com backoff exponencial, 3 tentativas) que faz `POST` ao
`N8N_WEBHOOK_URL` com o payload:

```json
{
  "event": "order.status_changed",
  "order_id": 42,
  "affiliate_id": 7,
  "previous_status": "pending",
  "new_status": "approved",
  "total_value": 259.90,
  "occurred_at": "2024-03-15T14:32:00Z"
}
```

Falhas no envio ao N8N **não** afetam a atualização do pedido (isolamento via job).

## Workflows

| Arquivo | Webhook | O que faz |
|---|---|---|
| `order-approved.json` | `POST /webhook/order-approved` | filtra `new_status = approved`, formata a mensagem, envia para Slack/Discord/e-mail (mock) e registra no Google Sheets (order_id, affiliate_id, total_value, approved_at) |
| `order-cancelled.json` | `POST /webhook/order-cancelled` | filtra `new_status = cancelled`, consulta `/api/affiliates/{id}/summary`; se a taxa de cancelamento > 30%, dispara alerta de atenção; registra o cancelamento |

## Como importar

1. Acesse http://localhost:5678 e faça login (credenciais do `.env`).
2. **Workflows → Import from File** → selecione cada `.json` desta pasta.
3. Nos nós de saída, troque as URLs `webhook.site/REPLACE-...` por um endpoint
   real (crie um em https://webhook.site e cole a URL). Os nós de HTTP são mocks
   de Slack/Discord/e-mail e do Google Sheets — substitua pelos nós nativos se
   tiver credenciais.
4. No `order-cancelled`, o nó "Consultar summary" chama a API autenticada:
   gere um token Sanctum (`/api/login`) e substitua `REPLACE_WITH_SANCTUM_TOKEN`.
5. **Ative** os workflows.

## Roteamento do webhook (trade-off)

O backend envia **um** payload para `N8N_WEBHOOK_URL`. Cada workflow filtra pelo
`new_status`, então só age no seu status. Duas formas de usar os dois workflows:

- **Simples (dev):** aponte `N8N_WEBHOOK_URL` para o webhook que quer exercitar
  (`.../webhook/order-approved` ou `.../webhook/order-cancelled`).
- **Roteado:** aponte `N8N_WEBHOOK_URL` para um webhook único (ex.:
  `order-status-changed`) e adicione um nó **Switch** por `new_status` que chama
  os dois fluxos — mantém um único ponto de entrada.

## Testar ponta a ponta

1. `docker compose exec app php artisan orders:sync` (popula pedidos).
2. Na aplicação, abra um pedido `pending` e mude para `approved` (ou `cancelled`).
3. O `worker` processa o `NotifyN8nJob` e o N8N recebe o POST — confira a
   execução em **Executions** no painel do N8N e no seu webhook.site.
