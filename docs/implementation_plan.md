# 🤖 Implementation Plan — Auto-Trade Bot

> این سند، نقشه راه کامل پیادهسازی سیستم «معاملات خودکار ارز دیجیتال» است.
> منابع پایه:
> - `Auto-trade_RFP.md`
> - `Auto-trade_SRS.md`
> - `فرمول_نهایی_اختصاص_سرمایه_و_وزن_دهی.md`
>
> ساختار سند: هر فاز شامل **هدف**، **خروجی**، **توضیح فارسی دقیق برای تیم**، و **Agent Prompt** آماده برای واگذاری به Coding Agent است.

---

## 📌 تصمیمات تثبیتشده (Locked Decisions)

این تصمیمات در همه فازها معتبر هستند و نباید توسط agent تغییر کنند:

| # | تصمیم |
|---|------|
| D1 | **فاز اول بدون پیادهسازی reinvest** — فقط در دیتامدل و enum ها دیده میشود (ستون/فلگ ذخیره میشود اما هیچ منطقی روی آن اجرا نمیشود). |
| D2 | **فاز اول بدون سیستم رفرال** — هیچ ستون/UI/منطق رفرال اضافه نمیشود. |
| D3 | تنها یک دکمه کنترلی در UI: **«خرید و فروش خودکار»** (مطابق حالت ب از SRS بند ۴.۲). دکمه reinvest در UI رندر میشود ولی `disabled` با tooltip «بهزودی». |
| D4 | **سقف تخصیص هر ارز**: هر سیگنال یک فیلد `max_allocation_percent` دارد. حتی اگر یک ارز تنها کاندید باشد، بیش از این درصد از کل سرمایه به آن تخصیص داده نمیشود؛ مازاد بین سایر ارزها توزیع مجدد میشود؛ اگر هیچ ارز دیگری نبود، مازاد در بالانس آزاد ربات باقی میماند. |
| D5 | **کارمزد transfer-in در لحظه واریز کسر میشود** (نه در اولین خرید). نمایش UI شفاف. |
| D6 | **کارمزد withdraw از ربات = همان فرمول transfer-in**: `20–100 → 1 USDT` ، `>100–1000 → 1%` ، `>1000 → 12 USDT`. |
| D7 | منبع `C_i` (قیمت کنونی): اولویت اول **سوکت زنده**، fallback روی جدول `exchange_prices`. |
| D8 | از جدول موجود `transactions` به عنوان ledger استفاده میشود (با enum های جدید + ستون `bot_order_id`). جدول مجزای `bot_fee_logs` ساخته **نمیشود**. |
| D9 | به جای `bot_pnl_ledger` یک جدول **snapshot** به نام `bot_trade_settlements` ساخته میشود برای گزارشهای سریع (هر ردیف = یک sell بستهشده با cost_basis، net_pnl و breakdown کارمزدها). |
| D10 | **رفتار دکمه auto-trade**: خاموش = هیچ خرید جدیدی روی هر ماندهای انجام نمیشود (اعم از واریزی جدید یا سود قبلی)، اما سفارشهای فروش باز قبلی همچنان فعالاند. روشن = هر مانده مجاز در `bot_wallets.balance` وارد چرخه خرید میشود. |
| D11 | اولویت ارزها در فاز اول بر اساس فیلد `priority` (عدد کمتر = اولویت بالاتر). |
| D12 | اجرای خرید روی صرافی مرجع (Bitexroom) — اجرای فروش روی engine P2P داخلی (مدل فعلی پروژه). |
| D13 | کارمزد عملکرد فقط زمانی کسر میشود که `net_pnl > 0`. |

---

## 🗄 ساختار ریپوها

| ریپو | نقش |
|------|-----|
| `admin-panel` | Laravel + Blade + Vue/Vuetify — پنل ادمین، CRUD سیگنالها، تنظیمات، گزارشها، migrationها (DB master) |
| `api-service` | Laravel — REST API برای فرانت کاربر |
| `bitex-frontend-app` | Vue 3 + Vuetify + Pinia — UI کاربر (onboarding، داشبورد ربات، transfer in/out) |

---

## 🔢 نقشه کلان فازها

```
P0 Discovery ─► P1 Data Model ─┬─► P2 Admin Panel ──┐
                                │                    ├─► P7 Reports ─► P8 QA & Deploy
                                ├─► P3 Wallet API ──►│
                                │                    │
                                ├─► P4 Buy Engine ──►│
                                │                    │
                                ├─► P5 Sell Engine ─►│
                                │                    │
                                └─► P6 User Frontend ┘
```

| فاز | عنوان | ریپو(ها) | پیشنیاز |
|-----|-------|---------|---------|
| P0 | Discovery & Design | — | — |
| P1 | Data Model & Migrations | admin-panel | P0 |
| P2 | Admin Panel — Settings & Signals CRUD | admin-panel | P1 |
| P3 | Bot Wallet API (transfer in/out, settings) | api-service | P1 |
| P4 | Allocation Algorithm & Buy Engine | api-service + admin-panel | P1, P3 |
| P5 | Sell Engine & Settlement | api-service | P4 |
| P6 | User Frontend | bitex-frontend-app | P3 (بقیه موازی) |
| P7 | Reports (Admin + User) | admin-panel + api-service | P5 |
| P8 | QA, Acceptance, Deploy | همه | P7 |

