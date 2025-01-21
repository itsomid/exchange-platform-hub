<?php

namespace App\Http\Controllers\Deposit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class DepositReportController extends Controller
{
    public function index()
    {

        return view('dashboard.report.deposit');
    }
}
