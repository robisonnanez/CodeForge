<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'lowercase_usernames' => true,
    'home' => '/dashboard',
    'prefix' => '',
    'domain' => null,
    'middleware' => ['web'],
    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],
    'views' => true,
    'features' => array_values(array_filter([
        config('codeforge.registration_enabled') ? Features::registration() : null,
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ])),
];