---

# Phase 0 — Discovery & Design

## هدف
تثبیت طراحی پیش از کدنویسی: ERD، state machine، OpenAPI، wireframe.

## خروجی
- `docs/erd.md` (یا تصویر ERD)
- `docs/state-machine.md` (BotOrder lifecycle)
- `docs/openapi.yaml` (draft)
- `docs/wireframes/*.png`

## توضیح فارسی
قبل از هر خط کد، چهار سند طراحی نوشته میشود:
1. **ERD** کامل تمام جداول جدید + روابط آنها با جداول موجود (`users`, `wallets`, `transactions`, `currencies`, `exchanges`, `exchange_prices`).
2. **State machine** سفارش ربات با حالات: `PENDING → BUYING → BOUGHT → SELLING → PARTIALLY_FILLED → FILLED → CANCELED → SETTLED` و رخدادهای گذار.
3. **OpenAPI draft** برای endpointهای فاز ۳/۴/۵.
4. **Wireframe** صفحات onboarding، داشبورد ربات، transfer in/out، order detail، cancel confirmation.

## Agent Prompt — P0

```
You are working on the exchange-platform-hub monorepo. Read these files for context:
- docs/Auto-trade_RFP.md
- docs/Auto-trade_SRS.md
- docs/فرمول_نهایی_ا��تصاص_سرمایه_و_وزن_دهی.md
- docs/implementation_plan.md (this file — Phase 0 section)

Produce the following design artifacts under `docs/`:
1. `docs/erd.md` — Mermaid ERD covering: bot_global_settings, bot_signals, bot_user_settings, bot_wallets, bot_orders, bot_buy_executions, bot_sell_orders, bot_trade_settlements + relations to existing users, wallets, currencies, transactions, exchange_prices.
2. `docs/state-machine.md` — Mermaid stateDiagram for BotOrder lifecycle with explicit transition triggers.
3. `docs/openapi.yaml` — OpenAPI 3.0 skeleton for: GET /bot/onboarding, POST /bot/accept-terms, GET /bot/wallet, POST /bot/transfer-in, POST /bot/transfer-out, GET/PATCH /bot/settings, GET /bot/orders, GET /bot/orders/{id}, POST /bot/orders/{id}/cancel-preview, POST /bot/orders/{id}/cancel.
4. `docs/wireframes/README.md` — text-based wireframes for: bot intro, bot dashboard, transfer in/out, order list, order detail, cancel dialog.

Constraints:
- DO NOT include referral fields anywhere.
- DO NOT include reinvest UI controls (only data fields).
- Include max_allocation_percent on bot_signals.
- Transfer-in fee is deducted at deposit time.
- Open a single PR titled "P0: Auto-trade design artifacts".
```

---

# Phase 1 — Data Model & Migrations

## هدف
ایجاد ساختار پایگاهداده در `admin-panel` (DB master).

## خروجی
- Migrationهای جدید
- مدلهای Eloquent
- Enumهای جدید
- یک seeder برای `bot_global_settings`
- بهروزرسانی جدول `transactions` برای پشتیبانی از تراکنشهای ربات

## جزئیات جداول

### 1. `bot_global_settings` (singleton)
```
id, min_deposit_usdt, alpha_weight (decimal 4,2 default 0.15),
default_sell_orders_count (default 3), performance_fee_percent (default 22),
transfer_fee_tiers (JSON), is_enabled (bool default true), timestamps
```

### 2. `bot_signals`
```
id, currency_id (FK currencies), priority (int, lower = higher),
floor_price decimal(18,8) [D_i], ceiling_price decimal(18,8) [E_i],
min_buy_amount_usdt decimal(18,8),
max_allocation_percent decimal(5,2) /* 0..100 — D4 */,
sell_orders_count tinyint, sell_mode enum('percent','price'),
sell_targets JSON /* [{trigger:20,share:50},{trigger:40,share:50}] */,
is_active bool, timestamps, unique(currency_id)
```

### 3. `bot_user_settings`
```
id, user_id (unique FK), auto_trade_enabled bool default true,
reinvest_enabled bool default true /* D1 — stored only */,
terms_accepted_at timestamp nullable, timestamps
```

### 4. `bot_wallets`
```
id, user_id unique, balance decimal(20,8) default 0,
principal_balance decimal(20,8) default 0 /* tracked for future reinvest split */,
profit_balance decimal(20,8) default 0   /* tracked for future reinvest split */,
locked_balance decimal(20,8) default 0   /* sum of in-flight buys + open sells */,
timestamps
```
> در فاز اول فقط `balance` و `locked_balance` در منطق استفاده میشوند. `principal_balance` و `profit_balance` فقط برای آماده‌سازی reinvest پر میشوند.

