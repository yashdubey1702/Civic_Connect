<?php

$pageTitle = 'Contact Us';
$pageKicker = 'Support';
$pageIntro = 'Reach the CivicConnect Bhubaneswar team for platform support and civic service guidance.';
$sections = [
    [
        'title' => 'Municipal Support',
        'body' => [
            'For current helpline numbers, office hours, and official service contacts, please use Bhubaneswar Municipal Corporation citizen service channels.',
            'For emergencies, use the appropriate local emergency service number.'
        ]
    ],
    [
        'title' => 'Platform Questions',
        'body' => [
            'For login, tracking, reporting, or accessibility concerns, submit details through the Feedback page so the support team has enough context to review your issue.'
        ]
    ]
];
require __DIR__ . '/../app/Support/public_page_template.php';
