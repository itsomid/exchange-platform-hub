<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Accounting panel SSO
    |--------------------------------------------------------------------------
    |
    | The admin sidebar links to an internal route that signs the logged-in
    | admin's email and redirects to the accounting panel login URL.
    |
    */

    'url' => env('ACCOUNTING_SSO_URL', 'https://acct.bitexroom.com'),

    'login_path' => env('ACCOUNTING_SSO_LOGIN_PATH', '/sso/login'),

    'secret' => env('ACCOUNTING_SSO_SECRET'),

];