### 5. `bot_orders` (master order — یک تخصیص کامل)
```
id, user_id, batch_uuid, total_amount_usdt, alpha_snapshot,
status enum('PENDING','PARTIALLY_FILLED','FILLED','CANCELED'),
triggered_by enum('TRANSFER_IN','TOGGLE_ON','MANUAL'),
created_at, completed_at
```

### 6. `bot_buy_executions` (یک ردیف به ازای هر ارز در یک batch)
```
id, bot_order_id, currency_id, signal_snapshot JSON /* D_i, E_i, C_i, priority, weight */,
allocated_usdt, filled_amount, avg_buy_price, exchange_fee, network_fee,
exchange_transaction_id (FK exchange_transactions nullable),
status enum('PENDING','BOUGHT','FAILED'), created_at
```

### 7. `bot_sell_orders` (sub-orders تولید‌شده توسط 5.6)
```
id, bot_buy_execution_id, p2p_order_id (FK to existing P2P engine table),
target_type enum('percent','price'), target_value decimal(18,8),
share_percent decimal(5,2), amount_to_sell decimal(20,8),
status enum('OPEN','FILLED','CANCELED'), filled_at nullable, timestamps
```

### 8. `bot_trade_settlements` (snapshot per closed sell — جایگزین pnl_ledger طبق D9)
```
id, user_id, bot_buy_execution_id, bot_sell_order_id,
gross_revenue, cost_basis, network_fee, exchange_fee, spread_fee,
performance_fee, cancel_fee, net_pnl, settled_at, timestamps
```

### 9. تغییرات روی `transactions` (طبق D8)
- افزودن ستون `bot_order_id` (FK nullable)
- افزودن ستون `bot_buy_execution_id` (FK nullable)
- افزودن مقادیر جدید به `TransactionTypeEnum` و `TransactionSubTypeEnum`:
  - `BOT_TRANSFER_IN`, `BOT_TRANSFER_OUT`
  - `BOT_TRANSFER_FEE`, `BOT_WITHDRAW_FEE`
  - `BOT_BUY`, `BOT_SELL`, `BOT_PROFIT`
  - `BOT_PERFORMANCE_FEE`, `BOT_CANCEL_FEE`, `BOT_NETWORK_FEE`

### 10. Seeder
- `BotGlobalSettingsSeeder` با مقادیر پیشفرض RFP.

## Agent Prompt — P1

```
Repo: itsomid/exchange-platform-hub
Branch base: develop (or main)

Implement Phase 1 of docs/implementation_plan.md.

Tasks:
1. Create migrations under admin-panel/database/migrations/:
   - create_bot_global_settings_table
   - create_bot_signals_table
   - create_bot_user_settings_table
   - create_bot_wallets_table
   - create_bot_orders_table
   - create_bot_buy_executions_table
   - create_bot_sell_orders_table
   - create_bot_trade_settlements_table
   - add_bot_columns_to_transactions_table (bot_order_id, bot_buy_execution_id as nullable FKs)
2. Extend `App\Enums\TransactionTypeEnum` and `TransactionSubTypeEnum` with the new BOT_* cases.
3. Create Eloquent models for every new table under admin-panel/app/Models/Bot/ namespace, with relations and casts (JSON, decimals, enums).
4. Create BotGlobalSettingsSeeder with defaults from docs/Auto-trade_RFP.md.
5. NO controllers, NO routes, NO views in this phase. Models + migrations + seeder only.
6. Add unit tests for: enum extension, BotWallet relations, BotSignal::scopeActive(), BotSignal::scopeEligibleForPrice($currentPrice).
7. Use existing project conventions (filterable trait if relevant, decimal(18,8) for prices, decimal(20,8) for USDT balances).

Acceptance:
- `php artisan migrate:fresh --seed` runs cleanly.
- All tests pass.
- No reinvest logic, no referral fields.

PR title: "P1: Auto-trade data model and migrations".
```

---

# Phase 2 — Admin Panel: Settings & Signals CRUD

## هدف
ادمین بتواند سیگنالها، تنظیمات کلی، و کارمزدها را مدیریت کند.

## خروجی
- Controllerها و Blade view ها زیر `admin-panel/resources/views/dashboard/bot/`
- Routeها در `admin-panel/routes/admin.php`
- Permission `bot-management` در Spatie
- آیتم منو در sidebar

## صفحات
1. `/admin/bot/settings` — singleton form برای `bot_global_settings`
2. `/admin/bot/signals` — index + create/edit/delete (الگو از `ApiSystemController`)
   - فرم signal: currency, priority, floor_price, ceiling_price, min_buy_amount, **max_allocation_percent**, sell_orders_count, sell_mode, sell_targets (repeater)
3. `/admin/bot/orders` — جدول read-only سفارشهای ربات (skeleton — KPI در فاز ۷)

## Agent Prompt — P2

