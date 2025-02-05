<?php

return [

    'reset-password-link' => env('RESET_PASSWORD_LINK', 'http://localhost/auth/reset-password?token=%s'),
    'email-verification-link' => env('EMAIL_VERIFICATION_LINK', 'http://localhost/auth/email-verification?token=%s'),
    'reset-two-factor-verification-link' => env('RESET_TWO_FACTOR_LINK', 'http://localhost/auth/reset-two-factor?token=%s'),
];
