<?php

namespace App\Http\Controllers\Wallet;

use App\Http\Controllers\Controller;
use App\Models\LockedBalanceDetail;
use App\Enums\LockedBalanceTypeEnum;
use Illuminate\Http\Request;

class LockedBalanceDetailController extends Controller
{
    public function index(Request $request)
    {
        // Set default filter for active records if no deleted filter is specified
        if (!$request->has('deleted')) {
            $request->merge(['deleted' => '0']);
        }

        $query = $this->buildQuery($request);

        // Get paginated results
        $lockedBalances = $query->with([
            'wallet.user',
            'wallet.currency',
            'spot'
        ])->paginate(50)->appends($request->all());

        // Get filter options
        $types = LockedBalanceTypeEnum::cases();

        return view('dashboard.wallet.locked-balances', compact(
            'lockedBalances',
            'types'
        ));
    }

    private function buildQuery(Request $request)
    {
        $query = LockedBalanceDetail::query();

        // Include soft deleted records handling
        if ($request->filled('deleted')) {
            if ($request->deleted == '1') {
                $query->onlyTrashed();
            } elseif ($request->deleted == '0') {
                $query->withoutTrashed();
            } else {
                $query->withTrashed();
            }
        } else {
            $query->withTrashed();
        }

        // User filter
        if ($request->filled('user')) {
            $query->whereHas('wallet.user', function ($q) use ($request) {
                $q->where('id', $request->user);
            });
        }

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Spot order filter
        if ($request->filled('spot_order_id')) {
            $query->where('spot_order_id', $request->spot_order_id);
        }

        // Description filter
        if ($request->filled('description')) {
            $query->where('description', 'like', '%' . $request->description . '%');
        }

        // Default sorting
        $query->orderBy('created_at', 'desc');

        return $query;
    }
}
