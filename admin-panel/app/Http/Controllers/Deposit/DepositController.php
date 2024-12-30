<?php

namespace App\Http\Controllers\Deposit;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function index()
    {
        $deposits = Deposit::with('currency')->get();
        return view('dashboard.deposits.index', [
            'deposits' => $deposits
        ]);
    }
}
