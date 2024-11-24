<?php

return [
    \App\Exceptions\Auth\InvalidVerificationTokenException::class => 'کد فعال سازی شما معتبر نمی باشد.',
    \App\Exceptions\Auth\Google2faSecretInvalidException::class => 'کد دومرحله ای شما معتبر نمی باشد.',
    \App\Exceptions\Auth\InvalidUsernameOrPasswordException::class => 'نام کاربری یا رمزعبور نادرست می باشد.',
];
