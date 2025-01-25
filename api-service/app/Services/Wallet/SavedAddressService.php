<?php

namespace App\Services\Wallet;

use App\Models\SavedAddress;

class SavedAddressService
{
    public function listAddresses(int $userId, $chain = null)
    {
        $query = SavedAddress::query()->with(['currencyChain', 'user'])->where('user_id', $userId);

        if ($chain) {
            $query->where('chain', $chain);
        }

        return $query->get();
    }

    public function saveAddress(int $userId, string $chain, string $name, string $address)
    {
        return SavedAddress::query()->create([
            'user_id' => $userId,
            'name' => $name,
            'chain' => $chain,
            'address' => $address,
        ]);
    }

    public function deleteAddress(int $userId, int $savedAddressId)
    {
        SavedAddress::query()
            ->where('user_id', $userId)
            ->where('id', $savedAddressId)
            ->firstOrFail()
            ->delete();
    }
}
