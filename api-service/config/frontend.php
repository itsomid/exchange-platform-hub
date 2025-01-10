<?php

return [

    'reset-password-link' => env('RESET_PASSWORD_LINK', 'http://localhost/reset-password?token=%s'),
    'email-verification-link' => env('RESET_PASSWORD_LINK', 'http://localhost/email-verification?token=%s'),
    'reset-two-factor-verification-link' => env('RESET_TWO_FACTOR_LINK', 'http://localhost/reset-two-factor?token=%s'),
];
