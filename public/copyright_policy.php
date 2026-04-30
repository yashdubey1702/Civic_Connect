<?php

$pageTitle = 'Copyright Policy';
$pageKicker = 'Content Use';
$pageIntro = 'Terms for use of CivicConnect public content and materials.';
$sections = [
    [
        'title' => 'Content Ownership',
        'body' => [
            'CivicConnect content is maintained for public civic service use. Platform text, interface elements, and civic information should be reused responsibly with clear attribution where applicable.',
            'User-submitted report content remains tied to the report workflow and should not be republished in a misleading or harmful manner.'
        ]
    ],
    [
        'title' => 'Permitted Use',
        'body' => [
            'Public information may be referenced for awareness, research, and civic participation, provided the context is preserved and no unauthorized claim of ownership is made.'
        ]
    ]
];
require __DIR__ . '/../app/Support/public_page_template.php';
