
<?php

return [
    'locales' => ['en', 'ar'],
    'default_locale' => 'en',
    'rtl_locales' => ['ar'],

    /*
    | Carbon locale used for date names, per interface language. Arabic uses
    | standard month names (e.g. أكتوبر). Lebanese-style month names would need
    | our own month list in lang/ar, which can be added later if wanted.
    */
    'carbon_locales' => [
        'en' => 'en',
        'ar' => 'ar',
    ],

    'invitation_lifetime_hours' => 72,
    'password_min_length' => 10,
    'display_timezone' => 'Asia/Beirut',
];
