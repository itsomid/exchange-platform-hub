@extends('dashboard.layout.master')
@section('title','وضعیت دسترسی مالی کاربر')
@section('content')

    <div class="card mb-4">
        <h5 class="card-header">ایجاد گروهی محدودیت دسترسی مالی</h5>
        <div class="card-body">
            <form action="{{route('admin.user.financial-block.store-mass-block')}}" method="post">
                @csrf
                <div class="row">

                    <div class="col-md-6 mb-1">
                        <div class="form-group mt-3">
                            <label class="form-label" for="action">دلیل محدودیت</label>
                            <select id="action" name="action" class="form-select text-capitalize mb-md-0 ">
                                @foreach($userFinancialBlockActions as $action)
                                    <option
                                            {{ old('action') == $action->value ? 'selected' : '' }} value="{{$action}}">
                                        {{ \App\Enums\FinancialBlockActionEnum::TYPE_LABEL[$action->value] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 mb-1">
                        <label for="users" class="form-label">لیست کاربران (Email)</label>
                        <textarea name="users" class="form-control" id="users" rows="3"></textarea>
                    </div>
                    <div class="col-md-6 mb-1">
                        <div class="form-group mt-2">
                            <div class="form-group">
                                <label class="form-label" for="restricted_until">زمان پایان محدودیت:</label>
                                <input name="restricted_until"
                                       type="text"
                                       id="restricted_until"
                                       class="form-control"
                                       data-jdp
                                       value="{{old('restricted_until')}}"
                                       autocomplete="off"
                                       required
                                       placeholder="زمان پایان محدودیت را وارد کنید">
                            </div>
                        </div>
                    </div>
                    <div class="w-100"></div>

                    <div class="col-md-6 mt-4">
                        <div class="form-group">
                            <label for="reason">توضیحات</label>
                            <textarea class="form-control" name="reason" id="reason"
                                      rows="3"></textarea>
                        </div>
                    </div>
                    <div class="col-12 text-right  mt-4">

                        <button class="btn btn-primary">
                            <i class="fa fa-save mx-2"></i>
                            ثبت محدودیت
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>

@endsection
@section('vendor-script')
    @vite([
            'resources/assets/vendor/js/forms-selects.js',
            'resources/assets/js/jalalidatepicker.js',
            'resources/assets/vendor/libs/cleavejs/cleave.js',
            'resources/assets/js/forms-extras.js',
          ])
@endsection
