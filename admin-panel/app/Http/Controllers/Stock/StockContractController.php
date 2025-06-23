<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\StockContract;
use App\Models\Stock;
use App\Models\User;
use App\Enums\StockContractStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StockContractController extends Controller
{
    public function index()
    {
        $contracts = StockContract::with(['user', 'stock'])->get();
        
        // Dashboard statistics
        $totalContracts = $contracts->count();
        $soldContracts = $contracts->where('contract_status', StockContractStatusEnum::SOLD)->count();
        $canceledContracts = $contracts->where('contract_status', StockContractStatusEnum::CANCELED)->count();
        $soldAmount = $contracts->where('contract_status', StockContractStatusEnum::SOLD)->sum('total_value');
        $canceledAmount = $contracts->where('contract_status', StockContractStatusEnum::CANCELED)->sum('total_value');
        $cancellationFees = $contracts->where('contract_status', StockContractStatusEnum::CANCELED)->sum('cancellation_fee');
        
        return view('dashboard.stock_cntract.index', [
            'contracts' => $contracts,
            'totalContracts' => $totalContracts,
            'soldContracts' => $soldContracts,
            'canceledContracts' => $canceledContracts,
            'soldAmount' => $soldAmount,
            'canceledAmount' => $canceledAmount,
            'cancellationFees' => $cancellationFees,
        ]);
    }

    public function create()
    {
        $stocks = Stock::where('status', 'active')->get();
        $users = User::all();
        return view('dashboard.stock_cntract.create', compact('stocks', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'stock_id' => 'required|exists:stocks,id',
            'amount' => 'required|numeric|min:1',
            'contract_status' => 'required|in:active,sold,canceled',
            'description' => 'nullable|string',
        ]);

        $stock = Stock::findOrFail($validated['stock_id']);
        $totalValue = $stock->value * $validated['amount'];

        $contractData = [
            'user_id' => $validated['user_id'],
            'stock_id' => $validated['stock_id'],
            'contract_number' => StockContract::generateContractNumber(),
            'amount' => $validated['amount'],
            'total_value' => $totalValue,
            'contract_status' => $validated['contract_status'],
            'cancellation_fee' => $stock->cancellation_fee,
            'description' => $validated['description'],
        ];

        // Set timestamps based on status
        if ($validated['contract_status'] === 'sold') {
            $contractData['sold_at'] = now();
        } elseif ($validated['contract_status'] === 'canceled') {
            $contractData['cancelled_at'] = now();
        }

        $contract = StockContract::create($contractData);

        return redirect()->route('admin.stock-contract.index')->with('success', 'قرارداد با موفقیت ایجاد شد.');
    }

    public function show(StockContract $stockContract)
    {
        $stockContract->load(['user', 'stock']);
        return view('dashboard.stock_cntract.show', compact('stockContract'));
    }

    public function edit(StockContract $stockContract)
    {
        $stocks = Stock::where('status', 'active')->get();
        $users = User::all();
        return view('dashboard.stock_cntract.edit', compact('stockContract', 'stocks', 'users'));
    }

    public function update(Request $request, StockContract $stockContract)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'stock_id' => 'required|exists:stocks,id',
            'amount' => 'required|numeric|min:1',
            'contract_status' => 'required|in:active,sold,canceled',
            'description' => 'nullable|string',
        ]);

        $stock = Stock::findOrFail($validated['stock_id']);
        $totalValue = $stock->value * $validated['amount'];

        $updateData = [
            'user_id' => $validated['user_id'],
            'stock_id' => $validated['stock_id'],
            'amount' => $validated['amount'],
            'total_value' => $totalValue,
            'contract_status' => $validated['contract_status'],
            'cancellation_fee' => $stock->cancellation_fee,
            'description' => $validated['description'],
        ];

        // Handle status changes and timestamps
        if ($validated['contract_status'] === 'sold' && $stockContract->contract_status !== StockContractStatusEnum::SOLD) {
            $updateData['sold_at'] = now();
        } elseif ($validated['contract_status'] === 'canceled' && $stockContract->contract_status !== StockContractStatusEnum::CANCELED) {
            $updateData['cancelled_at'] = now();
        }

        $stockContract->update($updateData);

        return redirect()->route('admin.stock-contract.index')->with('success', 'قرارداد با موفقیت بروزرسانی شد.');
    }

    public function destroy(StockContract $stockContract)
    {
        $stockContract->delete();
        return redirect()->route('admin.stock-contract.index')->with('success', 'قرارداد با موفقیت حذف شد.');
    }
}
