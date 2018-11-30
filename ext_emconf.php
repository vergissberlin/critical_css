<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Critical CSS rendering',
    'description' => 'Renders critical css inside the page for faster displaying',
    'category' => 'fe',
    'author' => 'Marco Pfeiffer',
    'author_email' => 'git@marco.zone',
    'author_company' => 'hauptsache.net',
    'constraints' => [
        'depends' => [],
        'conflicts' => [],
        'suggests' => [],
    ],
    'state' => 'excludeFromUpdates',
    'clearCacheOnLoad' => true,
];
