<?php

return [
    \App\Exceptions\Auth\InvalidVerificationTokenException::class => 'The activation code you entered is not valid.',
    \App\Exceptions\Auth\Google2faSecretInvalidException::class => 'The entered two-factor authentication code is not valid.',
    \App\Exceptions\Auth\InvalidUsernameOrPasswordException::class => 'These credentials do not match our records.',
];
