<?php

namespace App\Services\Bot\ReferenceExchange;

/**
 * Test-Lab-only decorator: fails the last placeLimitSell in a known batch with
 * a transport/SSL-style error so OpenSellOrdersJob's rollback of earlier tiers
 * + markFailed path can be exercised.
 *
 * Call {@see expectLimitSells()} once before the placement loop with the batch
 * size; the Nth (last) call then fails. All other methods are delegated.
 */
final class TestLabFailingSellExchange implements ExchangeContract
{
    private int $limitSellCalls = 0;

    /** 1-based index of the placeLimitSell call that should fail; null = no failure. */
    private ?int $failOnCall = null;

    public function __construct(
        private readonly ExchangeContract $inner,
    ) {}

    /**
     * Arm failure on the last call of an upcoming placeLimitSell batch.
     */
    public function expectLimitSells(int $total): void
    {
        $this->limitSellCalls = 0;
        $this->failOnCall = max(1, $total);
    }

    public function placeMarketBuy(string $market, string $quoteAmount): ExchangeOrderResult
    {
        return $this->inner->placeMarketBuy($market, $quoteAmount);
    }

    public function placeLimitSell(string $market, string $baseAmount, string $price): ExchangeOrderResult
    {
        $this->limitSellCalls++;

        if ($this->failOnCall !== null && $this->limitSellCalls === $this->failOnCall) {
            return new ExchangeOrderResult(
                exchangeOrderId: null,
                status:          ExchangeOrderStatus::FAILED,
                errorCode:       'TRANSPORT',
                errorMessage:    'cURL error 60: SSL certificate problem: unable to get local issuer certificate (simulated by Test Lab)',
            );
        }

        return $this->inner->placeLimitSell($market, $baseAmount, $price);
    }

    public function placeMarketSell(string $market, string $baseAmount): ExchangeOrderResult
    {
        return $this->inner->placeMarketSell($market, $baseAmount);
    }

    public function getOrder(string $market, string $exchangeOrderId): ExchangeOrderResult
    {
        return $this->inner->getOrder($market, $exchangeOrderId);
    }

    public function cancelOrder(string $market, string $exchangeOrderId): void
    {
        $this->inner->cancelOrder($market, $exchangeOrderId);
    }
}
