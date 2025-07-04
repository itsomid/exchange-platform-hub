<?php

namespace App\Data;

class FileStoragePaths
{
    public static function PROFILE_DOWNLOAD_URL($file = null)
    {
        return config('bitexroom.file_upload_url_4').'/filepond/user_profile/'.$file;
    }

    public static function PROFILE_UPLOAD_URL()
    {
        return config('bitexroom.file_upload_url_4').'/api/upload/user_profile';
    }
}
