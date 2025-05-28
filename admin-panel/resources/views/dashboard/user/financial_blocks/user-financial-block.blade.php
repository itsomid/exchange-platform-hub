@extends('dashboard.user.layout.master')
@section('title','وضعیت دسترسی مالی کاربر')
@section('user-body')

    <div class="card mb-4">
        <h5 class="card-header">ایجاد محدودیت دسترسی مالی</h5>
        <div class="card-body">
            <form action="{{route('admin.user.financial-block.addBlock', ['user' => $user->id])}}" method="post">
                @csrf
                <div class="row">

                    <div class="col-md-6 mb-1">
                        <div class="form-group mt-3">
                            <label class="form-label" for="action">دلیل محدودیت</label>
                            <select id="action" name="action" class="form-select text-capitalize mb-md-0 ">
                                @foreach($userFinancialBlockActions as $action)
                                    <option
                                            {{ old('action') == $action->value ? 'selected' : '' }} value="{{$action}}">{{ \App\Enums\FinancialBlockActionEnum::TYPE_LABEL[$action->value] }}</option>
                                @endforeach
                            </select>
                        </div>
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
                                @error('final_installment_date')<small class="text-danger">{{$message}}</small>@enderror
                            </div>
                        </div>
                    </div>


                    <div class="col-md-6 mt-4">
                        <div class="form-group">
                            <label for="description">توضیحات</label>
                            <textarea class="form-control" name="description" id="description"
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
    <div class="card mb-6">
        <h5 class="card-header">محدودیت های اخیر کاربر</h5>
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th class="text-truncate">بلاک از</th>
                    <th class="text-truncate">زمان شروع</th>
                    <th class="text-truncate">زمان پایان محدودیت</th>
                    <th class="text-truncate">دلیل</th>
                    <th class="text-truncate">توضیحات</th>
                    <th class="text-truncate">عملیات</th>
                </tr>
                </thead>
                <tbody>
                @foreach($userFinancialBlockHistory as $block)

                    <tr class=" {{$block->isExpired() || $block->deleted_at?'table-danger':null}}">
                        <td class="text-truncate text-heading fw-medium">
                            {{$block->action->label()}}
                            <span class="badge bg-label-primary me-1">{{$block->action }}</span>

                        </td>
                        <td class="text-truncate">{{\App\Helpers\DateFormatter::convertToPersianDate($block->created_at,'H:i:s %Y-%m-%d')}}</td>
                        <td class="text-truncate">{{\App\Helpers\DateFormatter::convertToPersianDate($block->restricted_until,'H:i:s %Y-%m-%d')}}</td>
                        <td class="">{{$block->reason === \App\Enums\FinancialBlockReasonsEnum::ADMIN ? $block->reason->label() .' (#'. $block->admin_id . ')' : $block->reason->label() }}</td>
                        <td class="">{{$block->description}}</td>
                        <td class="">
                            @if(!$block->deleted_at)
                                <form action="{{route('admin.user.financial-block.deleteBlock',['user'=>$user,'financialBlock'=>$block->id])}}"
                                      method="post">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-icon btn-danger text-white">
                                        <i class="fa-light fa-trash-alt fa-lg"></i>
                                    </button>
                                </form>

                            @else
                                <span class="badge bg-label-danger">حذف شده</span>
                            @endif

                        </td>
                    </tr>

                @endforeach
                </tbody>


            </table>
        </div>
    </div>
    {{$userFinancialBlockHistory->links()}}

@endsection
@section('vendor-script')
    @vite([
            'resources/assets/vendor/js/forms-selects.js',
            'resources/assets/js/jalalidatepicker.js',
            'resources/assets/vendor/libs/cleavejs/cleave.js',
            'resources/assets/js/forms-extras.js',
          ])
@endsection
