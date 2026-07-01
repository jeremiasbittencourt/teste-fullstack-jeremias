<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Padroniza toda resposta da API no formato { data, meta, errors }.
 */
final class ApiResponse
{
    /**
     * @param  mixed  $data
     * @param  array<string, mixed>|null  $meta
     */
    public static function success(mixed $data, ?array $meta = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => $meta,
            'errors' => null,
        ], $status);
    }

    /**
     * Resposta paginada. Mapeia os itens pela Resource informada e monta o meta.
     *
     * @param  class-string<JsonResource>  $resourceClass
     */
    public static function paginated(LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        return response()->json([
            'data' => $resourceClass::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'errors' => null,
        ]);
    }
}
