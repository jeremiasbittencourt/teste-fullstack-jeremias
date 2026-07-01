<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envia a notificação de mudança de status ao webhook do N8N.
 *
 * Assíncrono e isolado: falhas aqui NÃO afetam o fluxo de atualização do
 * pedido. Retry com backoff exponencial (3 tentativas). A URL vem de config
 * (services.n8n.webhook_url / N8N_WEBHOOK_URL) — nunca hardcoded.
 */
class NotifyN8nJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    /**
     * Backoff exponencial entre as tentativas (segundos).
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(): void
    {
        $url = config('services.n8n.webhook_url');

        if (blank($url)) {
            Log::info('NotifyN8nJob: N8N_WEBHOOK_URL não configurada, envio ignorado.');

            return;
        }

        Http::timeout(10)
            ->acceptJson()
            ->post($url, $this->payload)
            ->throw();
    }
}
