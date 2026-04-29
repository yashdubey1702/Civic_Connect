<?php
$pageTitle = 'Help';
$pageKicker = 'Citizen Guidance';
$pageIntro = 'Quick help for reporting and tracking civic issues through CivicConnect.';
$sections = [
    [
        'title' => 'Submit a Report',
        'body' => [
            'Open the map, choose a location inside Bhubaneswar city limits, sign in, select an issue category, and submit a description with an optional photo.',
            'Registered citizens can track their own report history from the citizen dashboard.'
        ]
    ],
    [
        'title' => 'Track a Report',
        'body' => [
            'Use the tracking box on the home page if you have a tracking token. The current status will be shown without opening the full dashboard.'
        ]
    ],
    [
        'title' => 'Need More Support',
        'body' => [
            'Use Contact Us for official support channels or Feedback to share platform suggestions.'
        ]
    ]
];
require __DIR__ . '/app/Support/public_page_template.php';
