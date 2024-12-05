<?php

return [
    'scale_precision' => env('BITEXROOM_SCALE_PRECISION', 8),
    'deposit_watching_per_minutes' => env('BITEXROOM_DEPOSIT_WATCHING_PER_MINUTES', 60 * 8), //Default is 8H
];
