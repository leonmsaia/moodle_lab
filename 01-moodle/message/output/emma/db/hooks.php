<?php

$callbacks = [
    [
        'hook' => \core\hook\output\before_http_headers::class,
        'callback' => \message_emma\hook\before_http_headers::class . '::render_menu',
    ],
];
