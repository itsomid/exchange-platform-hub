@extends('dashboard.layout.master')
@section('title', 'مشاهده قرارداد')
@section('content')
    <div class="card">
        <div class="card-header">
            <div class="card-title header-elements">
                <h5 class="m-0 me-2">جزئیات قرارداد</h5>
                <div class="card-title-elements ms-auto">
                    <a href="{{ route('admin.stock-contract.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-right mx-2"></i> بازگشت
                    </a>
                    <a href="{{ route('admin.stock-contract.edit', $stockContract->id) }}" class="btn btn-primary">
                        <i class="fa fa-edit mx-2"></i> ویرایش
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <h6 class="fw-bold">اطلاعات قرارداد</h6>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">شماره قرارداد:</span>
                                <span class="fw-medium">{{ $stockContract->contract_number }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">وضعیت:</span>
                                <span class="badge bg-label-{{ $stockContract->contract_status->color() }} rounded p-2">
                                    {{ $stockContract->contract_status->label() }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">تاریخ ایجاد:</span>
                                <span>{{ $stockContract->created_at->format('Y/m/d H:i') }}</span>
                            </div>
                            @if($stockContract->sold_at)
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">تاریخ فروش:</span>
                                    <span>{{ $stockContract->sold_at->format('Y/m/d H:i') }}</span>
                                </div>
                            @endif
                            @if($stockContract->cancelled_at)
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">تاریخ ابطال:</span>
                                    <span>{{ $stockContract->cancelled_at->format('Y/m/d H:i') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-4">
                        <h6 class="fw-bold">اطلاعات مالی</h6>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">تعداد سهم:</span>
                                <span class="font-number" dir="ltr">{{ number_format($stockContract->amount) }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">ارزش هر سهم:</span>
                                <span class="font-number" dir="ltr">{{ number_format($stockContract->stock->value ?? 0) }} تومان</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">ارزش کل قرارداد:</span>
                                <span class="font-number" dir="ltr">{{ number_format($stockContract->total_value) }} تومان</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">کارمزد ابطال:</span>
                                <span class="font-number" dir="ltr">{{ number_format($stockContract->cancellation_fee) }} تومان</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-4">
                        <h6 class="fw-bold">اطلاعات کاربر</h6>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">نام:</span>
                                <span>{{ $stockContract->user->name ?? 'نامشخص' }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">ایمیل:</span>
                                <span>{{ $stockContract->user->email ?? 'نامشخص' }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">شماره تلفن:</span>
                                <span>{{ $stockContract->user->phone ?? 'نامشخص' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-4">
                        <h6 class="fw-bold">اطلاعات سهام</h6>
                        <div class="d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">نام سهام:</span>
                                <span>{{ $stockContract->stock->name ?? 'نامشخص' }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">نوع سهام:</span>
                                <span>{{ $stockContract->stock->type->label() ?? 'نامشخص' }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">وضعیت سهام:</span>
                                <span class="badge bg-label-{{ $stockContract->stock->status->color() ?? 'secondary' }} rounded p-2">
                                    {{ $stockContract->stock->status->label() ?? 'نامشخص' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($stockContract->description)
                    <div class="col-md-12">
                        <div class="mb-4">
                            <h6 class="fw-bold">توضیحات</h6>
                            <p class="text-muted">{{ $stockContract->description }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection 