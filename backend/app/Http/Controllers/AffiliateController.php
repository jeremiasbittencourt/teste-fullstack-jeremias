<?php

namespace App\Http\Controllers;

use App\Http\Resources\AffiliateResource;
use App\Repositories\AffiliateRepository;
use App\Services\AffiliateService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function __construct(
        private readonly AffiliateService $affiliates,
        private readonly AffiliateRepository $repository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 100), 200);

        return ApiResponse::paginated(
            $this->repository->paginate($request->string('search')->toString() ?: null, $perPage),
            AffiliateResource::class,
        );
    }

    public function summary(int $id): JsonResponse
    {
        return ApiResponse::success($this->affiliates->summary($id));
    }
}
