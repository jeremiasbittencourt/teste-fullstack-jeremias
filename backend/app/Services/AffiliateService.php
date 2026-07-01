<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Repositories\AffiliateRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AffiliateService
{
    public function __construct(
        private readonly AffiliateRepository $affiliates,
    ) {}

    /**
     * Resumo do afiliado: total de pedidos, receita, ticket médio e taxa de cancelamento.
     *
     * @return array<string, mixed>
     */
    public function summary(int $affiliateId): array
    {
        $affiliate = $this->affiliates->find($affiliateId);

        if ($affiliate === null) {
            throw (new ModelNotFoundException)->setModel(Affiliate::class, [$affiliateId]);
        }

        return array_merge(
            [
                'affiliate' => [
                    'id' => $affiliate->id,
                    'name' => $affiliate->name,
                    'external_id' => $affiliate->external_id,
                ],
            ],
            $this->affiliates->summary($affiliateId),
        );
    }
}