```
Repo: itsomid/exchange-platform-hub  (admin-panel only)
Prerequisite: P1 merged.

Implement Phase 2 of docs/implementation_plan.md.

Follow conventions of existing admin/ApiSystemController and its Blade views.

Tasks:
1. Add Spatie permission "bot-management" via a dedicated migration/seeder.
2. Routes (admin-panel/routes/admin.php) under prefix /bot:
   - GET  /settings, PATCH /settings  → BotSettingsController
   - resource /signals               → BotSignalController (index/create/store/edit/update/destroy + toggle-status)
   - GET  /orders                    → BotOrderController@index (read-only paginated table)
3. Controllers under app/Http/Controllers/Admin/Bot/.
4. Form Requests with validation:
   - max_allocation_percent: required, numeric, >0, <=100
   - floor_price < ceiling_price
   - sell_targets: array, sum of share = 100
5. Blade views under resources/views/dashboard/bot/{settings,signals,orders}/ matching the project's master layout.
6. Add menu item "ربات معاملاتی" in resources/views/dashboard/layout/sidebar.blade.php with sub-items: "تنظیمات کلی" / "سیگنال‌ها" / "سفارش‌ها".
7. NO referral fields. NO reinvest controls in admin UI (reinvest is system-controlled in future phases).
8. Feature tests for each CRUD action and authorization.

PR title: "P2: Admin panel — bot settings & signals CRUD".
```

---

# Phase 3 — Bot Wallet API

## هدف
endpointهای فرانت برای onboarding، transfer in/out، settings، wallet view.

## خروجی
- Controllerها در `api-service/app/Http/Controllers/Bot/V1/`
- Form Requests + Resources
- `FeeCalculator` service
- Swagger annotations

## Endpointها
| Method | URL | شرح |
|--------|-----|-----|
| GET  | `/api/v1/bot/onboarding` | وضعیت پذیرش، video URL، fee preview tiers |
| POST | `/api/v1/bot/accept-terms` | ست `terms_accepted_at` |
| GET  | `/api/v1/bot/wallet` | balance, locked, principal, profit, allocation% |
| POST | `/api/v1/bot/transfer-in` | کسر کارمزد در همین لحظه (D5)، اسناد transactions، رویداد `BotWalletDeposited` |
| POST | `/api/v1/bot/transfer-out` | کارمزد یکسان (D6)، چک locked_balance |
| GET  | `/api/v1/bot/settings` | بازگرداندن تنظیمات کاربر |
| PATCH| `/api/v1/bot/settings` | فقط `auto_trade_enabled` قابل تغییر است در فاز اول. تغییر `false→true` رویداد `BotAutoTradeEnabled` منتشر میکند. |

## FeeCalculator
```php
public function transferFee(float $amount): float;
public function withdrawFee(float $amount): float; // identical to transferFee
```
قواعد (از RFP):
- `amount < 20` → reject
- `20 ≤ amount ≤ 100` → 1 USDT
- `100 < amount ≤ 1000` → 1% × amount
- `amount > 1000` → 12 USDT

## Agent Prompt — P3

```
Repo: itsomid/exchange-platform-hub  (api-service only)
Prerequisite: P1 merged.

Implement Phase 3 of docs/implementation_plan.md.

Tasks:
1. Create api-service/app/Services/Bot/FeeCalculator.php with transferFee/withdrawFee per docs/Auto-trade_RFP.md fee model. Reject amount<20 with a domain exception. Add full unit tests for boundaries (19,20,100,101,500,1000,1001,5000).
2. Create api-service/app/Services/Bot/BotWalletService.php with double-entry helpers writing to existing `transactions` table using new BOT_* enum cases (added in P1). Use DB transactions.
3. Controllers under api-service/app/Http/Controllers/Bot/V1/:
   - OnboardingController (show, accept)
   - WalletController (show, transferIn, transferOut)
   - SettingsController (show, update)
4. Routes under api-service/routes/api.php with prefix `bot/` and middleware `auth:sanctum`.
5. Form Requests with strict validation. transferIn/transferOut amounts go through FeeCalculator first.
6. JSON Resources for wallet view exposing: balance, locked_balance, principal_balance, profit_balance, allocation_used_percent.
7. Events: BotWalletDeposited, BotWalletWithdrawn, BotAutoTradeToggled (off→on triggers buy orchestrator in P4 — for now just dispatch event with no listener).
8. L5-Swagger annotations for all endpoints.
9. Feature tests covering: insufficient main balance, fee deduction in transactions table, locked_balance check on withdrawal, double-spend protection (concurrent requests).

Constraints:
- transferIn fee deducted at deposit time (NOT at first buy). Stored fee shows in `transactions` with subtype BOT_TRANSFER_FEE.
- Reinvest toggle in PATCH /settings must be REJECTED with 422 in phase 1 (not yet supported).
- No referral logic.

PR title: "P3: Bot wallet API & fee calculator".
```

---

# Phase 4 — Allocation Algorithm & Buy Engine

> این فاز قلب پروژه است. به دو زیر فاز شکسته میشود.

## P4-A — Pure Allocation Service (تستپذیر، بدون I/O)

