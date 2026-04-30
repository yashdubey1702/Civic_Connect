<?php

$pageTitle = 'Accessibility Statement';
$pageKicker = 'Inclusive Access';
$pageIntro = 'CivicConnect aims to make civic reporting usable across devices and assistive technologies.';
$sections = [
    [
        'title' => 'Accessibility Commitment',
        'body' => [
            'The platform is designed with readable typography, keyboard-friendly navigation, clear labels, responsive layouts, and meaningful visual contrast.',
            'Ongoing improvements may be made to support more users and devices.'
        ]
    ],
    [
        'title' => 'Report an Accessibility Issue',
        'body' => [
            'If you face difficulty accessing any page or feature, please use the Feedback or Contact Us page and describe the issue, device, and browser.'
        ]
    ]
];
require __DIR__ . '/../app/Support/public_page_template.php';
