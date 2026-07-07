<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Http\Controllers\Controller;
use App\Models\Bot\BotWalletTransfer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BotWalletTransferController extends Controller
{
    public function index(Request $request): View
    {
        $query = BotWalletTransfer::with([
            'user',
            'wallet',
            'botWallet',
            'transactions.wallet.currency',
            'transactions.admin',
        ]);

        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('user')) {
            $query->where('user_id', $request->input('user'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('amount_min')) {
            $query->where('gross_amount', '>=', $request->input('amount_min'));
        }

        if ($request->filled('amount_max')) {
            $query->where('gross_amount', '<=', $request->input('amount_max'));
        }

        $sortDirection = $request->input('sortById', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy('id', $sortDirection);

        $transfers = $query->paginate(20)->withQueryString();

        $today = now()->toDateString();

        $totalCount = BotWalletTransfer::count();
        $todayCount = BotWalletTransfer::whereDate('created_at', $today)->count();
        $totalDeposits = (float) BotWalletTransfer::where('direction', BotWalletTransfer::DIRECTION_IN)->sum('gross_amount');
        $totalWithdrawals = (float) BotWalletTransfer::where('direction', BotWalletTransfer::DIRECTION_OUT)->sum('gross_amount');
        $totalFees = (float) BotWalletTransfer::sum('fee');

        return view('dashboard.bot.wallet-transfers.index', [
            'transfers' => $transfers,
            'totalCount' => $totalCount,
            'todayCount' => $todayCount,
            'totalDeposits' => $totalDeposits,
            'totalWithdrawals' => $totalWithdrawals,
            'totalFees' => $totalFees,
        ]);
    }
}
