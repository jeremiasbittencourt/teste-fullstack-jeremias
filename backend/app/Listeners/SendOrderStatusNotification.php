<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Jobs\NotifyN8nJob;

/**
 * Listener dedicado: reage à transição de status e enfileira o job que
 * notifica o N8N. Não chama o webhook diretamente (desacoplamento).
 */
class SendOrderStatusNotification
{
    public function handle(OrderStatusChanged $event): void
    {
        NotifyN8nJob::dispatch($event->toPayload());
    }
}
