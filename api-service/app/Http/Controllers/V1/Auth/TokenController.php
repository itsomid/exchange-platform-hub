<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Helpers\JWT;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    /**
     * Generate a JWT token with the provided payload
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request)
    {
        try {
            // Validate the request payload
            $validated = $request->validate([
                'type' => 'nullable|string|in:accounting,bot',
                'secret_key' => 'required|string',
                'payload' => 'required|array',
                'payload.email' => 'required|email',
            ]);

            // Set default type to accounting if not provided
            $validated['type'] = $validated['type'] ?? 'accounting';

            // Verify secret key
            $validSecretKey = config($validated['type'] . '.auth.secret_key');
            if ($validated['secret_key'] !== $validSecretKey) {
                return response()->json([
                    'message' => 'Invalid secret key'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Calculate expiration timestamp (default: 30 days)
            $expiryDays = config($validated['type'] . '.auth.expiry_days');
            $exp = now()->addDays($expiryDays)->timestamp;

            // Prepare the final payload
            $payload = array_merge($validated['payload'], [
                'exp' => $exp,
                'iat' => now()->timestamp // Issued at time
            ]);

            // Generate the JWT token
            $token = JWT::new()
                ->payload($payload)
                ->encode();

            return response()->json([
                'token' => $token,
                'expires_at' => date('Y-m-d H:i:s', $exp),
                'expiry_days' => $expiryDays
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY); // 422
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500
        }
    }
}
