<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/wsreporter:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
        ],
    ],
];
