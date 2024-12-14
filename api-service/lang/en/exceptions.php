<?php

return [
    \App\Exceptions\Auth\InvalidVerificationTokenException::class => 'The activation code you entered is not valid.',
    \App\Exceptions\Auth\Google2faSecretInvalidException::class => 'The entered two-factor authentication code is not valid.',
    \App\Exceptions\Auth\InvalidUsernameOrPasswordException::class => 'These credentials do not match our records.',
    \App\Exceptions\User\OldPasswordNotMatchedNewPasswordException::class => 'The old password and new password do not match.',
    \App\Exceptions\Auth\GoogleInvalidUserSecretKeyException::class => 'The secret key you entered is invalid.',
    \App\Exceptions\NotFoundException::class => 'No results found.',
    \App\Exceptions\User\ReferralCodeDoesNotBelongsToUser::class => 'The referral code does not belong to the user.',
];
