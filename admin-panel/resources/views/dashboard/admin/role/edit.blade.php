@extends('dashboard.layout.master')
@section('title', 'ویرایش نقش ها')
@section('content')
    <div class="row">
        <div class="card">
            <div class="card-body">
                <div class="card-title header-elements">
                    <h5 class="m-0 me-2">
                        ویرایش نقش های
                      {{$user->fullname()}}
                    </h5>
                </div>
                <form action="{{route('admin.role.user.update' , $user)}}" method="POST" >
                    <div class="row">
                        @csrf
                        @method('PATCH')
                            <div class="row">
                                @foreach($roles as  $role)
                                    <div class="col-md-4">
                                        <div class="form-check my-3 d-block">
                                            <label class="form-check-label">
                                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{$role->id}}"
                                                    {{in_array($role->id , $user->roles->pluck('id')->toArray() ) ? 'checked' : ''}}>
                                                {{$role->name}} | {{$role->persian_name}}
                                                <span class="form-check-sign">
                                        <span class="check">
                                        </span>
                                    </span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="col-md-12">
                                    <div class="text-center mt-5">
                                        <button class=" btn btn-primary">
                                            <i class="fas fa-edit mx-2"></i>
                                            ویرایش نقش کاربر
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @error('roles') <small class="form-text text-danger">{{ $message }}</small>@enderror
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
