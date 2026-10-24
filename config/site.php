<?php

/*
 * Who runs the site and how to reach them. Shown in the footer and on the
 * privacy and affiliate pages. Anything left empty is simply not shown.
 */
return [
    'owner' => env('SITE_OWNER', 'JavaSunrise'),
    'phone' => env('SITE_PHONE'),
    'address' => env('SITE_ADDRESS'),

    'social' => [
        'Facebook' => env('SITE_FACEBOOK_URL'),
        'Instagram' => env('SITE_INSTAGRAM_URL'),
        'X' => env('SITE_X_URL'),
    ],

    // Shown at the top of the privacy policy; change it when the text changes
    'privacy_updated' => '2026-10-24',
];