### هدف
سرویسهای خالص برای محاسبه وزن و تخصیص با اعمال **سقف max_allocation_percent (D4)**.

### کلاسها
1. `SignalFilterService::eligibleSignals(): Collection<BotSignal>`
   - فقط `is_active = true` و `floor_price ≤ C_i ≤ ceiling_price`
   - منبع `C_i`: ابتدا `PriceFeed::getLive($currencyId)` (سوکت/cache)، fallback `exchange_prices`.
2. `WeightCalculatorService::compute(Collection $signals, float $alpha, float $B): array`
   - K = min(|F|, ⌊√B⌋)
   - مرتبسازی بر اساس priority، انتخاب K تای اول
   - p_i = (B_i - B_min) / (B_max - B_min)  (اگر B_max==B_min → p_i=0 برای همه)
   - q_i = (E_i - C_i) / (E_i - D_i)
   - W_i = q_i × (1 + (α-1)·p_i)
   - Ŵ_i = W_i / ΣW_i
   - returns `[signal_id => weight]`
3. `AllocationService::allocate(array $weights, Collection $signals, float $B): AllocationResult`
   - مرحله 1: A_i = B × Ŵ_i
   - مرحله 2: **Cap enforcement** — اگر A_i > B × cap_i → A_i = B × cap_i و overflow = A_i_old - A_i_new
   - مرحله 3: **Redistribute overflow** بین ارزهای not-capped به نسبت وزنهایشان (تکرار حداکثر K بار یا تا overflow < 0.01).
   - مرحله 4: حذف A_i < min_buy_amount و توزیع مجدد سهمشان (یک بار).
   - مرحله 5: مازاد قابل توزیع نهایی به عنوان `unallocated_remainder` بازگردانده میشود.
   - returns: `{ allocations: [signal_id => amount], unallocated_remainder: float, snapshot: [...] }`

### تست
- تک ارز با cap=30٪ → فقط 30٪ تخصیص، 70٪ unallocated.
- چند ارز با cap اعمال شده → overflow بین بقیه توزیع میشود.
- حذف ارز < min_buy → امن.

## P4-B — Orchestrator & Job (با I/O)

### کلاسها
1. `BotBuyOrchestrator` (Action class, callable):
   - ورودی: `userId`
   - چک `auto_trade_enabled = true`
   - چک `bot_wallets.balance - locked_balance ≥ min_deposit`
   - فراخوانی P4-A
   - ساخت `bot_orders` (status=PENDING) + `bot_buy_executions` per signal
   - افزایش `locked_balance`
   - dispatch `BuyExecutionJob` per execution
   - ثبت `unallocated_remainder` (در balance میماند)
2. `BuyExecutionJob` (Queueable):
   - فراخوانی صرافی مرجع از طریق سرویس `ExchangeBuyService` موجود
   - ذخیره fill price، fee، تغییر status به `BOUGHT`
   - ثبت سند `transactions` با subtype `BOT_BUY` و `BOT_EXCHANGE_FEE`
   - dispatch `OpenSellOrdersJob` (P5)
3. **Triggers**:
   - Listener روی `BotWalletDeposited` → orchestrator
   - Listener روی `BotAutoTradeToggled (off→on)` → orchestrator
   - (P5 listener روی fill با سود → reinvest در فاز اول NOOP)

## Agent Prompt — P4

```
Repo: itsomid/exchange-platform-hub  (api-service primarily; admin-panel for shared services if needed)
Prerequisite: P1, P3 merged.

Implement Phase 4 of docs/implementation_plan.md in TWO commits within one PR:

=== Commit 1: P4-A Pure services ===
1. api-service/app/Services/Bot/PriceFeed.php — getLive(int $currencyId): float, with socket→exchange_prices fallback.
2. api-service/app/Services/Bot/SignalFilterService.php — eligibleSignals(): Collection.
3. api-service/app/Services/Bot/WeightCalculatorService.php — compute(): array per docs/فرمول_نهایی_اختصاص_سرمایه_و_وزن_دهی.md (K = min(|F|, floor(sqrt(B)))).
4. api-service/app/Services/Bot/AllocationService.php — allocate() with:
   - max_allocation_percent enforcement (D4)
   - overflow redistribution (iterative, max K iterations, epsilon 0.01)
   - drop signals below min_buy_amount and redistribute their share once
   - return AllocationResult { allocations, unallocated_remainder, snapshot }
5. Comprehensive unit tests covering scenarios:
   a) Single eligible coin with cap=30% → only 30% allocated.
   b) Two coins, one hits cap → overflow flows to second.
   c) All coins hit cap → unallocated_remainder > 0.
   d) Coin allocation < min_buy → dropped, share redistributed.
   e) B_max == B_min edge case.
   f) No eligible coins → empty allocations, unallocated = B.

=== Commit 2: P4-B Orchestrator & Jobs ===
6. api-service/app/Actions/Bot/BotBuyOrchestrator.php (single __invoke($userId)):
   - check auto_trade_enabled
   - call P4-A pipeline
   - create bot_orders + bot_buy_executions
   - increment bot_wallets.locked_balance
   - dispatch BuyExecutionJob per execution
7. api-service/app/Jobs/Bot/BuyExecutionJob.php — queue 'bot-buy', retries 3, calls ExchangeBuyService (use existing exchange integration), writes transactions rows with new BOT_* enums, updates bot_buy_executions to BOUGHT, dispatches OpenSellOrdersJob (placeholder if P5 not yet merged).
8. Listeners:
   - HandleBotWalletDeposited → BotBuyOrchestrator
   - HandleBotAutoTradeToggled (only when off→on) → BotBuyOrchestrator
9. Feature tests:
   - End-to-end with a fake ExchangeBuyService.
   - Verify locked_balance is decremented if buy fails.

Constraints:
- Reinvest path is NOT triggered in this phase even if reinvest_enabled=true.
- All money math uses BCMath or decimal strings, never floats for persisted values (floats OK in pure services, but cast carefully when persisting).

PR title: "P4: Allocation algorithm & bot buy engine".
```

