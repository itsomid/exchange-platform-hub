<?php

namespace App\Repositories;

use App\Enums\ProductAccessType;
use App\Models\ProductAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ProductAccessRepository
{
    public function getProductPurchasedId(int $userId, bool $excludeFree = false): Collection
    {
        return Cache::remember("getProductPurchasedId:{$userId}-{$excludeFree}", '', function () use ($userId, $excludeFree) {
            $productAccesses = ProductAccess::query()
                ->where('user_id', $userId)
                ->where(fn (Builder $query) => $query->where('effective_from_datetime', '>=', now())
                    ->orWhereNull('effective_from_datetime')
                )
                ->where(fn (Builder $query) => $query->where('effective_to_datetime', '>=', now())
                    ->orWhereNull('effective_to_datetime')
                );

            if ($excludeFree) {
                $productAccesses->where('access_reason_type', ProductAccessType::BOUGHT);
            }

            return $productAccesses->get();
        });
    }
}
