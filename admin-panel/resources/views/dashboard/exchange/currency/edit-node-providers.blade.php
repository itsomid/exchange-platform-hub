@extends('dashboard.exchange.currency.layout.master')
@section('title', 'ویرایش Node Provider')
@section('currency-body')

    <div class="row">
        <div class="col-md-12">
            <div class="card">

                <div class="card-body">

                    <form action="{{route('admin.currency.nodeprovider.update',['currency'=>$currency])}}"
                          method="post">
                        @method('PATCH')
                        @csrf
                        @foreach($currency->nodeProviders as $node)
                            <h5> اطلاعات نود {{$node->name}}</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label" for="chain">نام Node</label>
                                        <input name="nodes[{{$node->id}}][name]"
                                               id="name_{{$node->id}}"
                                               class="form-control"
                                               placeholder="نام را وارد کنید."
                                               value="{{$node->name}}"
                                        >
                                        @error('name')
                                        <small class="text-danger">{{$message}}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-5">
                                <div class="col-md-6 col-xl-4">
                                    <div class="form-group">
                                        <label class="form-label" for="base_url_{{$node->base_url}}">آدرس پایه</label>
                                        <input name="nodes[{{$node->id}}][base_url]"
                                               id="base_url_{{$node->id}}" class="form-control"
                                               placeholder="آدرس را وارد کنید." value="{{$node->base_url}}" required>
                                        @error('min_deposit_amount')
                                        <small class="text-danger">{{$message}}</small>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-4">
                                    <div class="form-group">
                                        <label class="form-label" for="api_key{{$node->api_key}}">API KEY</label>
                                        <input name="nodes[{{$node->id}}][api_key]"
                                               id="api_key_{{$node->id}}" class="form-control"
                                               placeholder="API KEY را وارد کنید." value="{{$node->api_key}}"
                                               required>
                                        @error('min_withdraw_amount')
                                        <small class="text-danger">{{$message}}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-5">

                                <div class="col-md-6 col-xl-2">
                                    <div class="form-group">
                                        <label class="form-label" for="priority_{{$node->id}}">اولویت</label>
                                        <input type="number" name="nodes[{{$node->id}}][priority]"
                                               id="priority_{{$node->id}}" class="form-control"
                                               placeholder="اولویت را وارد کنید" value="{{$node->priority}}" required>
                                        @error('safe_confirmations')
                                        <small class="text-danger">{{$message}}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mt-5">
                                <label class="switch  switch-lg">
                                    <input type="checkbox" class="switch-input" name="nodes[{{$node->id}}][is_active]" value="1" {{ $node->is_active ? 'checked' : '' }} />
                                    <span class="switch-toggle-slider"></span>
                                    <span class="switch-label">وضعیت (فعال/غیر فعال)</span>
                                </label>
                            </div>
                            <hr class="my-6 mx-n4">
                        @endforeach
                        <div class=" d-flex justify-content-start mt-5">

                                <button class="btn btn-primary ">
                                    <i class="fa fa-save mx-2"></i>
                                    ذخیره
                                </button>

                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

@endsection


