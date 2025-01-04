<?php

return [
    \App\Exceptions\Auth\InvalidVerificationTokenException::class => 'کد فعال سازی شما معتبر نمی باشد.',
    \App\Exceptions\Auth\Google2faSecretInvalidException::class => 'کد دومرحله ای شما معتبر نمی باشد.',
    \App\Exceptions\Auth\InvalidUsernameOrPasswordException::class => 'نام کاربری یا رمزعبور نادرست می باشد.',
    \App\Exceptions\User\OldPasswordNotMatchedNewPasswordException::class => 'رمز عبور قدیمی و رمز عبور جدید مطابقت ندارند.',
    \App\Exceptions\Auth\GoogleInvalidUserSecretKeyException::class => 'کد وارد شده معتبر نمی باشد.',
    \App\Exceptions\NotFoundException::class => 'صفحه مورد نظر وجود ندارد.',
    \App\Exceptions\User\ReferralCodeDoesNotBelongsToUser::class => 'کد معرف متعلق به کاربر نیست.',
    \App\Exceptions\V1\OTC\InsufficientBalanceException::class => 'موجودی کافی نیست.',
    \App\Exceptions\V1\Wallet\InternalWalletHasProblemException::class => 'سرویس کیف پول در حال حاضر در دسترس نیست. لطفا بعدا تلاش کنید.',
];
