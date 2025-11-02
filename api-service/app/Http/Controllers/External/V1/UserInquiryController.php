<?php

namespace App\Http\Controllers\External\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UserInquiryController extends Controller
{
    /**
     * Check if user exists in the system.
     */
    public function checkUserExists(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|max:255',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'exists' => false,
                        'email' => $validated['email'],
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'exists' => true,
                    'email' => $user->email,
                    'username' => $user->username,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking user existence',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Check multiple users existence in the system.
     */
    public function checkMultipleUsersExist(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'emails' => 'required|array|min:1|max:100',
                'emails.*' => 'email|max:255',
            ]);

            $emails = $validated['emails'];
            $users = User::whereIn('email', $emails)->get()->keyBy('email');

            $results = [];
            foreach ($emails as $email) {
                $user = $users->get($email);

                if ($user) {
                    $results[] = [
                        'email' => $email,
                        'username' => $user->username,
                        'exists' => true,
                        'user_id' => $user->id,
                    ];
                } else {
                    $results[] = [
                        'email' => $email,
                        'username' => null,
                        'exists' => false,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_requested' => count($emails),
                    'found_count' => $users->count(),
                    'not_found_count' => count($emails) - $users->count(),
                    'results' => $results,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking users existence',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
