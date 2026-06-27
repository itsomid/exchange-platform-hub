<?php

namespace App\Http\Middleware;

use App\Enums\ApiRequestType;
use App\Models\ApiSystem\System;
use App\Models\ApiSystem\SystemToken;
use App\Models\ApiSystem\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiSystemAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Extract token from Authorization header
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->unauthorizedResponse('Missing API token');
        }
        // Find and validate the token
        $systemToken = SystemToken::where('token', $token)
            ->active()
            ->notExpired()
            ->with('system')
            ->first();

        if (!$systemToken) {
            return $this->unauthorizedResponse('Invalid or expired API token');
        }

        $system = $systemToken->system;

        // Check if API system is active
        if (!$system->is_active) {
            return $this->unauthorizedResponse('System is inactive');
        }

        // Check IP restrictions
        if (!$system->isIpAllowed($request->ip())) {
            return $this->unauthorizedResponse('IP address not allowed');
        }

        // Check permissions for the requested endpoint
        $endpoint = $request->path();
        $method = $request->method();

        if (!$this->hasEndpointPermission($system, $systemToken, $endpoint, $method)) {
            return $this->forbiddenResponse('Insufficient permissions for this endpoint');
        }

        // Update last used timestamps
        $systemToken->updateLastUsed();
        $system->updateLastUsed();

        // Add API system and token to request for use in controllers
        $request->merge([
            'system' => $system,
            'system_token' => $systemToken,
        ]);

        // Process the request
        $response = $next($request);

        // Log the request
        $this->logRequest($request, $response, $system, $startTime);

        return $response;
    }

    /**
     * Extract token from Authorization header.
     */
    private function extractToken(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7);
    }

    /**
     * Check if the system has permission for the endpoint.
     */
    private function hasEndpointPermission(System $system, SystemToken $systemToken, string $endpoint, string $method): bool
    {
        // Define endpoint permission mappings
        $endpointPermissions = [
            'api/external/v1/users/balance' => [ApiRequestType::USER_BALANCE->value],
            'api/external/v1/users/balances' => [ApiRequestType::USER_BALANCE->value],
            'api/external/v1/users/credit/increase' => [ApiRequestType::USER_CREDIT_INCREASE->value],
            'api/external/v1/users/inquiry' => [ApiRequestType::USER_INQUIRY->value],
            'api/external/v1/users/inquiries' => [ApiRequestType::USER_INQUIRY->value],
            'api/external/v1/users/credit/transactions' => [ApiRequestType::TRANSACTION_HISTORY->value],
            'api/external/v1/stocks/purchase' => [ApiRequestType::STOCK_PURCHASE->value],
            'api/external/v1/stocks/purchased/transactions' => [ApiRequestType::STOCK_PURCHASE->value],
            'api/external/v1/tracking/generate' => [ApiRequestType::USER_CREDIT_INCREASE->value],
            'api/external/v1/tracking/requests' => [ApiRequestType::USER_CREDIT_INCREASE->value],
        ];

        // Log debug information
        // \Log::debug('Permission check', [
        //     'endpoint' => $endpoint,
        //     'method' => $method,
        //     'system_permissions' => $system->permissions,
        //     'token_scopes' => $systemToken->scopes,
        // ]);

        // Check if endpoint requires specific permissions
        foreach ($endpointPermissions as $pattern => $requiredPermissions) {
            if (str_contains($endpoint, $pattern)) {
                // \Log::debug('Endpoint matched pattern', [
                //     'pattern' => $pattern,
                //     'required_permissions' => $requiredPermissions,
                // ]);

                // Check token scopes only (simplified authentication)
                foreach ($requiredPermissions as $permission) {
                    if (!$systemToken->hasScope($permission)) {
                        \Log::debug('Token missing scope', [
                            'permission' => $permission,
                            'token_scopes' => $systemToken->scopes,
                        ]);
                        return false;
                    }
                }

          
                return true;
            }
        }

        \Log::debug('Endpoint not found in mappings', ['endpoint' => $endpoint]);
        // If endpoint not found in mappings, deny access
        return false;
    }

    /**
     * Log the API request.
     */
    private function logRequest(Request $request, Response $response, System $system, float $startTime): void
    {
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        RequestLog::createLog(
            systemId: $system->id,
            endpoint: $request->path(),
            method: $request->method(),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            requestHeaders: $request->headers->all(),
            requestBody: $request->getContent(),
            responseStatus: $response->getStatusCode(),
            responseBody: $response->getContent(),
            responseTimeMs: (int) round($responseTime)
        );
    }

    /**
     * Return unauthorized response.
     */
    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $message,
        ], 401);
    }

    /**
     * Return forbidden response.
     */
    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'Forbidden',
            'message' => $message,
        ], 403);
    }
}
