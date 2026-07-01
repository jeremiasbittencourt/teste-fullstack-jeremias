<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderDetailResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
    ) {}

    public function index(IndexOrderRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 20);

        return ApiResponse::paginated($this->orders->list($filters, $perPage), OrderResource::class);
    }

    public function metrics(): JsonResponse
    {
        return ApiResponse::success($this->orders->metrics());
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orders->find($id);

        abort_if($order === null, 404, 'Pedido não encontrado.');

        return ApiResponse::success((new OrderDetailResource($order))->resolve());
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $order = $this->orders->transitionStatus($order, $request->status(), $request->user());

        return ApiResponse::success((new OrderResource($order->load('affiliate')))->resolve());
    }
}
