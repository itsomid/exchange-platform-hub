<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bot\QuickUpdateBotSignalRequest;
use App\Http\Requests\Bot\StoreBotSignalRequest;
use App\Http\Requests\Bot\UpdateBotSignalRequest;
use App\Models\Bot\BotSignal;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BotSignalController extends Controller
{
    public function index(Request $request): View
    {
        $query = BotSignal::with('currency.baseMarket.activeExchangePrice');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('currency', fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('symbol', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        $signals = $query->orderBy('priority')->paginate(100)->withQueryString();

        return view('dashboard.bot.signals.index', compact('signals'));
    }

    public function create(): View
    {
        $currencies   = Currency::orderBy('name')->get(['id', 'name', 'symbol', 'logo']);
        $globalP2pMin = \App\Models\Bot\BotGlobalSettings::current()->p2p_min_order_value ?? 5;

        return view('dashboard.bot.signals.create', compact('currencies', 'globalP2pMin'));
    }

    public function store(StoreBotSignalRequest $request): RedirectResponse
    {
        $data             = $request->validated();
        $data['priority'] = (BotSignal::max('priority') ?? 0) + 1;
        $signal           = BotSignal::create($data);

        // A signal that starts active is a new buy opportunity; flag it so the
        // api-service scan command runs a buy round for eligible users.
        if ($signal->is_active) {
            $signal->update(['activation_pending_at' => now()]);
        }

        return redirect()
            ->route('admin.bot.signal.index')
            ->with('success', 'سیگنال جدید با موفقیت ایجاد شد.');
    }

    public function edit(BotSignal $botSignal): View
    {
        $currencies   = Currency::orderBy('name')->get(['id', 'name', 'symbol', 'logo']);
        $globalP2pMin = \App\Models\Bot\BotGlobalSettings::current()->p2p_min_order_value ?? 5;

        return view('dashboard.bot.signals.edit', compact('botSignal', 'currencies', 'globalP2pMin'));
    }

    public function update(UpdateBotSignalRequest $request, BotSignal $botSignal): RedirectResponse
    {
        $wasActive = $botSignal->is_active;
        $botSignal->update($request->validated());

        // Inactive -> active transition is a new buy opportunity.
        if (! $wasActive && $botSignal->is_active) {
            $botSignal->update(['activation_pending_at' => now()]);
        }

        return redirect()
            ->route('admin.bot.signal.index')
            ->with('success', 'سیگنال با موفقیت ویرایش شد.');
    }

    public function quickUpdate(QuickUpdateBotSignalRequest $request, BotSignal $botSignal): JsonResponse
    {
        $data = $request->validated();
        $data['sell_orders_count'] = count($data['sell_targets']);

        $botSignal->update($data);
        $botSignal->refresh();

        return response()->json([
            'success' => true,
            'message' => 'سیگنال با موفقیت ویرایش شد.',
            'signal'  => [
                'id'                     => $botSignal->id,
                'floor_price'            => formatNumberTrimZeros($botSignal->floor_price),
                'ceiling_price'          => formatNumberTrimZeros($botSignal->ceiling_price),
                'max_allocation_percent' => (float) $botSignal->max_allocation_percent,
                'sell_orders_count'      => $botSignal->sell_orders_count,
                'sell_mode'              => $botSignal->sell_mode,
                'sell_targets'           => $botSignal->sell_targets,
            ],
        ]);
    }

    public function destroy(BotSignal $botSignal): RedirectResponse
    {
        $botSignal->delete();

        return redirect()
            ->route('admin.bot.signal.index')
            ->with('success', 'سیگنال با موفقیت حذف شد.');
    }

    public function toggleStatus(BotSignal $botSignal): RedirectResponse
    {
        $newActive  = ! $botSignal->is_active;
        $attributes = ['is_active' => $newActive];

        // Turning a stopped signal back on is a new buy opportunity.
        if ($newActive) {
            $attributes['activation_pending_at'] = now();
        }

        $botSignal->update($attributes);

        $label = $botSignal->is_active ? 'فعال' : 'غیرفعال';

        return redirect()
            ->back()
            ->with('success', "سیگنال {$label} شد.");
    }

    public function reorder(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'exists:bot_signals,id'],
        ]);

        $ids           = $data['order'];
        $newPriorities = [];
        foreach ($ids as $index => $id) {
            $priority           = $index + 1;
            BotSignal::where('id', $id)->update(['priority' => $priority]);
            $newPriorities[$id] = $priority;
        }

        return response()->json(['success' => true, 'priorities' => $newPriorities]);
    }
}
