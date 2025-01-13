<?php

namespace App\Services\OTC;

use App\Models\OTCOrder;
use App\Repositories\Interfaces\OTCOrderRepositoryInterface;
use App\Services\OTC\DTO\Order\OTCOrderListsRequestDTO;
use App\Services\OTC\DTO\Order\OTCOrderListsResponseDTO;

class OTCOrderService
{
    public function __construct(private readonly OTCOrderRepositoryInterface $orderRepository) {}

    public function lists(OTCOrderListsRequestDTO $requestDTO): array
    {
        $orders = $this->orderRepository->lists($requestDTO->getUserId(), $requestDTO->getFilterQueryString());

        return $orders->map(fn (OTCOrder $order) => resolve(OTCOrderListsResponseDTO::class)
            ->setCreatedAt($order->created_at)
            ->setMarket($order->market->market_name)
            ->setType($order->type)
            ->setQuantity($order->quantity)
            ->setPrice($order->price)
            ->setFee($order->fee)
            ->setStatus($order->status)
        )->toArray();
    }
}
