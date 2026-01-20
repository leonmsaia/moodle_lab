<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
/** @var moodle_database $DB */
global $CFG, $DB;

$courses = $DB->get_records_select('course', 'shortname like ?', array('ModeloFrontSD%'));

foreach ($courses as $course) {
	delete_course($course);
}
