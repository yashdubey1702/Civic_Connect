<?php
$pageTitle = 'Privacy Policy';
$pageKicker = 'Data Protection';
$pageIntro = 'How CivicConnect handles information submitted through the citizen reporting platform.';
$sections = [
    [
        'title' => 'Information We Collect',
        'body' => [
            'CivicConnect may collect report details such as category, description, location coordinates, optional images, optional email, and account information for registered users.',
            'This information is used to process civic issue reports and support municipal follow-up.'
        ]
    ],
    [
        'title' => 'Use of Information',
        'body' => [
            'Submitted information is used for civic issue management, report tracking, operational review, and improving public service delivery.',
            'Personal information is not intended for public display except where necessary for authorized administrative workflows.'
        ]
    ],
    [
        'title' => 'Data Care',
        'body' => [
            'Reasonable safeguards are used to protect user information. Users should avoid submitting sensitive personal data in public issue descriptions.'
        ]
    ]
];
require __DIR__ . '/app/Support/public_page_template.php';
