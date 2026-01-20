<?php

define('AJAX_SCRIPT', true);

include_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$action = required_param('action', PARAM_RAW);
$courseid = required_param('courseid', PARAM_RAW);
global $USER, $DB;

$params = [
    'userid' => $USER->id,
    'courseid' => $courseid
];

/** @var mysqli_native_moodle_database $DB */
$exist = $DB->record_exists('local_checkconfig', $params);

if(!$exist) {
    $DB->insert_record('local_checkconfig', (object)$params);
}

echo json_encode(["course" => $courseid, 'user' => $USER->id]);

