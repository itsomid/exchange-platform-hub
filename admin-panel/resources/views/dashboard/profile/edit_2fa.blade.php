@extends('dashboard.profile.layout.master')
@section('profile-body')

        <div class="card">
            <div class="row">
                <div class="col-md-12 text-center">
                    <form action="{{ route('two-factor.enable') }}" method="post">
                        @csrf
                        @if(auth()->user()->two_factor_secret)
                            @method('DELETE')
                            <div class="alert alert-success m-3">
                                <h5>در حال حاضر احراز هویت دو مرحله ای برای شما فعال است</h5>
                                <p>⚠ جهت غیر فعالسازی کلیک کنید </p>
                            </div>
                            <div>
                                {!!  auth()->user()->twoFactorQrCodeSvg() !!}
                            </div>

                            <small class="d-block mt-4">
                               بارکد بالا را در توسط google authenticator اسکن کنید.
                            </small>

                            <a class="btn btn-dark" href="https://apps.apple.com/us/app/google-authenticator/id388497605">
                                <span class=" text-white d-inline mt-2"> Download </span>
                                <i class="fa-brands fa-apple fa-2x mx-2"></i>
                            </a>
                            <a class="btn btn-success" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en&gl=US">
                                <span class=" text-white d-inline mt-2"> Download </span>
                                <i class="fa-brands fa-google-play fa-2x mx-2"></i>
                            </a>
                            <hr>


                            <button class="btn btn-danger my-3">
                                غیر فعالسازی احراز هویت دو مرحله ای
                            </button>
                        @else
                            <div class="alert alert-warning m-3">
                                <h5>در حال حاضر احراز هویت دو مرحله ای برای شما فعال نیست</h5>
                                <p>⚠ جهت فعالسازی کلیک کنید </p>
                            </div>

                            <button class="btn btn-success my-3">
                                 فعالسازی احراز هویت دو مرحله ای
                            </button>
                        @endif
                    </form>
                </div>
            </div>

        </div>

@endsection
