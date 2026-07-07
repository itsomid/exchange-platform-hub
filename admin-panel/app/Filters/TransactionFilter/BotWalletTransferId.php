<?php

namespace App\Filters\TransactionFilter;

use App\Filters\FilterContract;

class BotWalletTransferId implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value)) {
            $this->query->where('bot_wallet_transfer_id', $value);
        }
    }
}
