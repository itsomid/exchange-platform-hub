<?php

namespace App\Http\Controllers\ApiSystem;

use App\Enums\ApiRequestType;
use App\Http\Controllers\Controller;
use App\Models\ApiSystem\System;
use App\Models\ApiSystem\SystemToken;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class ApiSystemTokenController extends Controller
{
    /**
     * Display tokens for a specific system.
     */
    public function index(System $system): View
    {
        $tokens = $system->tokens()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $availableScopes = ApiRequestType::getOptions();

        return view('dashboard.api-system.tokens.index', compact('system', 'tokens', 'availableScopes'));
    }

    /**
     * Show the form for creating a new token.
     */
    public function create(System $system): View
    {
        $availableScopes = ApiRequestType::getOptions();

        return view('dashboard.api-system.tokens.create', compact('system', 'availableScopes'));
    }

    /**
     * Store a newly created token.
     */
    public function store(Request $request, System $system): RedirectResponse
    {
        $validScopes = implode(',', array_keys(ApiRequestType::getOptions()));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scopes' => 'required|array|min:1',
            'scopes.*' => "string|in:{$validScopes}",
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'boolean',
        ]);

        $validated['system_id'] = $system->id;

        // Generate the token
        $token = SystemToken::generateToken($validated);

        return redirect()
            ->route('admin.api-system.tokens.show', [$system, $token])
            ->with('success', 'توکن API با موفقیت ایجاد شد.')
            ->with('new_token', $token->token); // Show token only once
    }

    /**
     * Display the specified token.
     */
    public function show(System $system, SystemToken $token)
    {
        // Load recent usage statistics
        $recentLogs = $token->system->requestLogs()
            ->where('created_at', '>=', now()->subDays(30))
            ->orderBy('requested_at', 'desc')
            ->limit(20)
            ->get();

        $usageStats = [
            'total_requests' => $token->system->requestLogs()->count(),
            'requests_last_30_days' => $token->system->requestLogs()
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
            'successful_requests' => $token->system->requestLogs()
                ->where('response_status', '>=', 200)
                ->where('response_status', '<', 300)
                ->count(),
            'last_used' => $token->last_used_at,
        ];

        return view('dashboard.api-system.tokens.show', compact('system', 'token', 'recentLogs', 'usageStats'));
    }

    /**
     * Show the form for editing the specified token.
     */
    public function edit(System $system, SystemToken $token): View
    {
        $availableScopes = ApiRequestType::getOptions();

        return view('dashboard.api-system.tokens.edit', compact('system', 'token', 'availableScopes'));
    }

    /**
     * Update the specified token.
     */
    public function update(Request $request, System $system, SystemToken $token): RedirectResponse
    {
        $validScopes = implode(',', array_keys(ApiRequestType::getOptions()));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'scopes' => 'required|array|min:1',
            'scopes.*' => "string|in:{$validScopes}",
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'boolean',
        ]);

        $token->update($validated);

        return redirect()
            ->route('admin.api-system.tokens.index', $system)
            ->with('success', 'توکن API با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Toggle the active status of the token.
     */
    public function toggleStatus(System $system, SystemToken $token): RedirectResponse
    {
        $token->update([
            'is_active' => !$token->is_active,
        ]);

        $status = $token->is_active ? 'فعال' : 'غیرفعال';

        return redirect()
            ->back()
            ->with('success', "وضعیت توکن به {$status} تغییر یافت.");
    }

    /**
     * Regenerate the token.
     */
    public function regenerate(System $system, SystemToken $token): RedirectResponse
    {
        $newToken = $token->generateToken();

        return redirect()
            ->route('admin.api-system.tokens.show', [$system, $token])
            ->with('success', 'توکن جدید با موفقیت تولید شد.')
            ->with('new_token', $newToken); // Show new token only once
    }

    /**
     * Remove the specified token.
     */
    public function destroy(System $system, SystemToken $token): RedirectResponse
    {
        $token->delete();

        return redirect()
            ->route('admin.api-system.tokens.index', $system)
            ->with('success', 'توکن API با موفقیت حذف شد.');
    }

    /**
     * Get token usage statistics via AJAX.
     */
    public function getUsageStats(System $system, SystemToken $token): JsonResponse
    {
        $stats = [
            'requests_today' => $token->system->requestLogs()
                ->whereDate('requested_at', today())
                ->count(),
            'requests_this_week' => $token->system->requestLogs()
                ->where('requested_at', '>=', now()->startOfWeek())
                ->count(),
            'requests_this_month' => $token->system->requestLogs()
                ->where('requested_at', '>=', now()->startOfMonth())
                ->count(),
            'avg_response_time' => $token->system->requestLogs()
                ->where('requested_at', '>=', now()->subDays(7))
                ->avg('response_time_ms'),
            'error_rate' => $token->system->requestLogs()
                ->where('requested_at', '>=', now()->subDays(7))
                ->where('response_status', '>=', 400)
                ->count() / max(1, $token->system->requestLogs()
                    ->where('requested_at', '>=', now()->subDays(7))
                    ->count()) * 100,
        ];

        return response()->json($stats);
    }
}
