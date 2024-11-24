@extends('dashboard.layout.master')
@section('title', 'نشست های فعال '. $user->name)
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>عنوان</th>
                        <th>دستگاه</th>
                        <th>آدرس IP</th>
                        <th>token (hashed)</th>
                        <th>آخرین فعالیت</th>
                        <th>عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($tokens as $token)
                        <tr>
                            <td>{{$token->id}}</td>
                            <td>{{$token->name}}</td>
                            <td>{{$token->device}}</td>
                            <td>{{$token->ip}}</td>
                            <td>
                                <span id="monospace-text" style="font-family: monospace; cursor: pointer; user-select: text;">
                                    {{$token->token}}
                                </span>
                            </td>
                            <td>
                                {{\Morilog\Jalali\Jalalian::forge($token->last_used_at)->ago()}}
                            </td>
                            <td>
                                <form action="{{route('admin.user.token.revoke', ['user'=>$user->id, 'token' => $token->id])}}" method="post">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-danger">منقضی سازی</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
