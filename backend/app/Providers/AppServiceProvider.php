<?php

namespace App\Providers;

use App\Events\OrderStatusChanged;
use App\Listeners\SendOrderStatusNotification;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Notificação orientada a eventos: transição → listener → job → N8N.
        Event::listen(OrderStatusChanged::class, SendOrderStatusNotification::class);

        // Heartbeat do worker: a cada iteração do loop do queue:work (mesmo ocioso)
        // grava um timestamp que o endpoint /api/health usa para saber se o
        // worker está ativo.
        Event::listen(Looping::class, function (): void {
            Cache::put('worker:heartbeat', time(), 120);
        });
    }
}
