<?php

namespace App\Http\Controllers\Admin\Bot;

use App\Http\Controllers\Controller;
use App\Services\Bot\BotTestApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * UI + AJAX proxy for the Bot Test Lab. All real work happens on the
 * api-service side; this controller just shapes requests and re-emits
 * responses to the browser.
 */
class BotTestLabController extends Controller
{
    public function __construct(private readonly BotTestApiClient $api)
    {
    }

    public function index(): View
    {
        $userId = (int) config('smart-bot.test_user_id', 2);
        return view('dashboard.bot.test-lab.index', ['userId' => $userId]);
    }

    public function status(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id', config('smart-bot.test_user_id', 2));

        return $this->forward($this->api->status($userId));
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'      => 'required|integer',
            'capital_usdt' => 'required|numeric|min:1',
            'main_balance' => 'nullable|numeric|min:0',
        ]);
        return $this->forward($this->api->start(
            (int) $data['user_id'],
            (float) $data['capital_usdt'],
            isset($data['main_balance']) ? (float) $data['main_balance'] : null,
        ));
    }

    public function bumpPrice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'symbol'    => 'required|string',
            'direction' => 'required|in:up,down',
            'step'      => 'required|numeric|min:0.0001',
            'mode'      => 'nullable|in:percent,absolute',
        ]);
        return $this->forward($this->api->bumpPrice(
            $data['symbol'],
            $data['direction'],
            (float) $data['step'],
            $data['mode'] ?? 'percent',
        ));
    }

    public function setPrice(Request $request): JsonResponse
    {
        $data = $request->validate(['symbol' => 'required|string', 'price' => 'required|numeric|min:0.00000001']);
        return $this->forward($this->api->setPrice($data['symbol'], (float) $data['price']));
    }

    public function sync(): JsonResponse
    {
        return $this->forward($this->api->sync());
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'      => 'required|integer',
            'main_balance' => 'nullable|numeric|min:0',
        ]);
        return $this->forward($this->api->reset(
            (int) $data['user_id'],
            isset($data['main_balance']) ? (float) $data['main_balance'] : null,
        ));
    }

    private function forward(\Illuminate\Http\Client\Response $response): JsonResponse
    {
        $payload = $response->json();
        if ($payload === null) {
            return response()->json([
                'ok'    => false,
                'error' => 'Empty/invalid response from api-service.',
                'body'  => (string) $response->body(),
            ], $response->status() ?: 502);
        }
        return response()->json($payload, $response->status());
    }
}
