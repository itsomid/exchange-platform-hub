<?php

namespace App\Http\Controllers\ApiSystem;

use App\Http\Controllers\Controller;
use App\Models\ApiSystem\ApiRequest;
use App\Models\ApiSystem\System;
use App\Enums\ApiRequestType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class ApiRequestController extends Controller
{
    /**
     * Display a listing of API requests.
     */
    public function index(Request $request): View
    {
        $query = ApiRequest::with('system');

        // Filter by system
        if ($request->filled('system_id')) {
            $query->where('system_id', $request->get('system_id'));
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by user ID
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference_id', 'like', "%{$search}%")
                    ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get filter options
        $systems = System::where('is_active', true)->get();
        $requestTypes = ApiRequestType::getOptions();
        $statuses = [
            'pending' => 'در انتظار',
            'processing' => 'در حال پردازش',
            'completed' => 'تکمیل شده',
            'failed' => 'ناموفق',
        ];

        return view('dashboard.api-system.requests.index', compact(
            'requests',
            'systems',
            'requestTypes',
            'statuses'
        ));
    }

    /**
     * Show the form for creating a new API request.
     */
    public function create(): View
    {
        $systems = System::where('is_active', true)->get();
        $requestTypes = ApiRequestType::getOptions();

        return view('dashboard.api-system.requests.create', compact('systems', 'requestTypes'));
    }

    /**
     * Store a newly created API request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'system_id' => 'required|exists:api_system_db.systems,id',
            'user_id' => 'required|integer|min:1',
            'type' => 'required|in:' . implode(',', array_column(ApiRequestType::cases(), 'value')),
            'reference_id' => 'nullable|string|max:50',
            'user_data' => 'nullable|array',
            'request_data' => 'nullable|array',
        ]);



        $apiRequest = ApiRequest::create($validated);

        return redirect()
            ->route('admin.api-requests.show', $apiRequest)
            ->with('success', 'درخواست API با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified API request.
     */
    public function show(ApiRequest $apiRequest): View
    {
        $apiRequest->load('system');

        return view('dashboard.api-system.requests.show', compact('apiRequest'));
    }

    /**
     * Show the form for editing the specified API request.
     */
    public function edit(ApiRequest $apiRequest): View
    {
        $systems = System::where('is_active', true)->get();
        $requestTypes = ApiRequestType::getOptions();

        return view('dashboard.api-system.requests.edit', compact('apiRequest', 'systems', 'requestTypes'));
    }

    /**
     * Update the specified API request.
     */
    public function update(Request $request, ApiRequest $apiRequest): RedirectResponse
    {
        $validated = $request->validate([
            'system_id' => 'required|exists:api_system_db.systems,id',
            'user_id' => 'required|integer|min:1',
            'type' => 'required|in:' . implode(',', array_column(ApiRequestType::cases(), 'value')),
            'reference_id' => 'nullable|string|max:50',
            'status' => 'required|in:pending,processing,completed,failed',
            'failure_reason' => 'nullable|string',
            'user_data' => 'nullable|array',
            'request_data' => 'nullable|array',
            'response_data' => 'nullable|array',
        ]);

        // Update processed_at if status changed to processing, completed, or failed
        if (
            in_array($validated['status'], ['processing', 'completed', 'failed']) &&
            $apiRequest->status === 'pending'
        ) {
            $validated['processed_at'] = now();
        }

        $apiRequest->update($validated);

        return redirect()
            ->route('admin.api-requests.show', $apiRequest)
            ->with('success', 'درخواست API با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified API request.
     */
    public function destroy(ApiRequest $apiRequest): RedirectResponse
    {
        // Only allow deletion of failed or completed requests
        if (!in_array($apiRequest->status, ['completed', 'failed'])) {
            return redirect()
                ->back()
                ->with('error', 'فقط درخواست‌های تکمیل شده یا ناموفق قابل حذف هستند.');
        }

        $apiRequest->delete();

        return redirect()
            ->route('admin.api-requests.index')
            ->with('success', 'درخواست API با موفقیت حذف شد.');
    }

    /**
     * Process a pending request
     */
    public function process(ApiRequest $apiRequest): JsonResponse
    {
        if ($apiRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'فقط درخواست‌های در انتظار قابل پردازش هستند.'
            ], 400);
        }

        $apiRequest->markAsProcessing();

        // Here you would implement the actual processing logic
        // For now, we'll just simulate processing

        return response()->json([
            'success' => true,
            'message' => 'درخواست با موفقیت به حالت پردازش تغییر یافت.'
        ]);
    }

    /**
     * Mark request as completed
     */
    public function complete(Request $request, ApiRequest $apiRequest): JsonResponse
    {
        if (!in_array($apiRequest->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'فقط درخواست‌های در انتظار یا در حال پردازش قابل تکمیل هستند.'
            ], 400);
        }

        $responseData = $request->get('response_data', []);
        $apiRequest->markAsCompleted($responseData);

        return response()->json([
            'success' => true,
            'message' => 'درخواست با موفقیت تکمیل شد.'
        ]);
    }

    /**
     * Mark request as failed
     */
    public function fail(Request $request, ApiRequest $apiRequest): JsonResponse
    {
        if (!in_array($apiRequest->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'فقط درخواست‌های در انتظار یا در حال پردازش قابل ناموفق کردن هستند.'
            ], 400);
        }

        $validated = $request->validate([
            'failure_reason' => 'required|string|max:1000',
            'response_data' => 'nullable|array',
        ]);

        $apiRequest->markAsFailed(
            $validated['failure_reason'],
            $validated['response_data'] ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'درخواست با موفقیت به عنوان ناموفق علامت‌گذاری شد.'
        ]);
    }

    /**
     * Get statistics for API requests
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => ApiRequest::count(),
            'pending' => ApiRequest::where('status', 'pending')->count(),
            'processing' => ApiRequest::where('status', 'processing')->count(),
            'completed' => ApiRequest::where('status', 'completed')->count(),
            'failed' => ApiRequest::where('status', 'failed')->count(),
            'today' => ApiRequest::whereDate('created_at', today())->count(),
            'this_week' => ApiRequest::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month' => ApiRequest::whereMonth('created_at', now()->month)->count(),
        ];

        // Statistics by type
        $typeStats = [];
        foreach (ApiRequestType::cases() as $type) {
            $typeStats[$type->value] = [
                'name' => $type->getDisplayName(),
                'count' => ApiRequest::where('type', $type)->count(),
                'pending' => ApiRequest::where('type', $type)->where('status', 'pending')->count(),
                'completed' => ApiRequest::where('type', $type)->where('status', 'completed')->count(),
            ];
        }

        return response()->json([
            'general' => $stats,
            'by_type' => $typeStats,
        ]);
    }
}
