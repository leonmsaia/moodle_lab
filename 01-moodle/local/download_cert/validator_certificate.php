<?php

use local_download_cert\form\search_form;

include_once('../../config.php');
require_once('classes/search_form.php');
/**
 * @var moodle_page $PAGE
 * @var core_renderer $OUTPUT;
 */
global $PAGE, $OUTPUT, $DB;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/download_cert/validator_certificate.php'));
$PAGE->set_title('Validador de diplomas');
$PAGE->set_heading('Validador de diplomas');
echo $OUTPUT->header();

$mform = new search_form();
if ($formdata = $mform->get_data()) {

    $sql = "SELECT * FROM {download_cert_code} WHERE code_certificate = :code ";

    $diploma = $DB->get_record_sql($sql, array('code' => $formdata->diploma));

    if (!$diploma){
        echo "<span class='d-flex justify-content-center'> El código de diploma: &nbsp;<p class='text-dark'>".$formdata->diploma. "</p> &nbsp; <p class='text-danger'>No se encuentra registrado </p>. <br><br></b> </span>";
    }else{
        echo "<span class='d-flex justify-content-center'> El diploma: ".$formdata->diploma. " es válido <a class='text-success ' href='validator_download.php?codigo=".$diploma->code_certificate."' target='blank'>, haga click aquí para descargarlo</a></span><br><br>";
    }
}

$mform->display();

echo $OUTPUT->footer();