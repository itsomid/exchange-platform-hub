<?php

namespace App\Http\Controllers\ApiSystem;

use App\Http\Controllers\Controller;
use App\Models\ApiSystem\System;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;

class ApiSystemController extends Controller
{
    /**
     * Display a listing of API systems.
     */
    public function index(Request $request): View
    {
        $query = System::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        $systems = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('dashboard.api-system.index', compact('systems'));
    }

    /**
     * Show the form for creating a new API system.
     */
    public function create(): View
    {
        return view('dashboard.api-system.create');
    }

    /**
     * Store a newly created API system.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:systems,name',
            'description' => 'nullable|string|max:1000',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'allowed_ips' => 'nullable|string',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:10000',
            'is_active' => 'boolean',
        ]);

        // Process allowed IPs
        if ($validated['allowed_ips']) {
            $ips = array_map('trim', explode("\n", $validated['allowed_ips']));
            $validated['allowed_ips'] = array_filter($ips, function ($ip) {
                return !empty($ip);
            });
        } else {
            $validated['allowed_ips'] = [];
        }

        $system = System::create($validated);

        return redirect()
            ->route('admin.api-system.show', $system)
            ->with('success', 'سیستم API با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified API system.
     */
    public function show(System $system)
    {
        $system->load(['tokens' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }, 'requestLogs' => function ($query) {
            $query->orderBy('requested_at', 'desc')->limit(20);
        }]);

        // Calculate statistics for the view
        $statistics = [
            'total_requests' => $system->requestLogs()->count(),
            'successful_requests' => $system->requestLogs()->where('response_status', '>=', 200)->where('response_status', '<', 300)->count(),
            'avg_response_time' => $system->requestLogs()->avg('response_time_ms'),
            'total_tokens' => $system->tokens()->count(),
            'active_tokens' => $system->tokens()->where('is_active', true)->count(),
            'requests_today' => $system->requestLogs()->whereDate('requested_at', today())->count(),
        ];

        return view('dashboard.api-system.show', compact('system', 'statistics'));
    }

    /**
     * Show the form for editing the specified API system.
     */
    public function edit(System $system): View
    {
        return view('dashboard.api-system.edit', compact('system'));
    }

    /**
     * Update the specified API system.
     */
    public function update(Request $request, System $system)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:api_system_db.systems,name,' . $system->id,
            'description' => 'nullable|string|max:1000',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'allowed_ips' => 'nullable|string',
            'rate_limit_per_minute' => 'nullable|integer|min:1|max:200',
            'rate_limit_per_hour' => 'nullable|integer|min:1|max:1200',
            'is_active' => 'boolean',
        ]);

        // Process allowed IPs
        if ($validated['allowed_ips']) {
            $ips = array_map('trim', explode("\n", $validated['allowed_ips']));
            $validated['allowed_ips'] = array_filter($ips, function ($ip) {
                return !empty($ip);
            });
        } else {
            $validated['allowed_ips'] = [];
        }

        $system->update($validated);

        return redirect()
            ->route('admin.api-system.show', $system)
            ->with('success', 'سیستم API با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Toggle the active status of the API system.
     */
    public function toggleStatus(System $system): RedirectResponse
    {
        $system->update([
            'is_active' => !$system->is_active,
        ]);

        $status = $system->is_active ? 'فعال' : 'غیرفعال';

        return redirect()
            ->back()
            ->with('success', "وضعیت سیستم API به {$status} تغییر یافت.");
    }

    /**
     * Remove the specified API system.
     */
    public function destroy(System $system): RedirectResponse
    {
        // Check if system has active tokens
        if ($system->tokens()->active()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'نمی‌توان سیستم API را حذف کرد زیرا دارای توکن‌های فعال است.');
        }

        $system->delete();

        return redirect()
            ->route('admin.api-system.index')
            ->with('success', 'سیستم API با موفقیت حذف شد.');
    }

    /**
     * Show API system statistics.
     */
    public function statistics(System $system): View
    {
        $stats = [
            'total_tokens' => $system->tokens()->count(),
            'active_tokens' => $system->tokens()->active()->count(),
            'total_requests' => $system->requestLogs()->count(),
            'successful_requests' => $system->requestLogs()->where('response_status', '>=', 200)->where('response_status', '<', 300)->count(),
            'avg_response_time' => $system->requestLogs()->avg('response_time_ms'),
        ];

        // Recent activity
        $recentActivity = $system->requestLogs()
            ->orderBy('requested_at', 'desc')
            ->limit(50)
            ->get();

        return view('dashboard.api-system.statistics', compact('system', 'stats', 'recentActivity'));
    }
}
