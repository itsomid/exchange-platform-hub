<?php

namespace App\Http\Middleware;

use App\Enums\FinancialBlockActionEnum;
use App\Services\User\FinancialBlockService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinancialWithdrawalBlockMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $service = resolve(FinancialBlockService::class);

        $blockState = $service->getUserBlockedState(auth()->id(), FinancialBlockActionEnum::WITHDRAW);
        if ($blockState->isBlock()) {
            return response()->json(['message' => __('messages.user_financial_block.withdrawal', ['date' => $blockState->getRestrictUntil()->diffForHumans()])], 403);
        }

        return $next($request);
    }
}