---

# Phase 5 — Sell Engine & Settlement

## هدف
ایجاد چند سفارش فروش به ازای هر buy، گوش دادن به fillها، محاسبه سود/زیان و ثبت اسناد.

## خروجی
- `OpenSellOrdersJob` که از engine P2P موجود استفاده میکند
- Listener روی event `P2POrderFilled` (یا معادل آن در پروژه)
- `SettlementService` برای محاسبه net_pnl و کارم��دها
- Endpointهای cancel-preview / cancel
- ثبت در `bot_trade_settlements` و `transactions`

## منطق Settlement
```
gross_revenue = sell_amount × sell_price
cost_basis    = sell_amount × avg_buy_price (از bot_buy_executions)
fees = network_fee + exchange_fee + spread_fee
gross_pnl = gross_revenue - cost_basis - fees
performance_fee = gross_pnl > 0 ? gross_pnl × performance_fee_percent : 0
net_pnl = gross_pnl - performance_fee
```
- اگر `net_pnl > 0`: `bot_wallets.profit_balance += net_pnl` و `balance += net_pnl`
- اگر `net_pnl ≤ 0`: فقط balance بهروز میشود
- در فاز اول هیچ reinvest job اجرا نمیشود (D1).

## Cancel
- Endpoint `POST /api/v1/bot/orders/{id}/cancel-preview` → نمایش breakdown هزینهها
- Endpoint `POST /api/v1/bot/orders/{id}/cancel` → لغو سفارشهای P2P باز + settlement + ثبت `BOT_CANCEL_FEE`

## Agent Prompt — P5

```
Repo: itsomid/exchange-platform-hub  (api-service)
Prerequisite: P4 merged.

Implement Phase 5 of docs/implementation_plan.md.

Tasks:
1. api-service/app/Jobs/Bot/OpenSellOrdersJob.php — given bot_buy_execution_id:
   - read signal.sell_orders_count and sell_targets
   - split filled_amount per share_percent
   - call existing internal P2P engine to create sell orders
   - persist bot_sell_orders rows with p2p_order_id and OPEN status
2. api-service/app/Listeners/Bot/HandleP2POrderFilled.php — listens to existing P2POrderFilled event:
   - find matching bot_sell_orders row
   - call SettlementService
3. api-service/app/Services/Bot/SettlementService.php — implements the formula above. Writes:
   - bot_trade_settlements row (full breakdown)
   - transactions rows with subtypes BOT_SELL, BOT_PERFORMANCE_FEE, BOT_PROFIT, BOT_NETWORK_FEE
   - updates bot_wallets.balance and profit_balance
   - DOES NOT trigger reinvest (D1).
4. Cancel endpoints in BotOrderController:
   - POST /bot/orders/{id}/cancel-preview → CostBreakdownResource
   - POST /bot/orders/{id}/cancel → cancels open p2p orders, runs SettlementService with cancel_fee, writes BOT_CANCEL_FEE transaction.
5. Feature tests for: profitable sell, losing sell, partial fill, cancel with profit, cancel without profit.

Constraints:
- All settlement actions inside a single DB transaction.
- Decimal precision: 8 for amounts, 8 for prices.
- Performance fee = 0 when gross_pnl ≤ 0.

PR title: "P5: Bot sell engine & settlement".
```

---

# Phase 6 — User Frontend

## هدف
UI کامل کاربر در `bitex-frontend-app`.

## خروجی
- `src/repository/BotRepository.js`
- `src/store/botStore.js` (Pinia)
- routes جدید زیر panel
- کامپوننتها در `src/components/bot/`
- i18n برای fa/en/ar

## صفحات و routeها
| route | view | شرح |
|-------|------|-----|
| `panel/bot/intro` | `BotIntroView.vue` | ویدیو + checkbox پذیرش |
| `panel/bot/dashboard` | `BotDashboardView.vue` | KPI، toggle، order list |
| `panel/bot/transfer-in` | `BotTransferInView.vue` | فرم با fee preview |
| `panel/bot/transfer-out` | `BotTransferOutView.vue` | فرم با fee preview |
| `panel/bot/orders/:id` | `BotOrderDetailView.vue` | جزئیات + cancel |

