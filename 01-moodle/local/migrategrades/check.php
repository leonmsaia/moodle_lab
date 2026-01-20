<?php

require_once('../../config.php');

global $PAGE, $OUTPUT, $CFG, $USER;

require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('local/migrategrades:upload', $context);

$url = new moodle_url('/local/migrategrades/check.php');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('checktitle', 'local_migrategrades'));
$PAGE->set_heading(get_string('pluginname', 'local_migrategrades'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('checktitle', 'local_migrategrades'));

echo $OUTPUT->single_button(new moodle_url('/local/migrategrades/index.php'), get_string('backtoupload', 'local_migrategrades'));

echo $OUTPUT->box_start('generalbox');
try {
    $olddb = new \local_migrategrades\old_moodle_db();
    $olddb->ping();
    $olddb->close();

    echo $OUTPUT->notification(get_string('check_ok', 'local_migrategrades'), 'notifysuccess');
} catch (Throwable $e) {
    echo $OUTPUT->notification(get_string('check_fail', 'local_migrategrades', $e->getMessage()), 'notifyproblem');
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
