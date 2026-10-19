<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported interface languages
    |--------------------------------------------------------------------------
    | English and Arabic only. Anything else is ignored by the locale logic.
    */
    'locales' => ['en', 'ar'],

    /*
    | Locales written right-to-left. Must be a subset of 'locales'.
    */
    'rtl_locales' => ['ar'],

    /*
    | How long an invitation or password-reset link stays valid.
    */
    'invitation_lifetime_hours' => 72,

    /*
    | Minimum password length for accounts created through ScoutDesk.
    */
    'password_min_length' => 10,

    /*
    | Timestamps are stored in UTC and displayed in this timezone.
    */
    'display_timezone' => 'Asia/Beirut',

];