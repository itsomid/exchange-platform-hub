<?php

namespace App\Http\Controllers\User;

use App\Enums\UserFinancialBlockAction;
use App\Functions\FlashMessages\Toast;
use App\Helpers\DateFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserFinancialBlock;
use Illuminate\Http\Request;

class UserFinancialBlockController extends Controller
{
    public function index()
    {
        return $blockedUsers = User::with('financialBlocks')->get();
        return view('user.financial_block.index');
    }
    public function getBlocks(User $user)
    {
        $userFinancialBlockActions = UserFinancialBlockAction::cases();

        $user->load('financialBlocks');
        return view('dashboard.user.financial-block', [
            'user' => $user,
            'userFinancialBlockActions' => $userFinancialBlockActions
        ]);
    }

    public function addBlock(Request $request, User $user)
    {
        $request->validate([
            'action' => 'required|string',
            'reason' => 'nullable|string',
        ]);

         UserFinancialBlock::query()->create([
            'user_id' => $user->id,
            'action' => $request->action,
            'reason' => $request->reason,
            'restricted_until' =>  DateFormatter::convertPersianToCarbonDate($request->restricted_until),
        ]);

        Toast::message('محدودیت با موفقیت اعمال شد.')->success()->notify();

        return redirect()->back();
    }

    public function createMassBlock()
    {

        return view('dashboard.user.financial-block.create-mass-block');
    }
}
