@extends('dashboard.user.layout.master')
@section('title','ویرایش رمز عبور')
@section('user-body')

    <!-- Two-steps verification -->
    <div class="card mb-6">
        <div class="card-header">
            <h5 class="mb-6"><i class="fa fa-firewall"></i>
                تنظیمات امنیتی کاربر
            </h5>
        </div>
        <div class="card-body d-flex">

            <a href="{{route('admin.user.reset-password-email',['user'=>$user->id])}}" class="btn btn-primary me-3">
                ارسال لینک بازیابی رمز عبور
            </a>

            <form action="{{ route('admin.users.disable-user-two-factor', ['user'=>$user->id]) }}" method="post">
                @csrf
                <button class="btn btn-warning" @if(!$user->twoFAStatus()) disabled @endif>
                    غیرفعال سازی ورود دومرحله ایی
                    @if(!$user->twoFAStatus())
                        <small class="mx-2">(ورود دو مرحله ایی غیرفعال است)</small>
                    @endif

                </button>
            </form>

        </div>
    </div>

    <div class="card mb-6">
        <h5 class="card-header">دستگاه های اخیر کاربر</h5>
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th class="text-truncate">مرورگر</th>
                    <th class="text-truncate">دستگاه</th>
                    <th class="text-truncate">مکان</th>
                    <th class="text-truncate">ip</th>
                    <th class="text-truncate">آخرین فعالیت</th>
                </tr>
                </thead>
                <tbody>
                    @foreach($tokens as $token)
                        @php
                            $agent->setUserAgent($token->user_agent);
                        @endphp
                        <tr class="{{$token->expires_at > now() ? 'table-success': 'table-danger'}}">
                            <td class="text-truncate text-heading fw-medium bf">
                                <i class="fa-brands
                                    <x-os-fa-icon :platform=" $agent->platform()"></x-os-fa-icon>
                                    me-2">
                                </i>
                                {{$agent->browser()}} On {{  $agent->platform() }}
                            </td>
                            <td class="text-truncate">{{  $token->user_agent }}</td>
                            <td class="text-truncate">{{ $token->ip ? App\Helpers\LocationFinder::getCountryAndCity($token->ip): 'N\A' }}</td>
                            <td class="text-truncate">{{ $token->ip }}</td>
                            <td class="text-truncate">
                                <span>{{\App\Helpers\DateFormatter::convertToPersianDate($token->last_used_at,'H:i:s %Y/%m/%d')}}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    {{$tokens->appends(request()->all())->links()}}

@endsection
