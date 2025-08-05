<?php

namespace App\Http\Controllers\SpotBot;

/**
 * SpotBot API Documentation
 *
 * @OA\Info(
 *     title="Bot API Documentation",
 *     version="1.0.0",
 *     description="API documentation for Bot services",
 *     @OA\Contact(
 *         email="support@exchange.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api/bot/v1",
 *     description="Bot API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="BearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT token in format: Bearer {token}"
 * )
 *
 * @OA\Schema(
 *     schema="BotSetting",
 *     type="object",
 *     @OA\Property(property="id", type="integer", description="Setting ID", example=1),
 *     @OA\Property(property="currency_id", type="integer", description="Currency ID", example=1),
 *     @OA\Property(property="currency_symbol", type="string", description="Currency symbol", example="BTC"),
 *     @OA\Property(property="is_active", type="boolean", description="Whether bot is active", example=true),
 *     @OA\Property(property="price_interval_seconds", type="integer", description="Price update interval in seconds", example=60),
 *     @OA\Property(property="order_margin", type="string", description="Order margin percentage", example="0.5"),
 *     @OA\Property(property="buy_orders_count", type="integer", description="Number of buy orders", example=10),
 *     @OA\Property(property="sell_orders_count", type="integer", description="Number of sell orders", example=10),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation completed successfully")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="An error occurred")
 * )
 *
 * @OA\Schema(
 *     schema="UnauthorizedResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Unauthorized")
 * )
 *
 * @OA\Schema(
 *     schema="BulkOperationResult",
 *     type="object",
 *     @OA\Property(property="currency_id", type="integer", description="Currency ID", example=1),
 *     @OA\Property(property="currency_name", type="string", description="Currency name", example="Bitcoin"),
 *     @OA\Property(property="status", type="string", description="Operation status", example="completed")
 * )
 */
class SpotBotDocumentation
{

