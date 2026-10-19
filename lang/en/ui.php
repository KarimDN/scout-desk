<?php

return [

    'app_name' => 'ScoutDesk',

    'nav' => [
        'home' => 'Home',
        'language' => 'Language',
    ],

    // Always shown in their own language, whatever the current locale.
    'languages' => [
        'en' => 'English',
        'ar' => 'العربية',
    ],

    'demo' => [
        'title' => 'Language and layout check',
        'intro' => 'A temporary page for checking English, Arabic, right-to-left layout and mixed text. It is removed in Task 2.8.',
        'typed_text' => 'Typed text (direction follows the text)',
        'email' => 'Email address (always left-to-right)',
        'sent_to' => 'Invitation sent to',
        'today' => 'Today',
        'now' => 'Now (stored in UTC, shown in Beirut time)',
    ],

];
