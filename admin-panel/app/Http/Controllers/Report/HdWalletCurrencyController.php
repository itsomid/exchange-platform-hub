<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\CurrencyChain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HdWalletCurrencyController extends Controller
{
    private const TOKEN_NETWORKS = ['ethereum', 'bnb', 'tron', 'polygon', 'arbitrum', 'optimism', 'avalanche'];

    public function index()
    {
         $currencyChains = CurrencyChain::with('currency')
            ->whereNotNull('chain')
            ->whereHas('currency')
            ->get()
            ->sortBy(function ($chain) {
                return $chain->currency->symbol . '-' . $chain->chain_name;
            })
            ->values();

        $sweeperStatus = $this->fetchCurrencies('sweeper');
        $serviceStatus = $this->fetchCurrencies('service_new');
        $wallets = $this->fetchWallets();

        $sweeperMap = $this->buildCurrencyMap($sweeperStatus['currencies']);
        $serviceMap = $this->buildCurrencyMap($serviceStatus['currencies']);

        $items = $currencyChains->map(function ($chain) use ($sweeperMap, $serviceMap) {
            $currency = $chain->currency;
            $chainValue = $chain->chain?->value ?? (string) $chain->chain;
            $network = $this->mapChainToNetwork($chainValue);
            $symbol = strtoupper($currency->symbol ?? '');
            $key = $network ? $symbol . '|' . $network : null;
            $contractAddress = $chain->contract_address ?? '';
            $displayName = ($currency->name ?: $currency->symbol) . ' (' . ($chain->chain_name ?: $chainValue) . ')';
            $currencyName = Str::lower($symbol) . '-' . ($network ?: Str::lower($chainValue));
            $decimals = $currency->amount_precision ?? $chain->withdrawal_precision ?? 18;
            $createAllowed = $network && in_array($network, self::TOKEN_NETWORKS, true) && !empty($contractAddress);

            $serviceCurrency = $key && isset($serviceMap[$key]) ? $serviceMap[$key] : null;
            $sweeperCurrency = $key && isset($sweeperMap[$key]) ? $sweeperMap[$key] : null;

            return [
                'id' => $chain->id,
                'currency_name' => $currencyName,
                'display_name' => $displayName,
                'persian_name' => $currency->persian_name,
                'symbol' => $symbol,
                'network' => $network,
                'network_label' => $chain->chain_name ?: $chainValue,
                'chain' => $chainValue,
                'contract_address' => $contractAddress,
                'decimals' => $decimals,
                'description' => $displayName,
                'logo_url' => $currency->coinLogo(),
                'exists_sweeper' => $key && isset($sweeperMap[$key]),
                'exists_service' => $key && isset($serviceMap[$key]),
                'service_identifier' => $serviceCurrency['currencyName'] ?? ($serviceCurrency['_id'] ?? null),
                'sweeper_identifier' => $sweeperCurrency['currencyName']
                    ?? ($sweeperCurrency['_id'] ?? $currencyName),
                'service_payload' => $serviceCurrency,
                'sweeper_payload' => $sweeperCurrency,
                'create_allowed' => $createAllowed,
            ];
        });
        
        return view('dashboard.hd-wallet.currencies.index', [
            'items' => $items,
            'sweeperStatus' => $sweeperStatus,
            'serviceStatus' => $serviceStatus,
            'wallets' => $wallets,
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'target' => 'required|in:sweeper,service_new',
            'currency_name' => 'required|string|max:80',
            'display_name' => 'required|string|max:120',
            'symbol' => 'required|string|max:20',
            'network' => 'required|string|max:20',
            'contract_address' => 'required|string|max:255',
            'decimals' => 'nullable|integer|min:0|max:30',
            'description' => 'nullable|string|max:255',
        ]);

        $baseUrl = $validated['target'] === 'sweeper'
            ? config('sweeper.base_url')
            : config('hd-wallet.new_base_url');

        $client = Http::timeout(15)->acceptJson();

        $configKey = $validated['target'] === 'sweeper' ? 'sweeper.api_key' : 'hd-wallet.api_key';
        $serviceName = $validated['target'] === 'sweeper' ? 'sweeper' : 'HD Wallet Service';

        $apiKey = config($configKey);
        if (!$apiKey) {
            return back()->withErrors(['api' => "کلید API برای {$serviceName} تنظیم نشده است."]);
        }

        $client = $client->withHeaders(['x-api-key' => $apiKey]);

        $payload = [
            'currencyName' => $validated['currency_name'],
            'displayName' => $validated['display_name'],
            'symbol' => strtoupper($validated['symbol']),
            'network' => strtolower($validated['network']),
            'contractAddress' => $validated['contract_address'],
            'decimals' => $validated['decimals'] ?? 18,
            'description' => $validated['description'] ?? '',
            'metadata' => [
                'isVerified' => true,
            ],
        ];

        try {
            $response = $client->post(rtrim($baseUrl, '/') . '/api/currencies', $payload);
        } catch (\Throwable $exception) {
            return back()->withErrors(['api' => 'خطا در ارتباط با سرویس: ' . $exception->getMessage()]);
        }

        if (! $response->successful()) {
            $message = $response->json('error.message')
                ?? $response->json('error')
                ?? $response->json('message')
                ?? $response->body();

            return back()->withErrors(['api' => 'خطا در ایجاد ارز: ' . $message]);
        }

        return back()->with('success', 'ارز با موفقیت ایجاد شد.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'target' => 'required|in:sweeper,service_new',
            'identifier' => 'required|string|max:120',
            'display_name' => 'required|string|max:120',
            'symbol' => 'required|string|max:20',
            'contract_address' => 'nullable|string|max:255',
            'decimals' => 'nullable|integer|min:0|max:30',
            'confirmations' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'assigned_wallet_id' => 'nullable|string|max:50',
        ]);

        $baseUrl = $validated['target'] === 'sweeper'
            ? config('sweeper.base_url')
            : config('hd-wallet.new_base_url');

        $client = Http::timeout(15)->acceptJson();

        $configKey = $validated['target'] === 'sweeper' ? 'sweeper.api_key' : 'hd-wallet.api_key';
        $serviceName = $validated['target'] === 'sweeper' ? 'sweeper' : 'HD Wallet Service';

        $apiKey = config($configKey);
        if (!$apiKey) {
            return back()->withErrors(['api' => "کلید API برای {$serviceName} تنظیم نشده است."]);
        }

        $client = $client->withHeaders(['x-api-key' => $apiKey]);

        $payload = [
            'displayName' => $validated['display_name'],
            'symbol' => strtoupper($validated['symbol']),
            'contractAddress' => $validated['contract_address'] ?? null,
            'decimals' => $validated['decimals'],
            'confirmations' => $validated['confirmations'] ?? null,
            'description' => $validated['description'] ?? '',
            'isActive' => $request->boolean('is_active'),
        ];

        if ($validated['target'] === 'service_new') {
            $payload['assignedWalletId'] = $validated['assigned_wallet_id'] ?: null;
        }

        try {
            $response = $client->put(
                rtrim($baseUrl, '/') . '/api/currencies/' . $validated['identifier'],
                $payload
            );
        } catch (\Throwable $exception) {
            return back()->withErrors(['api' => 'خطا در ارتباط با سرویس: ' . $exception->getMessage()]);
        }

        if (! $response->successful()) {
            $message = $response->json('error.message')
                ?? $response->json('error')
                ?? $response->json('message')
                ?? $response->body();

            return back()->withErrors(['api' => 'خطا در ویرایش ارز: ' . $message]);
        }

        return back()->with('success', 'ارز با موفقیت ویرایش شد.');
    }

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'target' => 'required|in:sweeper,service_new',
            'identifier' => 'required|string|max:120',
        ]);

        $baseUrl = $validated['target'] === 'sweeper'
            ? config('sweeper.base_url')
            : config('hd-wallet.new_base_url');

        $client = Http::timeout(15)->acceptJson();

        $configKey = $validated['target'] === 'sweeper' ? 'sweeper.api_key' : 'hd-wallet.api_key';
        $serviceName = $validated['target'] === 'sweeper' ? 'sweeper' : 'HD Wallet Service';

        $apiKey = config($configKey);
        if (!$apiKey) {
            return back()->withErrors(['api' => "کلید API برای {$serviceName} تنظیم نشده است."]);
        }

        $client = $client->withHeaders(['x-api-key' => $apiKey]);

        try {
            $response = $client->delete(
                rtrim($baseUrl, '/') . '/api/currencies/' . $validated['identifier']
            );
        } catch (\Throwable $exception) {
            return back()->withErrors(['api' => 'خطا در ارتباط با سرویس: ' . $exception->getMessage()]);
        }

        if (! $response->successful()) {
            $message = $response->json('error.message')
                ?? $response->json('error')
                ?? $response->json('message')
                ?? $response->body();

            return back()->withErrors(['api' => 'خطا در غیرفعال‌سازی ارز: ' . $message]);
        }

        return back()->with('success', 'ارز با موفقیت غیرفعال شد.');
    }

    private function fetchCurrencies(string $target): array
    {
        $baseUrl = $target === 'sweeper'
            ? config('sweeper.base_url')
            : config('hd-wallet.new_base_url');

        $client = Http::timeout(15)->acceptJson();

        if ($target === 'sweeper') {
            $apiKey = config('sweeper.api_key');
            if ($apiKey) {
                $client = $client->withHeaders(['x-api-key' => $apiKey]);
            }
        } else {
            $apiKey = config('hd-wallet.api_key');
            if ($apiKey) {
                $client = $client->withHeaders(['x-api-key' => $apiKey]);
            }
        }

        try {
            $response = $client->get(rtrim($baseUrl, '/') . '/api/currencies', [
                'isActive' => 'all',
                'limit'    => 1000,
            ]);
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'currencies' => [],
                'error' => $exception->getMessage(),
            ];
        }

        if (! $response->successful()) {
            return [
                'success' => false,
                'currencies' => [],
                'error' => $response->body(),
            ];
        }

        return [
            'success' => true,
            'currencies' => $response->json('data.currencies') ?? [],
            'error' => null,
        ];
    }

    private function buildCurrencyMap(array $currencies): array
    {
        $map = [];
        foreach ($currencies as $currency) {
            $symbol = strtoupper($currency['symbol'] ?? '');
            $network = strtolower($currency['network'] ?? '');
            if (! $symbol || ! $network) {
                continue;
            }
            $map[$symbol . '|' . $network] = $currency;
        }
        return $map;
    }

    private function mapChainToNetwork(?string $chain): ?string
    {
        return match ($chain) {
            'ERC20' => 'ethereum',
            'TRC20' => 'tron',
            'BSC' => 'bnb',
            'BTC' => 'bitcoin',
            'DOGE' => 'dogecoin',
            'POLYGON' => 'polygon',
            'ARBITRUM' => 'arbitrum',
            'OPTIMISM' => 'optimism',
            'AVALANCHE' => 'avalanche',
            'AVAX' => 'avalanche',
            default => null,
        };
    }

    private function fetchWallets(): array
    {
        $baseUrl = config('hd-wallet.new_base_url');
        $apiKey = config('hd-wallet.api_key');

        $client = Http::timeout(15)->acceptJson();
        if ($apiKey) {
            $client = $client->withHeaders(['x-api-key' => $apiKey]);
        }

        try {
            $response = $client->get(rtrim($baseUrl, '/') . '/api/currencies/wallets');
        } catch (\Throwable $exception) {
            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        return $response->json('data.wallets') ?? [];
    }
}
