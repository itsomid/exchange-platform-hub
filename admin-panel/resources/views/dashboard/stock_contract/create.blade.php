@extends('dashboard.layout.master')
@section('title', 'ایجاد قرارداد جدید')
@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">ایجاد قرارداد جدید</h5>
            <form class="row" method="post" action="{{ route('admin.stock-contract.store') }}">
                @csrf
                <div class="col-md-6">
                    <label class="form-label mt-5" for="user_id">کاربر :</label>
                    <x-user-selection-component inputName="user_id" multiple="0" selected="" selected-label="">
                    </x-user-selection-component>
                </div>

                <div class="col-md-6">
                    <label class="form-label mt-5" for="stock_id">سهام :</label>
                    <select name="stock_id" id="stock_id" class="form-control @error('stock_id') is-invalid @enderror"
                        required>
                        <option value="">انتخاب سهام</option>
                        @foreach ($stocks as $stock)
                            <option value="{{ $stock->id }}" {{ old('stock_id') == $stock->id ? 'selected' : '' }}>
                                {{ $stock->name }} - {{ number_format($stock->value) }} USDT
                            </option>
                        @endforeach
                    </select>
                    @error('stock_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mt-3">
                    <label class="form-label" for="amount">تعداد سهم:</label>
                    <input type="number" name="amount" id="amount"
                        class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}"
                        placeholder="تعداد سهم را وارد کنید"  step="0.01" required>
                    @error('amount')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mt-3">
                    <label class="form-label" for="contract_status">وضعیت قرارداد:</label>
                    <select name="contract_status" id="contract_status"
                        class="form-control @error('contract_status') is-invalid @enderror" required>
                        <option value="active" {{ old('contract_status') == 'active' ? 'selected' : '' }}>فعال</option>
                    </select>
                    @error('contract_status')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-12 mt-3">
                    <label class="form-label" for="description">توضیحات:</label>
                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                        rows="3" placeholder="توضیحات قرارداد را وارد کنید">{{ old('description') }}</textarea>
                    @error('description')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-12 text-right mt-5">
                    <a href="{{ route('admin.stock-contract.index') }}" class="btn btn-secondary me-2">
                        <i class="fa fa-arrow-right mx-2"></i>
                        بازگشت
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-plus mx-2"></i>
                        ثبت و ذخیره
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
