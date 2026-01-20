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
$url = new moodle_url('/local/password_company/view.php');
$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url($url);
$download = optional_param('download', '', PARAM_ALPHA);
require_login();
require_capability('local/password_company:password_company', $context);

$table = new \local_password_company\table\custom_table('uniqueid');
$table->is_downloading($download, $download);

$select = 'c.*, p.timemodified as timecreate_secure';
$from = '{local_password_company} AS p
    join {company} as c on p.companyid = c.id
';

$where = ' ' ;
                
if ($table->is_downloading($download, $download)) {
    $table->set_sql($select, $from, '1=1'.$where);
    $table->define_baseurl($url);
    $table->out(0, true);
    exit;
}


echo $OUTPUT->header();
echo "<h1>Empresas con contraseña segura</h1>";

$mformadd = new \local_password_company\form\add_secure_password();
$mformadd->display();

if ($mformadd->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($mformadd = $mformadd->get_data()) {
    \local_password_company\utils::save_secure_password($mformadd->company);
} 

$table->set_sql($select, $from, '1=1'.$where);
$table->define_baseurl($url);
$table->out(10, true);


echo $OUTPUT->footer();
