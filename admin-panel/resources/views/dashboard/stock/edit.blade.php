@extends('dashboard.layout.master')
@section('title', 'ویرایش سهام')
@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">ویرایش سهام</h5>
            <form class="row" method="post" action="{{route('admin.stock.update', $stock->id)}}">
                @csrf
                @method('PATCH')
                <div class="col-md-6 mt-3">
                    <label class="form-label" for="name">نام سهام:</label>
                    <input type="text"
                           name="name"
                           id="name"
                           class="form-control"
                           value="{{old('name', $stock->name)}}"
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
                           value="{{old('value', $stock->value)}}"
                           placeholder="ارزش سهام را وارد کنید">
                    @error('value')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-6 mt-3">
                    <label class="form-label" for="type">نوع سهام:</label>
                    <select name="type" id="type" class="form-control">
                        @foreach(\App\Enums\StockTypeEnum::cases() as $type)
                            <option value="{{$type->value}}" {{ old('type', $stock->type->value ?? $stock->type) == $type->value ? 'selected' : '' }}>{{$type->label()}}</option>
                        @endforeach
                    </select>
                    @error('type')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-6 mt-3">
                    <label class="form-label" for="cancellation_fee">کارمزد ابطال:</label>
                    <input type="number"
                           name="cancellation_fee"
                           id="cancellation_fee"
                           class="form-control"
                           value="{{old('cancellation_fee', $stock->cancellation_fee)}}"
                           placeholder="کارمزد ابطال را وارد کنید">
                    @error('cancellation_fee')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-6 mt-3">
                    <label class="form-label" for="status">وضعیت سهام:</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active" {{ old('status', $stock->status) == 'active' ? 'selected' : '' }}>فعال</option>
                        <option value="inactive" {{ old('status', $stock->status) == 'inactive' ? 'selected' : '' }}>غیرفعال</option>
                    </select>
                    @error('status')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-12 mt-3">
                    <label class="form-label" for="description">توضیحات:</label>
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="توضیحات سهام را وارد کنید">{{old('description', $stock->description)}}</textarea>
                    @error('description')
                    <small class="text-danger">{{$message}}</small>
                    @enderror
                </div>
                <div class="col-md-12 text-right mt-5">
                    <button class="btn btn-primary mt-2">
                        <i class="fa fa-save mx-2"></i>
                        ذخیره تغییرات
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection 