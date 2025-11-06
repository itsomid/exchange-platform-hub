<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StockController extends Controller
{

    public function index()
    {
        $stocks = Stock::with('contracts')->get();
        $totalStocks = $stocks->count();
        $activeStocks = $stocks->where('status', 'active')->count();
        return view('dashboard.stock.index',[
            'stocks' => $stocks,
            'totalStocks' => $totalStocks,
            'activeStocks' => $activeStocks,
        ]);
    }


    public function show($id)
    {
        $stock = Stock::findOrFail($id);
        return view('dashboard.stock.show', compact('stock'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'value' => 'required|numeric',
            'initial_quantity' => 'required|numeric|min:0',
            'available_quantity' => 'required|numeric|min:0',
            'type' => 'required|in:normal,gift,partner',
            'cancellation_fee' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);
        $stock = Stock::create($validated);
        return redirect()->route('admin.stock.index')->with('success', 'سهام با موفقیت ایجاد شد.');
    }

    public function update(Request $request, $id)
    {
        $stock = Stock::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'value' => 'sometimes|required|numeric',
            'initial_quantity' => 'sometimes|required|numeric|min:0',
            'available_quantity' => 'sometimes|required|numeric|min:0',
            'type' => 'sometimes|required|in:normal,gift,partner',
            'cancellation_fee' => 'sometimes|required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:active,inactive',
        ]);
        $stock->update($validated);
        return redirect()->route('admin.stock.index')->with('success', 'سهام با موفقیت بروزرسانی شد.');
    }

    public function destroy($id)
    {
        $stock = Stock::findOrFail($id);
        $stock->delete();
        return redirect()->route('admin.stock.index')->with('success', 'سهام با موفقیت حذف شد.');
    }

    public function create()
    {
        return view('dashboard.stock.create');
    }

    public function edit($id)
    {
        $stock = Stock::findOrFail($id);
        return view('dashboard.stock.edit', compact('stock'));
    }
}
