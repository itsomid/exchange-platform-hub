@props([
    'explain' => null,
    'label' => 'CANCELED',
    'class' => 'bg-danger',
    'style' => '',
])

@if ($explain)
    <span class="badge {{ $class }} canceled-reason-badge" style="cursor:help; {{ $style }}"
        data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-container="body"
        data-bs-custom-class="tooltip-cancel-reason" title="{!! $explain['html'] !!}">
        {{ $label }}
        <i class="fa-regular fa-question-circle ms-1" style="font-size:.72em; opacity:.85"></i>
    </span>
@else
    <span class="badge {{ $class }}" style="{{ $style }}">{{ $label }}</span>
@endif
