<?php

use App\Enums\SpotStatusEnum;
use App\Notifications\RefExchangeOrderFailed;

uses(Tests\TestCase::class);

function orderFailed(SpotStatusEnum $reason, array $overrides = []): RefExchangeOrderFailed
{
    return new RefExchangeOrderFailed(...$overrides + [
        'exchangeName' => 'Binance',
        'reason' => $reason,
        'marketName' => 'BNBUSDT',
        'amount' => '0.005',
        'side' => 'buy',
        'errorMessage' => 'Filter failure: NOTIONAL',
        'errorCode' => -1013,
        'tradeType' => 'otc',
        'userId' => 7,
        'orderId' => 42,
    ]);
}

it('names the exchange and the failure reason without any exchange-specific class', function () {
    $data = orderFailed(SpotStatusEnum::AmountTooSmall)->toDatabase(new stdClass());

    expect($data['message'])
        ->toContain('مقدار سفارش کمتر از حداقل مجاز صرافی مرجع است')
        ->toContain('Binance')
        ->toContain('BNBUSDT')
        ->toContain('سفارش OTC: #42')
        ->toContain('کاربر: #7')
        ->toContain('Filter failure: NOTIONAL (کد -1013)')
        ->and($data['url'])->toBe('/otc_orders')
        ->and($data['exchange'])->toBe('Binance')
        ->and($data['reason'])->toBe(SpotStatusEnum::AmountTooSmall->value);
});

it('picks the title from the reason', function () {
    expect(orderFailed(SpotStatusEnum::PriceDifferenceTooLarge)->toDatabase(new stdClass())['message'])
        ->toContain('اختلاف زیادی دارد');
    expect(orderFailed(SpotStatusEnum::BuyOrderFailed, ['tradeType' => 'spot', 'side' => 'sell'])->toDatabase(new stdClass()))
        ->message->toContain('خطا در ثبت سفارش در صرافی مرجع')->toContain('فروش اسپات')
        ->url->toBe('/spot/trades');
});

it('renders the mail with the exchange in the subject', function () {
    $mail = orderFailed(SpotStatusEnum::AmountTooSmall)->toMail(new stdClass());

    expect($mail->subject)->toBe('مقدار سفارش کمتر از حداقل مجاز صرافی مرجع است (Binance)')
        ->and((string) $mail->render())->toContain('Filter failure: NOTIONAL');
});
