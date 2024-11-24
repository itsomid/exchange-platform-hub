<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>بیتکس روم</title>
        @vite(['resources/assets/scss/admin/app.scss'])
    </head>
    <body class="container">
        <div class="d-flex justify-content-center align-items-center flex-column my-5 ">
            <img  src="{{asset('assets/images/logos/logo_main_horiz.png')}}" class="img-fluid w-25" >

        </div>
       <div class="row">
           <div class="col-md-6 mt-3">
               <a href="{{route('login')}}">
                    <div class="card p-5 d-flex flex-row justify-content-evenly align-items-center">
                        <i class="fa-sharp fa-solid fa-user-tie fa-3x text-dark"></i>
                        <h3 class="mb-0">ورود به پنل مدیریت</h3>
                    </div>
               </a>
           </div>
           <div class="col-md-6 mt-3">
               <a href="https://mail.com">
                    <div class="card p-5 d-flex flex-row justify-content-evenly align-items-center">
                        <i class="fa-regular fa-mailbox text-dark fa-3x"></i>
                        <h3 class="mb-0">ایمیل ها</h3>
                    </div>
               </a>
           </div>
           <div class="col-md-6 mt-3">
               <a href="https://share.com">
                    <div class="card p-5 d-flex flex-row justify-content-evenly align-items-center">
                        <i class="fa-regular fa-cloud-arrow-up text-dark fa-3x"></i>
                        <h3 class="mb-0">فضای ابری</h3>
                    </div>
               </a>
           </div>
           <div class="col-md-6 mt-3">
               <a href="https://localhost">
                    <div class="card p-5 d-flex flex-row justify-content-evenly align-items-center">
                        <i class="fa-regular fa-screen-users fa-3x text-dark"></i>
                        <h3 class="mb-0">پنل کاربر</h3>
                    </div>
               </a>
           </div>
       </div>
    </body>
</html>
