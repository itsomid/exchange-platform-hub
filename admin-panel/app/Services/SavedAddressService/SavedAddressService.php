<?php

namespace App\Services\SavedAddressService;

use App\Models\SavedAddress;
use App\Models\User;

class SavedAddressService
{
    public function listAddresses(int $userId, $chain = null)
    {
        $query = SavedAddress::query()->with(['currencyChain','user'])->where('user_id', $userId);

        if ($chain) {
            $query->where('chain', $chain);
        }

        return $query->get();
    }

    public function saveAddress($userId, $chain, $name, $address)
    {
        return SavedAddress::create([
            'user_id' => $userId,
            'name' => $name,
            'chain' => $chain,
            'address' => $address
        ]);
    }

    public function deleteAddress($userId, $chain)
    {
        $address = SavedAddress::query()->where('user_id', $userId)->where('chain',$chain)->firstOrFail();
        return $address->delete();
    }


}
