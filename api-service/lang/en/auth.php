<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'register' => [
        'success' => 'You have successfully registered',
    ],
    'login' => [
        'encrypted_token_invalid' => 'The token you entered is invalid.',
        'success' => 'You have successfully logged in',
        '2fa-required' => 'Entering the two-factor authentication code is mandatory.',
    ],
    'email-verification' => [
        'success' => 'Your account has been successfully activated.',
    ],
    'forget-password' => [
        'send' => 'If your email is registered in our system, a password reset link has been sent. Please check your inbox.',
    ],
    'two-factor' => [
        'save-secret' => '2FA setup completed successfully.',
        'disable-success' => '2FA has been successfully disabled.',
    ],
    'too_many_attempts' => 'Too many attempts. Please try again later.',
];
