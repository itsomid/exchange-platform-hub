<?php

namespace App\Http\Controllers\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\Profile\ActiveSessionCollection;
use App\Services\Profile\ProfileService;

class SessionController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    public function active()
    {
        return new ActiveSessionCollection(
            $this->profileService->getActiveSessions(
                auth()->id()
            )
        );
    }
}
