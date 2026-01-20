<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_wsreporter_get_general_report_data' => [
        'classname' => 'local_wsreporter\external\general_report',
        'methodname' => 'get_data',
        'description' => 'Get report data.',
        'type' => 'read',
        'ajax' => true,
    ],
];

$services = [
    'WS Reporter latest 24h' => [
        'functions' => ['local_wsreporter_get_general_report_data'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];

