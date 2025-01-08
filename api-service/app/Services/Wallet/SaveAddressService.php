<?php

namespace App\Services\Wallet;

use App\Models\SavedAddress;

class SaveAddressService
{
    public function listAddresses(int $userId, $chain = null)
    {
        $query = SavedAddress::query()->where('user_id', $userId);

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

    public function deleteAddress(int $userId, string $name)
    {
        SavedAddress::query()
            ->where('user_id', $userId)
            ->where('name', $name)
            ->delete();
    }
}
