<?php

namespace App\Filters\TicketFilters;

use App\Filters\FilterContract;
use App\Models\Deposit;
use App\Models\OTCOrder;
use App\Models\StockContract;
use App\Models\Withdrawal;

class Type implements FilterContract
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function handle($value = null): void
    {
        if (! is_null($value)) {
            // Map enum values to their corresponding model classes stored in ticketable_type
            $map = [
                'withdrawal' => Withdrawal::class,
                'deposit' => Deposit::class,
                'otc_order' => OTCOrder::class,
                'stock' => StockContract::class,
            ];

            if ($value === 'unknown') {
                $this->query->whereNull('ticketable_type');
                return;
            }

            $modelClass = $map[$value] ?? null;
            if ($modelClass) {
                $this->query->where('ticketable_type', $modelClass);
            }
        }
    }
}