## کامپوننتهای کلیدی
- `BrBotToggleAutoTrade.vue`
- `BrBotToggleReinvest.vue` ← **disabled** + tooltip «بهزودی»
- `BrBotWalletCard.vue`
- `BrBotAllocationChart.vue` (درصد مصرفشده/مصرفنشده)
- `BrBotOrderList.vue`, `BrBotOrderRow.vue`
- `BrBotCancelDialog.vue` (نمایش breakdown هزینه)
- `BrBotFeePreview.vue` (محاسبه live کارمزد قبل از submit)

## Agent Prompt — P6

```
Repo: itsomid/bitex-frontend-app
Prerequisite: P3 merged on api-service.

Implement Phase 6 of docs/implementation_plan.md.

Follow project conventions:
- Repository pattern via src/repository/RepositoryFactory.js
- Pinia stores with showError helper
- Vuetify 3 components
- routes registered in src/router/routes/panel/index.js
- i18n keys under src/locales/{fa,en,ar}/bot/

Tasks:
1. src/repository/BotRepository.js with all endpoints from docs/openapi.yaml.
2. Register Bot in RepositoryFactory.
3. src/store/botStore.js — state {settings, wallet, orders, currentOrder}, actions matching API.
4. Routes:
   - panel.bot.intro            → BotIntroView.vue
   - panel.bot.dashboard        → BotDashboardView.vue
   - panel.bot.transfer-in      → BotTransferInView.vue
   - panel.bot.transfer-out     → BotTransferOutView.vue
   - panel.bot.order-detail     → BotOrderDetailView.vue (path: bot/orders/:id)
   Guard: if !terms_accepted_at → redirect to intro.
5. Components under src/components/bot/:
   - BrBotToggleAutoTrade.vue (calls PATCH /bot/settings)
   - BrBotToggleReinvest.vue (visually present, prop disabled=true, tooltip "بزودی")
   - BrBotWalletCard.vue
   - BrBotAllocationChart.vue (uses chartAssets-style data)
   - BrBotOrderList.vue, BrBotOrderRow.vue
   - BrBotCancelDialog.vue
   - BrBotFeePreview.vue (computes fee client-side using same tier rules; double-checked server-side)
6. Sidebar entry "ربات معاملاتی" with sub-items.
7. i18n for fa, en, ar.
8. NO referral UI anywhere.
9. NO calls to reinvest endpoint (does not exist yet).

Acceptance:
- Lint clean (npm run lint).
- All views render with mocked store data (Storybook or simple mounted test).

PR title: "P6: Auto-trade user frontend".
```

---

# Phase 7 — Reports

## هدف
گزارشهای کاربر و ادمین (SRS بند 5.11 و 5.12) بدون فیلدهای رفرال و بدون KPI تجمیعی فاز ۲.

## خروجی
### Admin (`admin-panel`)
- داشبورد جدید `/admin/bot/reports` با کارتهای KPI: تعداد کاربران فعال، حجم خرید کل، حجم به تفکیک ارز، سود مجموعه، کارمزد لغو، کارمزد صرافی مرجع، سود خالص سیستم.
- جدول فیلترشدنی معاملات (نمونه از `BotOrderController`).
- Export CSV.

### User (`api-service` + `bitex-frontend-app`)
- Endpoint `GET /api/v1/bot/reports/summary?from=&to=`
- Endpoint `GET /api/v1/bot/reports/per-coin`
- نمودارهای داشبورد ربات (PnL روزانه، میانگین خرید per coin)

## Agent Prompt — P7

```
Repos: itsomid/exchange-platform-hub (admin-panel + api-service) and itsomid/bitex-frontend-app
Prerequisite: P5 merged.

Implement Phase 7 of docs/implementation_plan.md.

Admin (admin-panel):
1. /admin/bot/reports route + BotReportController + Blade view with KPI cards reading from bot_trade_settlements and transactions (BOT_* subtypes).
2. CSV export endpoints for orders and settlements.

API (api-service):
3. GET /api/v1/bot/reports/summary — totals: deposit, withdrawal, profit, allocation%, daily PnL series.
4. GET /api/v1/bot/reports/per-coin — per-currency average buy, total bought, current value, PnL.

Frontend (bitex-frontend-app):
5. Extend BotDashboardView with charts (use existing chart wrapper, e.g. ApexCharts already used in project).
6. Per-coin table integrated with BrBotOrderList.

Constraints:
- DO NOT include the "system-wide aggregate" stats (deferred to phase 2 per SRS 5.12).
- DO NOT include referral stats.

PR title: "P7: Auto-trade reports".
```

---

# Phase 8 — QA, Acceptance & Deploy

## هدف
کیفیت تولید + استقرار تدریجی.

