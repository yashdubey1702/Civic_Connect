<?php

$pageTitle = 'Archives';
$pageKicker = 'Public Information';
$pageIntro = 'Find important CivicConnect public information and historical notices in one place.';
$sections = [
    [
        'title' => 'Archived Civic Information',
        'body' => [
            'This archive page is reserved for older public notices, platform updates, civic service announcements, and published summaries related to CivicConnect Bhubaneswar.',
            'Current reporting, tracking, and map services remain available from the home page.'
        ]
    ],
    [
        'title' => 'Availability',
        'body' => [
            'Archived material may be periodically reviewed to keep public information relevant, concise, and accessible.'
        ]
    ]
];
require __DIR__ . '/../app/Support/public_page_template.php';
