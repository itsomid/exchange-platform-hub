@extends('dashboard.layout.master')
@section('title', 'ویرایش قرارداد')
@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">ویرایش قرارداد</h5>
            <form class="row" method="post" action="{{ route('admin.stock-contract.update', $stockContract->id) }}">
                @csrf
                @method('PATCH')
                
                <div class="col-md-6">
                    <label class="form-label mt-5" for="user_id">کاربر :</label>
                    <select name="user_id" id="user_id" class="form-control @error('user_id') is-invalid @enderror" required>
                        <option value="">انتخاب کاربر</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id', $stockContract->user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label mt-5" for="stock_id">سهام :</label>
                    <select name="stock_id" id="stock_id" class="form-control @error('stock_id') is-invalid @enderror" required>
                        <option value="">انتخاب سهام</option>
                        @foreach($stocks as $stock)
                            <option value="{{ $stock->id }}" {{ old('stock_id', $stockContract->stock_id) == $stock->id ? 'selected' : '' }}>
                                {{ $stock->name }} - {{ number_format($stock->value) }} تومان
                            </option>
                        @endforeach
                    </select>
                    @error('stock_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mt-3">
                    <label class="form-label" for="amount">تعداد سهم:</label>
                    <input type="number"
                           name="amount"
                           id="amount"
                           class="form-control @error('amount') is-invalid @enderror"
                           value="{{ old('amount', $stockContract->amount) }}"
                           placeholder="تعداد سهم را وارد کنید"
                           min="1"
                           required>
                    @error('amount')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-6 mt-3">
                    <label class="form-label" for="contract_status">وضعیت قرارداد:</label>
                    <select name="contract_status" id="contract_status" class="form-control @error('contract_status') is-invalid @enderror" required>
                        <option value="active" {{ old('contract_status', $stockContract->contract_status->value) == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="sold" {{ old('contract_status', $stockContract->contract_status->value) == 'sold' ? 'selected' : '' }}>فروخته شده</option>
                        <option value="canceled" {{ old('contract_status', $stockContract->contract_status->value) == 'canceled' ? 'selected' : '' }}>لغو شده</option>
                    </select>
                    @error('contract_status')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="col-md-12 mt-3">
                    <label class="form-label" for="description">توضیحات:</label>
                    <textarea name="description" 
                              id="description" 
                              class="form-control @error('description') is-invalid @enderror" 
                              rows="3" 
                              placeholder="توضیحات قرارداد را وارد کنید">{{ old('description', $stockContract->description) }}</textarea>
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
                        <i class="fa fa-save mx-2"></i>
                        ذخیره تغییرات
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection 