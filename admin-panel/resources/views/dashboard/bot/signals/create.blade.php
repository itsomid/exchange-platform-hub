@extends('dashboard.layout.master')

@section('title', 'افزودن سیگنال جدید')

@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">افزودن سیگنال جدید</h4>
                    <a href="{{ route('admin.bot.signal.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-right me-1"></i> بازگشت
                    </a>
                </div>
                <div class="card-body">
                    @include('dashboard.bot.signals._form', [
                        'formAction' => route('admin.bot.signal.store'),
                        'method'     => 'POST',
                        'signal'     => null,
                    ])
                </div>
            </div>
        </div>
    </div>

@endsection
