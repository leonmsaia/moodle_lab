<?php
defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    'message' => [
        'capability' => 'mod/eabcattendance:receivetasknotifications',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED,
            'email' => MESSAGE_PERMITTED,
        ],
        'forced' => [
            'popup' => MESSAGE_PERMITTED,
            'email' => MESSAGE_PERMITTED,
        ],
    ],
];
