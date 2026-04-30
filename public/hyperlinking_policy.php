<?php

$pageTitle = 'Hyperlinking Policy';
$pageKicker = 'Linking Guidelines';
$pageIntro = 'Guidelines for links to and from CivicConnect Bhubaneswar.';
$sections = [
    [
        'title' => 'Links to External Websites',
        'body' => [
            'CivicConnect may provide links to official or relevant public service websites for user convenience.',
            'External websites are governed by their own policies, and CivicConnect is not responsible for their content or availability.'
        ]
    ],
    [
        'title' => 'Linking to CivicConnect',
        'body' => [
            'Other websites may link to public CivicConnect pages as long as the link does not misrepresent official endorsement or alter the meaning of published content.'
        ]
    ]
];
require __DIR__ . '/../app/Support/public_page_template.php';
