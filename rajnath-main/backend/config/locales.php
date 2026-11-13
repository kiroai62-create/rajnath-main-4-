<?php

return [
    // Languages the website and API offer: English plus the eight most-spoken languages of India
    // (Census of India). Visitors choose one from the globe button in the header; English is the
    // default. Text that is not in lang/{code}.json is translated with the AI key from
    // Admin › AI assistant and kept in the site_translations table (Admin › Languages).
    'available' => [
        'en' => 'English', 'hi' => 'हिन्दी', 'bn' => 'বাংলা', 'mr' => 'मराठी', 'te' => 'తెలుగు',
        'ta' => 'தமிழ்', 'gu' => 'ગુજરાતી', 'ur' => 'اردو', 'kn' => 'ಕನ್ನಡ',
    ],

    // English names, used in AI prompts and the admin.
    'english_names' => [
        'en' => 'English', 'hi' => 'Hindi', 'bn' => 'Bengali', 'mr' => 'Marathi', 'te' => 'Telugu',
        'ta' => 'Tamil', 'gu' => 'Gujarati', 'ur' => 'Urdu', 'kn' => 'Kannada',
    ],

    // Written right to left.
    'rtl' => ['ur'],

    // Switched on unless the owner changes it in Admin › Languages (which takes priority).
    'enabled' => array_values(array_filter(array_map('trim', explode(',', (string) env('SITE_LOCALES', 'en,hi,bn,mr,te,ta,gu,ur,kn'))))),
];
