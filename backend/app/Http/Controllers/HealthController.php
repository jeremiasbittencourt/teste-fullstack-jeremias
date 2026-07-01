<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /** Considera o worker vivo se o heartbeat foi atualizado nos últimos 90s. */
    private const WORKER_TTL = 90;

    public function index(): JsonResponse
    {
        $services = [
            'mysql' => $this->check(fn () => DB::connection()->select('select 1')),
            'redis' => $this->check(fn () => (bool) Redis::connection()->ping()),
            'worker' => $this->workerStatus(),
        ];

        $healthy = ! in_array(false, array_column($services, 'healthy'), true);

        return response()->json([
            'data' => [
                'status' => $healthy ? 'ok' : 'degraded',
                'services' => $services,
            ],
            'meta' => null,
            'errors' => null,
        ], $healthy ? 200 : 503);
    }

    /**
     * @return array{healthy: bool, error?: string}
     */
    private function check(callable $probe): array
    {
        try {
            $probe();

            return ['healthy' => true];
        } catch (\Throwable $e) {
            return ['healthy' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{healthy: bool, last_seen_at: ?string}
     */
    private function workerStatus(): array
    {
        try {
            $timestamp = Cache::get('worker:heartbeat');
        } catch (\Throwable) {
            return ['healthy' => false, 'last_seen_at' => null];
        }

        $alive = $timestamp !== null && (time() - (int) $timestamp) <= self::WORKER_TTL;

        return [
            'healthy' => $alive,
            'last_seen_at' => $timestamp ? date(DATE_ATOM, (int) $timestamp) : null,
        ];
    }
}
