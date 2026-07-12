@php
    $systemSegments = [];
    if (! empty($description)) {
        foreach (preg_split('/\s*\|\|\s*/', trim((string) $description)) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $symbol = null;
            $text = $part;
            if (preg_match('/^\[([A-Z0-9]+)\]\s*(.+)$/s', $part, $m)) {
                $symbol = $m[1];
                $text = trim($m[2]);
            }
            $systemSegments[] = ['symbol' => $symbol, 'text' => $text];
        }
    }
@endphp

@if (! empty($systemSegments))
    @foreach ($systemSegments as $seg)
        @include('dashboard.bot.orders.partials.system-note-card', [
            'symbol' => $seg['symbol'],
            'text' => $seg['text'],
        ])
    @endforeach
@else
    <p class="text-muted small mb-0 text-center py-3">توضیح سیستمی برای این سفارش ثبت نشده است.</p>
@endif
