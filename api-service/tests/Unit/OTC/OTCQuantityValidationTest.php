<?php

use App\Http\Requests\V1\OTC\OTCBuyRequest;
use Illuminate\Support\Facades\Validator;

uses(Tests\TestCase::class);

it('rejects zero quantity on otc buy', function () {
    $rules = (new OTCBuyRequest())->rules();

    $validator = Validator::make(
        ['market_id' => 1, 'quantity' => 0],
        [
            'market_id' => ['required', 'integer'],
            'quantity' => $rules['quantity'],
        ]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('quantity'))->toBeTrue();
});

it('rejects negative quantity on otc buy', function () {
    $rules = (new OTCBuyRequest())->rules();

    $validator = Validator::make(
        ['market_id' => 1, 'quantity' => -1],
        [
            'market_id' => ['required', 'integer'],
            'quantity' => $rules['quantity'],
        ]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('quantity'))->toBeTrue();
});

it('accepts a positive quantity on otc buy numeric rules', function () {
    $rules = (new OTCBuyRequest())->rules();

    $validator = Validator::make(
        ['market_id' => 1, 'quantity' => 0.001],
        [
            'market_id' => ['required', 'integer'],
            'quantity' => $rules['quantity'],
        ]
    );

    expect($validator->fails())->toBeFalse();
});
