<?php

namespace App\Events\Bot;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BotAutoTradeToggled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly bool $enabled,
    ) {}
}