## خروجی
- مجموعه acceptance test مطابق SRS بند ۶
- Load test (k6 یا Artillery) روی orchestrator با 1000 کاربر همزمان
- Feature flag `bot.enabled` در `config/features.php`
- Runbook + alertها (Sentry + Telegram bot برای job failures)
- مستند کاربر فارسی برای modal «شرایط را میپذیرم»

## Acceptance scenarios (طبق SRS 6)
1. **6.1 — استاندارد**: واریز 1000 + هر دو روشن → خرید طبق الگوریتم → fillها → سود به balance.
2. **6.2 — توقف خرید با حفظ سفارشهای باز**: dimming auto-trade بعد از خرید → سفارشهای موجود باز میمانند، واریز جدید no-op.
3. **6.4 — فاز اول بدون reinvest** (سناریوی فعلی پروژه): فقط دکمه خرید، بدون منطق reinvest.

## Agent Prompt — P8

```
Repo: itsomid/exchange-platform-hub + itsomid/bitex-frontend-app
Prerequisite: P7 merged.

Implement Phase 8 of docs/implementation_plan.md.

Tasks:
1. Add feature flag `bot.enabled` to admin-panel and api-service config/features.php; gate all bot routes/listeners on it.
2. Write acceptance tests in api-service/tests/Acceptance/Bot/ implementing SRS scenarios 6.1, 6.2, 6.4 end-to-end (skip 6.3 in phase 1).
3. Add k6 script under tests/load/bot-orchestrator.js simulating 1000 concurrent transfer-in events.
4. Add Sentry breadcrumbs and Telegram alert on BuyExecutionJob/SellEngine failures.
5. Document the user-facing terms acceptance copy in docs/bot-user-terms.md (Farsi).
6. Add migration plan + rollback plan in docs/bot-deploy-runbook.md.

PR title: "P8: Auto-trade QA, acceptance & deploy hardening".
```

---

## 📎 پیوستها

### A) Fee tiers (مرجع پیادهسازی)

| amount (USDT) | fee |
|---------------|-----|
| `< 20` | reject (under minimum) |
| `20 ≤ x ≤ 100` | `1` USDT |
| `100 < x ≤ 1000` | `1% × x` |
| `x > 1000` | `12` USDT |

### B) فرمول وزن (مرجع — جزئیات کامل در `فرمول_نهایی_اختصاص_سرمایه_و_وزن_دهی.md`)

```
F_i = 1 if D_i ≤ C_i ≤ E_i else 0
K   = min(Σ F_i, floor(sqrt(B)))
S   = top-K eligible signals by priority
p_i = (B_i - B_min) / (B_max - B_min)        // 0 if B_max == B_min
q_i = (E_i - C_i) / (E_i - D_i)
W_i = q_i × (1 + (α - 1) × p_i)
Ŵ_i = W_i / Σ W_j  (j ∈ S)
A_i = B × Ŵ_i      // before applying max_allocation_percent
```
**سپس** اعمال D4 (cap) + redistribute overflow.

### C) فهرست enum های جدید transactions (طبق D8)

```
BOT_TRANSFER_IN, BOT_TRANSFER_OUT,
BOT_TRANSFER_FEE, BOT_WITHDRAW_FEE,
BOT_BUY, BOT_SELL,
BOT_PROFIT,
BOT_PERFORMANCE_FEE,
BOT_CANCEL_FEE,
BOT_NETWORK_FEE,
BOT_EXCHANGE_FEE
```

### D) جداول جدیدی که ساخته میشوند (مرور سریع)

```
bot_global_settings        (singleton)
bot_signals                (definition per coin, includes max_allocation_percent)
bot_user_settings          (per user toggles + terms_accepted_at)
bot_wallets                (balance/principal/profit/locked)
bot_orders                 (master batch order)
bot_buy_executions         (per coin within a batch)
bot_sell_orders            (sub-orders against P2P engine)
bot_trade_settlements      (snapshot per closed sell — for fast reports)
```

### E) چه چیزی **ساخته نمیشود** در فاز اول

- ❌ `bot_fee_logs` (به جای آن `transactions` با subtypeهای جدید)
- ❌ `bot_pnl_ledger` (به جای آن `bot_trade_settlements`)
- ❌ هر فیلد یا منوی رفرال
- ❌ منطق پیادهسازی reinvest (فقط ستونها ساخته میشوند)
- ❌ KPIهای تجمیعی کل سیستم در داشبورد کاربر (SRS 5.12 — فاز ۲)

---

## 🚦 ترتیب پیشنهادی واگذاری به Agent

برای هر فاز یک PR مستقل با عنوان طبق Agent Prompt آن فاز ساخته شود:

```
1.  P0 → docs only          (1 PR)
2.  P1 → migrations + models (1 PR)
3.  P2 و P3 موازی            (2 PR)
4.  P4                       (1 PR، دو commit)
5.  P5                       (1 PR)
6.  P6                       (1 PR، موازی با P5 مجاز)
7.  P7                       (1 PR)
8.  P8                       (1 PR)
```

**جمع کل: ۹ PR در ۸ فاز.**