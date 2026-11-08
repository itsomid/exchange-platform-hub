@props([
    'light' => null,
    'dark' => null,
])

@php
    $preference = \App\Support\AdminTheme::preference();
    $lightFiles = array_values(array_filter(is_array($light) ? $light : ($light ? [$light] : [])));
    $darkFiles = array_values(array_filter(is_array($dark) ? $dark : ($dark ? [$dark] : [])));
@endphp

@if ($preference === \App\Support\AdminTheme::SYSTEM)
    @foreach ($lightFiles as $file)
        <link rel="stylesheet" href="{{ \Illuminate\Support\Facades\Vite::asset($file) }}" media="(prefers-color-scheme: light)">
    @endforeach
    @foreach ($darkFiles as $file)
        <link rel="stylesheet" href="{{ \Illuminate\Support\Facades\Vite::asset($file) }}" media="(prefers-color-scheme: dark)">
    @endforeach
@elseif ($preference === \App\Support\AdminTheme::DARK)
    @if ($darkFiles)
        @vite($darkFiles)
    @endif
@elseif ($lightFiles)
    @vite($lightFiles)
@endif
