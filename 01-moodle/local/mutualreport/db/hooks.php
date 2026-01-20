<?php

$callbacks = [
    [
        'hook' => \core\hook\output\before_http_headers::class,
        'callback' => \local_mutualreport\hook\before_http_headers::class . '::render_menu',
    ],
];
