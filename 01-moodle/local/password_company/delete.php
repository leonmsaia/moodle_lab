<?php

/**
 * @throws coding_exception
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/pubsub/lib.php');
require_once($CFG->dirroot . '/webservice/externallib.php');

/** @var moodle_page $PAGE */
global $PAGE, $OUTPUT, $DB, $USER;
$url = new moodle_url('/local/password_company/delete.php');
$url_password = new moodle_url('/local/password_company/view.php');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($url);
$id = required_param('id', PARAM_RAW);
require_login();
require_capability('local/password_company:password_company', $context);

echo $OUTPUT->header();

$DB->delete_records('local_password_company', array('companyid' => $id));
redirect($url_password, 'Clave segura borrada para esta empresa', 2);
echo $OUTPUT->footer();
