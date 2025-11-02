<?php

namespace App\Http\Controllers\External\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Currency;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UserBalanceController extends Controller
{
    /**
     * Get user balance for a specific currency.
     */
    public function getUserBalance(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|max:255',
                'currency_symbol' => 'required|string|exists:currencies,symbol',
            ]);

            $user = User::where('email', $validated['email'])->first();
            $currency = Currency::where('symbol', $validated['currency_symbol'])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if (!$currency) {
                return response()->json([
                    'success' => false,
                    'message' => 'Currency not found',
                ], 404);
            }

            // Get user wallet for the specified currency
            $wallet = Wallet::where('user_id', $user->id)
                ->where('currency_symbol', $currency->symbol)
                ->first();

            $balance = $wallet ? $wallet->balance : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $user->email,
                    'currency_symbol' => $currency->symbol,
                    'balance' => $balance,
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
                'message' => 'An error occurred while retrieving user balance',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get user balances for multiple currencies.
     */
    public function getUserBalances(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|max:255',
                'currency_symbols' => 'array',
                'currency_symbols.*' => 'string|exists:currencies,symbol',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Get currencies to query
            $currencyQuery = Currency::query();
            if (isset($validated['currency_symbols'])) {
                $currencyQuery->whereIn('symbol', $validated['currency_symbols']);
            }
            $currencies = $currencyQuery->get();
            // Get user wallets
            $wallets = Wallet::where('user_id', $user->id)
                ->whereIn('currency_symbol', $currencies->pluck('symbol'))
                ->get()
                ->keyBy('currency_symbol');

            $balances = [];

            foreach ($currencies as $currency) {

                $wallet = $wallets->get($currency->symbol);
                $balance = $wallet ? $wallet->balance : 0;

                $balances[] = [
                    'currency_symbol' => $currency->symbol,
                    'currency_name' => $currency->name,
                    'balance' => $balance,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $user->email,
                    'balances' => $balances,
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
                'message' => 'An error occurred while retrieving user balances',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
