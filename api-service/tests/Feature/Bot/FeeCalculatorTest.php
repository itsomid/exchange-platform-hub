<?php

use App\Exceptions\Bot\BotTransferAmountTooLowException;
use App\Models\Bot\BotGlobalSettings;
use App\Services\Bot\FeeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedDefaultTransferFeeTiers(): void
{
    BotGlobalSettings::create([
        'min_deposit_usdt'          => 20,
        'alpha_weight'              => 0.15,
        'default_sell_orders_count' => 3,
        'performance_fee_percent'   => 22,
        'p2p_min_order_value'       => 5,
        'transfer_fee_tiers'        => BotGlobalSettings::defaultTransferFeeTiers(),
        'is_enabled'                => true,
    ]);
}

it('throws exception for amount below 20', function () {
    seedDefaultTransferFeeTiers();
    $calc = new FeeCalculator();
    expect(fn () => $calc->transferFee('19'))->toThrow(BotTransferAmountTooLowException::class);
    expect(fn () => $calc->transferFee('0'))->toThrow(BotTransferAmountTooLowException::class);
    expect(fn () => $calc->transferFee('19.99999999'))->toThrow(BotTransferAmountTooLowException::class);
});

it('charges 1 USDT flat for amounts 20 to 100', function () {
    seedDefaultTransferFeeTiers();
    $calc = new FeeCalculator();
    expect($calc->transferFee('20'))->toBe('1.00000000');
    expect($calc->transferFee('50'))->toBe('1.00000000');
    expect($calc->transferFee('100'))->toBe('1.00000000');
});

it('charges 1 percent for amounts above 100 to 1000', function () {
    seedDefaultTransferFeeTiers();
    $calc = new FeeCalculator();
    // 101 * 1% = 1.01
    expect($calc->transferFee('101'))->toBe('1.01000000');
    // 500 * 1% = 5
    expect($calc->transferFee('500'))->toBe('5.00000000');
    // 1000 * 1% = 10
    expect($calc->transferFee('1000'))->toBe('10.00000000');
});

it('charges 12 USDT flat for amounts above 1000', function () {
    seedDefaultTransferFeeTiers();
    $calc = new FeeCalculator();
    expect($calc->transferFee('1001'))->toBe('12.00000000');
    expect($calc->transferFee('5000'))->toBe('12.00000000');
});

it('applies same fee schedule to withdrawFee', function () {
    seedDefaultTransferFeeTiers();
    $calc = new FeeCalculator();
    expect($calc->withdrawFee('20'))->toBe('1.00000000');
    expect($calc->withdrawFee('500'))->toBe('5.00000000');
    expect($calc->withdrawFee('1001'))->toBe('12.00000000');
});

it('uses configured flat fee tiers from bot global settings', function () {
    BotGlobalSettings::create([
        'min_deposit_usdt'          => 20,
        'alpha_weight'              => 0.15,
        'default_sell_orders_count' => 3,
        'performance_fee_percent'   => 22,
        'p2p_min_order_value'       => 5,
        'transfer_fee_tiers'        => [
            ['from' => 20,   'to' => 100,  'fee_type' => 'flat', 'fee_value' => 1],
            ['from' => 100,  'to' => 1000, 'fee_type' => 'flat', 'fee_value' => 50],
            ['from' => 1000, 'to' => null, 'fee_type' => 'flat', 'fee_value' => 12],
        ],
        'is_enabled'                => true,
    ]);

    $calc = new FeeCalculator();
    expect($calc->transferFee('270'))->toBe('50.00000000');
    expect($calc->transferFee('100'))->toBe('1.00000000');
    expect($calc->transferFee('1001'))->toBe('12.00000000');
});

it('falls back to default tiers when transfer_fee_tiers is empty', function () {
    BotGlobalSettings::create([
        'min_deposit_usdt'          => 20,
        'alpha_weight'              => 0.15,
        'default_sell_orders_count' => 3,
        'performance_fee_percent'   => 22,
        'p2p_min_order_value'       => 5,
        'transfer_fee_tiers'        => [],
        'is_enabled'                => true,
    ]);

    $calc = new FeeCalculator();
    expect($calc->transferFee('500'))->toBe('5.00000000');
});
