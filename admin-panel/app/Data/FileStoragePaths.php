<?php

namespace App\Data;

class FileStoragePaths
{
    public static function CONTRACT_DOWNLOAD_URL($file = null)
    {
        return config('bitexroom.contracts.base_url').'/storage/contracts/stock/'.$file;
    }

    public static function CONTRACT_UPLOAD_URL($file = null)
    {
        return config('bitexroom.contracts.base_url').'/storage/contracts/stock/'.$file;
    }
}
