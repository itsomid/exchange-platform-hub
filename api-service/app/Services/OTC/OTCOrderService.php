<?php

namespace App\Services\OTC;

use App\Enums\OTCOrderTypeEnum;
use App\Helpers\Math;
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

        return $orders->map(function (OTCOrder $order) {
            if ($order->type === OTCOrderTypeEnum::BUY) {
                $receivedAmount = Math::sub($order->quantity, $order->fee);
            } else {
                $receivedAmount = Math::sub(Math::mul(
                    $order->price,
                    $order->quantity
                ), $order->fee);
            }

            return resolve(OTCOrderListsResponseDTO::class)
                ->setCreatedAt($order->created_at)
                ->setMarket($order->market->market_name)
                ->setType($order->type)
                ->setQuantity($order->quantity)
                ->setPrice($order->price)
                ->setReceivedAmount($receivedAmount)
                ->setFee($order->fee)
                ->setStatus($order->status)
                ->setBaseCurrency($order->market->base_currency)
                ->setQuoteCurrency($order->market->quote_currency);
        })->toArray();
    }
}
