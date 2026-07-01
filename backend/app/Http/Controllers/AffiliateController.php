<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AffiliateController extends Controller
{
    public function __construct(
        private readonly AffiliateService $affiliates,
    ) {}

    public function summary(int $id): JsonResponse
    {
        return ApiResponse::success($this->affiliates->summary($id));
    }
}
