<?php

namespace App\Http\Controllers\External\V1;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\ApiRequestRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class TrackingController extends Controller
{
    public function __construct(
        private ApiRequestRepositoryInterface $apiRequestRepository
    ) {}

    /**
     * Generate a new tracking code for API requests
     */
    public function generateTrackingCode(Request $request): JsonResponse
    {
        try {
            // Generate unique tracking code
            $trackingCode = $this->generateUniqueTrackingCode();


            return response()->json([
                'success' => true,
                'message' => 'کد رهگیری با موفقیت تولید شد',
                'data' => [
                    'tracking_code' => $trackingCode,
                    'expires_at' => now()->addDays(30)->toISOString(), // 30 days validity
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('Error generating tracking code', [
                'error' => $e->getMessage(),
                'system_id' => $request->system_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در تولید کد رهگیری: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get requests by tracking code
     */
    public function getRequestsByTrackingCode(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tracking_code' => 'required|string|max:100'
            ]);

            $requests = $this->apiRequestRepository->getByTrackingCode($request->tracking_code);

            if ($requests->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هیچ درخواستی با این کد رهگیری یافت نشد'
                ], 404);
            }

            // Group requests by type for better organization
            $groupedRequests = $requests->groupBy('type');

            return response()->json([
                'success' => true,
                'message' => 'درخواست‌ها با موفقیت دریافت شد',
                'data' => [
                    'tracking_code' => $request->tracking_code,
                    'total_requests' => $requests->count(),
                    'requests_by_type' => $groupedRequests->map(function ($typeRequests) {
                        return [
                            'count' => $typeRequests->count(),
                            'requests' => $typeRequests->map(function ($req) {
                                return [
                                    'id' => $req->id,
                                    'type' => $req->type,
                                    'status' => $req->status,
                                    'user_id' => $req->user_id,
                                    'created_at' => $req->created_at,
                                    'processed_at' => $req->processed_at,
                                    'request_data' => $req->request_data,
                                    'response_data' => $req->response_data,
                                    'failure_reason' => $req->failure_reason
                                ];
                            })
                        ];
                    }),
                    'requests' => $requests->map(function ($req) {
                        return [
                            'id' => $req->id,
                            'type' => $req->type,
                            'status' => $req->status,
                            'user_id' => $req->user_id,
                            'created_at' => $req->created_at,
                            'processed_at' => $req->processed_at,
                            'request_data' => $req->request_data,
                            'response_data' => $req->response_data,
                            'failure_reason' => $req->failure_reason
                        ];
                    })
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Error retrieving requests by tracking code', [
                'error' => $e->getMessage(),
                'tracking_code' => $request->tracking_code ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت درخواست‌ها'
            ], 500);
        }
    }

    /**
     * Generate a unique tracking code
     */
    private function generateUniqueTrackingCode(): string
    {
        do {
            // Generate a tracking code with format: TRK-YYYYMMDD-XXXXXX
            $trackingCode = 'TRK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while ($this->apiRequestRepository->existsByTrackingCode($trackingCode));

        return $trackingCode;
    }
}