    /**
     * @OA\Post(
     *     path="/token/generate",
     *     summary="Generate JWT token for bot authentication",
     *     description="Generate a JWT token with the provided payload for bot or accounting services",
     *     operationId="generateToken",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"secret_key", "payload"},
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 enum={"accounting", "bot"},
     *                 default="bot",
     *                 description="Type of service requesting the token"
     *             ),
     *             @OA\Property(
     *                 property="secret_key",
     *                 type="string",
     *                 description="Secret key for authentication"
     *             ),
     *             @OA\Property(
     *                 property="payload",
     *                 type="object",
     *                 required={"email"},
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     format="email",
     *                     description="Email address for the token"
     *                 ),
     *                 description="Additional payload data for the token"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", description="Generated JWT token"),
     *             @OA\Property(property="expires_at", type="string", format="date-time", description="Token expiration date"),
     *             @OA\Property(property="expiry_days", type="integer", description="Number of days until expiration")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid secret key",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid secret key")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", description="Validation errors")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred"),
     *             @OA\Property(property="error", type="string", description="Error details")
     *         )
     *     )
     * )
     */
    public function generateTokenDocumentation() {}
    /**
     * @OA\Get(
     *     path="/status/{currency_id}",
     *     summary="Get bot status for specific currency",
     *     description="Retrieve the current status and settings of the spot bot for a specific currency",
     *     operationId="getBotStatus",
     *     tags={"Spot Bot Status"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="currency_id",
     *         in="path",
     *         required=true,
     *         description="Currency ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bot status retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/components/schemas/BotSetting"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Currency or bot settings not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function getBotStatusDocumentation() {}

    /**
     * @OA\Get(
     *     path="/settings",
     *     summary="Get all bot settings",
     *     description="Retrieve all bot settings for all currencies",
     *     operationId="getAllBotSettings",
     *     tags={"Spot Bot Settings"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Bot settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/BotSetting")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function getAllBotSettingsDocumentation() {}

    /**
     * @OA\Get(
     *     path="/settings/{currency_id}",
     *     summary="Get bot settings for specific currency",
     *     description="Retrieve bot settings for a specific currency",
     *     operationId="getBotSettings",
     *     tags={"Spot Bot Settings"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="currency_id",
     *         in="path",
     *         required=true,
     *         description="Currency ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bot settings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/components/schemas/BotSetting"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Currency or bot settings not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function getBotSettingsDocumentation() {}

    /**
     * @OA\Post(
     *     path="/sync-orders/{currency_id}",
     *     summary="Sync orders for specific currency",
     *     description="Synchronize bot orders for a specific currency",
     *     operationId="syncOrdersForCurrency",
     *     tags={"Spot Bot Operations"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="currency_id",
     *         in="path",
     *         required=true,
     *         description="Currency ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orders synchronized successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bot is not active for this currency",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Currency not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function syncOrdersDocumentation() {}

    /**
     * @OA\Post(
     *     path="/cancel-orders/{currency_id}",
     *     summary="Cancel orders for specific currency",
     *     description="Cancel bot orders for a specific currency",
     *     operationId="cancelOrdersForCurrency",
     *     tags={"Spot Bot Operations"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="currency_id",
     *         in="path",
     *         required=true,
     *         description="Currency ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orders cancelled successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bot is not active for this currency",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Currency not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function cancelOldOrdersDocumentation() {}

    /**
     * @OA\Post(
     *     path="/generate-orders/{currency_id}",
     *     summary="Generate orders for specific currency",
     *     description="Generate new bot orders for a specific currency. You can optionally specify the number of buy and sell orders to generate. If not provided, the values from bot settings will be used.",
     *     operationId="generateOrdersForCurrency",
     *     tags={"Spot Bot Operations"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="currency_id",
     *         in="path",
     *         required=true,
     *         description="Currency ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         description="Optional parameters to override bot settings",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="buy_orders_count",
     *                 type="integer",
     *                 description="Number of buy orders to generate (optional, defaults to bot setting)",
     *                 example=5,
     *                 minimum=0
     *             ),
     *             @OA\Property(
     *                 property="sell_orders_count",
     *                 type="integer",
     *                 description="Number of sell orders to generate (optional, defaults to bot setting)",
     *                 example=5,
     *                 minimum=0
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orders generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Orders generated successfully for Bitcoin"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="currency", type="string", example="BTC"),
     *                 @OA\Property(property="market_id", type="integer", example=1),
     *                 @OA\Property(property="current_price", type="string", example="45000.00000000"),
     *                 @OA\Property(property="total_orders_generated", type="integer", example=10),
     *                 @OA\Property(property="buy_orders_count", type="integer", example=5),
     *                 @OA\Property(property="sell_orders_count", type="integer", example=5),
     *                 @OA\Property(property="order_margin", type="string", example="0.5"),
     *                 @OA\Property(
     *                     property="orders",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="side", type="string", enum={"buy", "sell"}, example="buy"),
     *                         @OA\Property(property="price", type="string", example="44775.00000000"),
     *                         @OA\Property(property="quantity", type="string", example="0.05000000"),
     *                         @OA\Property(property="order_id", type="integer", example=123)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="errors",
     *                     type="array",
     *                     @OA\Items(type="string"),
     *                     example={}
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bot is not active for this currency or market not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Currency not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error during order generation",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function generateOrdersDocumentation() {}

    /**
     * @OA\Post(
     *     path="/match-order/{currency_id}",
     *     summary="Match order for specific currency",
     *     description="Match bot orders for a specific currency",
     *     operationId="matchOrderForCurrency",
     *     tags={"Spot Bot Operations"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="currency_id",
     *         in="path",
     *         required=true,
     *         description="Currency ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order matched successfully",
     *         @OA\JsonContent(ref="#/components/schemas/SuccessResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bot is not active for this currency",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Currency not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function matchOrderDocumentation() {}

    /**
     * @OA\Post(
     *     path="/sync-all-orders",
     *     summary="Sync orders for all active currencies",
     *     description="Synchronize bot orders for all active currencies",
     *     operationId="syncAllOrders",
     *     tags={"Spot Bot Bulk Operations"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Orders synchronized successfully for all active currencies",
     *         @OA\JsonContent(ref="#/components/schemas/BulkOperationResult")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No active bot settings found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function syncAllOrdersDocumentation() {}

    /**
     * @OA\Post(
     *     path="/cancel-all-orders",
     *     summary="Cancel all orders for all active currencies",
     *     description="Cancel all bot orders for all active currencies",
     *     operationId="cancelAllOrders",
     *     tags={"Spot Bot Bulk Operations"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All orders cancelled successfully for all active currencies",
     *         @OA\JsonContent(ref="#/components/schemas/BulkOperationResult")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No active bot settings found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function cancelAllOldOrdersDocumentation() {}

    /**
     * @OA\Post(
     *     path="/generate-all-orders",
     *     summary="Generate orders for all active currencies",
     *     description="Generate new bot orders for all active currencies",
     *     operationId="generateAllOrders",
     *     tags={"Spot Bot Bulk Operations"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Orders generated successfully for all active currencies",
     *         @OA\JsonContent(ref="#/components/schemas/BulkOperationResult")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No active bot settings found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedResponse")
     *     )
     * )
     */
    public function generateAllOrdersDocumentation() {}
}
