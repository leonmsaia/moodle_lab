<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_http_headers::class,
        'callback' => [\local_companymessage\hook\before_http_headers_handler::class, 'show_message'],
        'priority' => 1000,
    ],
];