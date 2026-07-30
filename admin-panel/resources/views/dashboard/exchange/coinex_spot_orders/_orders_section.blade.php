@php
    $isBuy = ($side ?? '') === 'buy';
    $orders = $orders ?? [];
    $pagination = $pagination ?? [];
@endphp

<div class="card mb-4">
    <div class="card-header border-bottom-0 pb-0">
        <div class="order-section-title">
            <span class="dot {{ $isBuy ? 'dot-buy' : 'dot-sell' }}"></span>
            <h5 class="mb-0">{{ $title }}</h5>
            <span class="badge {{ $isBuy ? 'side-badge-buy' : 'side-badge-sell' }} rounded-pill ms-1">
                {{ count($orders) }} در این صفحه
                @if (isset($pagination['total']))
                    / کل {{ number_format((int) $pagination['total']) }}
                @endif
            </span>
        </div>
    </div>
    <div class="card-body pt-2">
        @if (empty($orders))
            <div class="empty-state py-4">
                <i class="fa-light fa-inbox fa-2x mb-2"></i>
                <div>سفارشی یافت نشد.</div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>بازار</th>
                            <th>سمت</th>
                            <th>نوع</th>
                            <th>مقدار</th>
                            <th>قیمت</th>
                            <th>پر شده</th>
                            <th>باقی‌مانده</th>
                            <th>ارزش پرشده</th>
                            <th>کارمزد</th>
                            <th>زمان</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php
                                $orderId = $order['order_id'] ?? null;
                                $sideLabel = ($order['side'] ?? '') === 'buy' ? 'خرید' : 'فروش';
                                $sideClass = ($order['side'] ?? '') === 'buy' ? 'side-badge-buy' : 'side-badge-sell';
                                $baseFee = $order['base_fee'] ?? '0';
                                $quoteFee = $order['quote_fee'] ?? '0';
                                $discountFee = $order['discount_fee'] ?? '0';
                                $modalId = 'coinex-order-' . ($cancellable ? 'p' : 'f') . '-' . ($order['side'] ?? 'x') . '-' . $orderId;
                            @endphp
                            <tr>
                                <td class="fw-semibold">#{{ $orderId }}</td>
                                <td>
                                    <span class="text-nowrap">{{ $order['market'] ?? $market }}</span>
                                    @if (!empty($order['ccy']))
                                        <div class="small text-muted">{{ $order['ccy'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $sideClass }} rounded-pill px-2 py-1">{{ $sideLabel }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-label-secondary">{{ $order['type'] ?? '—' }}</span>
                                </td>
                                <td>{{ $fmt($order['amount'] ?? 0) }}</td>
                                <td>{{ $fmt($order['price'] ?? 0) }}</td>
                                <td>
                                    <div>{{ $fmt($order['filled_amount'] ?? 0) }}</div>
                                    @if (!empty($order['last_fill_amount']) || !empty($order['last_filled_amount']))
                                        <small class="text-muted">
                                            آخرین:
                                            {{ $fmt($order['last_fill_amount'] ?? $order['last_filled_amount'] ?? 0) }}
                                            @ {{ $fmt($order['last_fill_price'] ?? $order['last_filled_price'] ?? 0) }}
                                        </small>
                                    @endif
                                </td>
                                <td>{{ $fmt($order['unfilled_amount'] ?? 0) }}</td>
                                <td>{{ $fmt($order['filled_value'] ?? 0) }}</td>
                                <td>
                                    <div class="small">Base: {{ $fmt($baseFee) }}</div>
                                    <div class="small">Quote: {{ $fmt($quoteFee) }}</div>
                                    @if ((float) $discountFee > 0)
                                        <div class="small text-success">Discount: {{ $fmt($discountFee) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">ایجاد: {{ $fmtTs($order['created_at'] ?? null) }}</div>
                                    <div class="small text-muted">بروزرسانی: {{ $fmtTs($order['updated_at'] ?? null) }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#{{ $modalId }}" title="جزئیات">
                                            <i class="fa-light fa-eye"></i>
                                        </button>
                                        @if ($cancellable)
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-danger cancel-coinex-order"
                                                data-order-id="{{ $orderId }}"
                                                data-market="{{ $order['market'] ?? $market }}"
                                                title="لغو سفارش">
                                                <i class="fa-light fa-times"></i>
                                            </button>
                                        @endif
                                    </div>

                                    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <div>
                                                        <h5 class="modal-title mb-1">جزئیات سفارش #{{ $orderId }}</h5>
                                                        <div class="small text-muted">
                                                            {{ $order['market'] ?? $market }} · {{ $sideLabel }} · {{ $order['type'] ?? '—' }}
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="detail-grid">
                                                        @foreach ([
                                                            'order_id' => 'Order ID',
                                                            'market' => 'بازار',
                                                            'market_type' => 'نوع بازار',
                                                            'ccy' => 'ارز',
                                                            'side' => 'سمت',
                                                            'type' => 'نوع سفارش',
                                                            'amount' => 'مقدار',
                                                            'price' => 'قیمت',
                                                            'unfilled_amount' => 'باقی‌مانده',
                                                            'filled_amount' => 'پر شده',
                                                            'filled_value' => 'ارزش پرشده',
                                                            'client_id' => 'Client ID',
                                                            'base_fee' => 'کارمزد پایه',
                                                            'quote_fee' => 'کارمزد نقل‌قول',
                                                            'discount_fee' => 'کارمزد تخفیف',
                                                            'maker_fee_rate' => 'نرخ Maker',
                                                            'taker_fee_rate' => 'نرخ Taker',
                                                            'last_fill_amount' => 'آخرین مقدار پرشده',
                                                            'last_filled_amount' => 'آخرین مقدار پرشده',
                                                            'last_fill_price' => 'آخرین قیمت پرشده',
                                                            'last_filled_price' => 'آخرین قیمت پرشده',
                                                            'created_at' => 'زمان ایجاد',
                                                            'updated_at' => 'زمان بروزرسانی',
                                                        ] as $key => $label)
                                                            @if (array_key_exists($key, $order) && $order[$key] !== null && $order[$key] !== '')
                                                                <div class="detail-item">
                                                                    <span class="label">{{ $label }}</span>
                                                                    <span class="value">
                                                                        @if (in_array($key, ['created_at', 'updated_at'], true))
                                                                            {{ $fmtTs($order[$key]) }}
                                                                        @elseif (in_array($key, ['amount', 'price', 'unfilled_amount', 'filled_amount', 'filled_value', 'base_fee', 'quote_fee', 'discount_fee', 'last_fill_amount', 'last_filled_amount', 'last_fill_price', 'last_filled_price', 'maker_fee_rate', 'taker_fee_rate'], true))
                                                                            {{ $fmt($order[$key]) }}
                                                                        @else
                                                                            {{ $order[$key] }}
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                                @if ($cancellable)
                                                    <div class="modal-footer">
                                                        <button type="button"
                                                            class="btn btn-danger cancel-coinex-order"
                                                            data-order-id="{{ $orderId }}"
                                                            data-market="{{ $order['market'] ?? $market }}"
                                                            data-bs-dismiss="modal">
                                                            <i class="fa-light fa-times me-1"></i>
                                                            لغو این سفارش
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
