<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SelectsApiController extends Controller
{
    public function users()
    {
        return \App\Models\User::query()
            ->select('id','username', 'first_name','last_name', 'email')
            ->where('last_name', 'LIKE', '%'.request()->input('term').'%')
            ->orWhere('email', 'LIKE', '%'.request()->input('term').'%')
            ->orWhere('username', 'LIKE', '%'.request()->input('term').'%')
            ->get()->toJson();
    }

    public function admins()
    {
        return \App\Models\Admin::query()
            ->select('id', 'first_name', 'last_name', 'mobile')
            ->where('first_name', 'LIKE', '%'.request()->input('term').'%')
            ->orWhere('last_name', 'LIKE', '%'.request()->input('term').'%')
            ->orWhere('mobile', 'LIKE', '%'.request()->input('term').'%')
            ->get()->toJson();
    }
}
