<?php

return [
    'initial_admin' => [
        'name' => env('ADMIN_NAME') ?: 'YouthHub Administrator',
        'email' => env('ADMIN_EMAIL') ?: 'admin@youthhubpartner.test',
        'password' => env('ADMIN_PASSWORD'),
    ],
];
