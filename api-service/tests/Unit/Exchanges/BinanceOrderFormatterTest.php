<?php

use App\Enums\SpotStatusEnum;
use App\Services\Exchanges\Asset\Binance\BinanceOrderFormatter;

it('floors the quantity to the lot step and never adds thousand separators', function () {
    expect(BinanceOrderFormatter::formatQuantity('1500.129', '0.01000000'))->toBe('1500.12');
    expect(BinanceOrderFormatter::formatQuantity('12345.9', '1.00000000'))->toBe('12345');
    expect(BinanceOrderFormatter::formatQuantity('0.000019', '0.00001000'))->toBe('0.00001');
    expect(BinanceOrderFormatter::formatQuantity('0.123456789', null, 6))->toBe('0.123456');
});

it('falls back to LOT_SIZE when MARKET_LOT_SIZE reports a zero step', function () {
    $filters = [
        'stepSize' => '0.00001000',
        'minQty' => '0.00001000',
        'marketStepSize' => '0.00000000',
        'marketMinQty' => '0.00000000',
    ];

    expect(BinanceOrderFormatter::stepFor('MARKET', $filters))->toBe('0.00001');
    expect(BinanceOrderFormatter::minQtyFor('MARKET', $filters))->toBe('0.00001');
});

it('uses MARKET_LOT_SIZE for market orders when it is set', function () {
    $filters = ['stepSize' => '0.01', 'marketStepSize' => '0.1'];

    expect(BinanceOrderFormatter::stepFor('MARKET', $filters))->toBe('0.1');
    expect(BinanceOrderFormatter::stepFor('LIMIT', $filters))->toBe('0.01');
});

it('maps binance rejections onto the statuses the OTC and spot flows react to', function () {
    expect(BinanceOrderFormatter::mapError(-2010, 'Account has insufficient balance for requested action.'))
        ->toBe(SpotStatusEnum::NotEnoughBalance);
    expect(BinanceOrderFormatter::mapError(-1013, 'Filter failure: NOTIONAL'))
        ->toBe(SpotStatusEnum::AmountTooSmall);
    expect(BinanceOrderFormatter::mapError(-1013, 'Filter failure: LOT_SIZE'))
        ->toBe(SpotStatusEnum::AmountTooSmall);
    expect(BinanceOrderFormatter::mapError(-1013, 'Filter failure: PERCENT_PRICE_BY_SIDE'))
        ->toBe(SpotStatusEnum::PriceDifferenceTooLarge);
    expect(BinanceOrderFormatter::mapError(-2010, 'Market is closed.'))
        ->toBe(SpotStatusEnum::BuyOrderFailed);
});

it('reports the commission in the asset binance actually charged', function () {
    $commission = BinanceOrderFormatter::commission([
        ['commission' => '0.00010000', 'commissionAsset' => 'BTC'],
        ['commission' => '0.00005000', 'commissionAsset' => 'BTC'],
    ], ['BTC', 'USDT']);

    expect($commission)->toBe(['amount' => '0.00015', 'currency' => 'BTC']);
});

it('prefers the traded asset when fills were charged in mixed assets', function () {
    $commission = BinanceOrderFormatter::commission([
        ['commission' => '0.00020000', 'commissionAsset' => 'BNB'],
        ['commission' => '0.00010000', 'commissionAsset' => 'BTC'],
    ], ['BTC', 'USDT']);

    expect($commission)->toBe(['amount' => '0.0001', 'currency' => 'BTC']);
    expect(BinanceOrderFormatter::commission([]))->toBe(['amount' => '0', 'currency' => null]);
});
