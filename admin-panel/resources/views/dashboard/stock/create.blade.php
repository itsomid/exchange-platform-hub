@extends('dashboard.layout.master')
@section('title', 'تعریف سهام جدید')
@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">تعریف سهام جدید</h5>
            <form class="row" method="post" action="{{route('admin.stock.store')}}">
                @csrf
                <div class="col-md-6 mt-3">
                    <label class="form-label" for="name">نام سهام:</label>
                    <input type="text"
                           name="name"
                           id="name"
                           class="form-control"
                           value="{{old('name')}}"
                           placeholder="نام سهام را وارد کنید">
                    @error('name')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-6 mt-3">
                    <label class="form-label" for="value">ارزش سهام:</label>
                    <input type="number"
                           name="value"
                           id="value"
                           class="form-control"
                           value="{{old('value')}}"
                           step="0.01"
                           placeholder="ارزش سهام را وارد کنید">
                    @error('value')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-6 mt-3">
                    <label class="form-label" for="initial_quantity">تعداد کل سهام اولیه:</label>
                    <input type="number"
                           name="initial_quantity"
                           id="initial_quantity"
                           class="form-control"
                           value="{{old('initial_quantity', 0)}}"
                           step="0.001"
                           min="0"
                           placeholder="تعداد کل سهام اولیه را وارد کنید">
                    @error('initial_quantity')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                    <small class="text-muted">این مقدار به عنوان مرجع برای محاسبه درصد موجودی استفاده می‌شود</small>
                </div>
                <div class="col-md-6 mt-3">
                    <label class="form-label" for="available_quantity">تعداد سهام موجود:</label>
                    <input type="number"
                           name="available_quantity"
                           id="available_quantity"
                           class="form-control"
                           value="{{old('available_quantity', 0)}}"
                           step="0.001"
                           min="0"
                           placeholder="تعداد سهام موجود را وارد کنید">
                    @error('available_quantity')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-6 mt-3">
                    <label class="form-label" for="type">نوع سهام:</label>
                    <select name="type" id="type" class="form-control">
                        @foreach(\App\Enums\StockTypeEnum::cases() as $type)
                            <option value="{{$type->value}}" {{ old('type') == $type->value ? 'selected' : '' }}>{{$type->label()}}</option>
                        @endforeach
                    </select>
                    @error('type')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-6 mt-3">
                    <label class="form-label" for="cancellation_fee">کارمزد ابطال (درصد):</label>
                    <div class="input-group">
                        <input type="number"
                               name="cancellation_fee"
                               id="cancellation_fee"
                               class="form-control"
                               value="{{old('cancellation_fee')}}"
                               step="0.01"
                               min="0"
                               max="100"
                               placeholder="درصد کارمزد ابطال را وارد کنید">
                        <span class="input-group-text">%</span>
                    </div>
                    @error('cancellation_fee')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-6 mt-3">
                    <label class="form-label" for="status">وضعیت سهام:</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                    </select>
                    @error('status')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-12 mt-3">
                    <label class="form-label" for="description">توضیحات:</label>
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="توضیحات سهام را وارد کنید">{{old('description')}}</textarea>
                    @error('description')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-12 text-right mt-5">
                    <button class="btn btn-primary mt-2">
                        <i class="fa fa-plus mx-2"></i>
                        ثبت و ذخیره
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
