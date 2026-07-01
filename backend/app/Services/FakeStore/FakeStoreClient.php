<?php

namespace App\Services\FakeStore;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Cliente HTTP da fakestoreapi.
 *
 * Estratégia de resiliência (documentada no README):
 *  - Http::retry(N, backoff) para falhas transitórias de rede/5xx.
 *  - Rate limiting via RateLimiter (Redis) para não estourar a API externa:
 *    no máximo `rate_limit` requisições por segundo, com espera ativa curta
 *    quando a janela está saturada.
 *
 * Observação sobre paginação: a fakestoreapi não expõe offset/page real.
 * Simulamos paginação sobre a lista completa de carts (array_slice), o que é
 * suficiente para o dataset fake. Em uma API real, cada página viraria um
 * request `?_page=&_limit=` — a arquitetura (um Job por página) não muda.
 */
class FakeStoreClient
{
    private string $baseUrl;

    private int $rateLimit;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.fakestore.base_url'), '/');
        $this->rateLimit = max(1, (int) config('services.fakestore.rate_limit', 30));
    }

    /** @return array<int, array<string, mixed>> */
    public function users(): array
    {
        return $this->get('/users');
    }

    /** @return array<int, array<string, mixed>> */
    public function products(): array
    {
        return $this->get('/products');
    }

    /** @return array<int, array<string, mixed>> */
    public function carts(): array
    {
        return $this->get('/carts');
    }

    public function cartsTotal(): int
    {
        return count($this->carts());
    }

    /**
     * Retorna a "página" de carts (slice sobre a lista completa — ver nota da classe).
     *
     * @return array<int, array<string, mixed>>
     */
    public function cartsPage(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        return array_slice($this->carts(), $offset, $perPage);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function get(string $path, array $query = []): array
    {
        $this->throttle();

        $response = $this->request()->get($this->baseUrl.$path, $query);

        $response->throw();

        return $response->json() ?? [];
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout((int) config('services.fakestore.timeout', 15))
            ->retry(
                (int) config('services.fakestore.retries', 3),
                (int) config('services.fakestore.retry_backoff', 200),
                throw: false,
            );
    }

    /**
     * Espera ativa até haver espaço na janela de rate limit (1s).
     */
    private function throttle(): void
    {
        // Tenta até ~5s antes de desistir e seguir (evita travar o worker).
        for ($i = 0; $i < 25; $i++) {
            $allowed = RateLimiter::attempt(
                key: 'fakestore-api',
                maxAttempts: $this->rateLimit,
                callback: fn () => true,
                decaySeconds: 1,
            );

            if ($allowed) {
                return;
            }

            usleep(200_000); // 200ms
        }
    }
}
