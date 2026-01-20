<?php

global $PAGE;

require(__DIR__ . '/../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/eabcprogramas/manage.php'));
$PAGE->set_title(get_string('pluginname', 'local_eabcprogramas'));

echo $OUTPUT->header();

if (is_siteadmin()) {
    $programas = new moodle_url('/local/eabcprogramas/programs.php');
    $diplomas = new moodle_url('/local/eabcprogramas/diplomas.php');
    $certificados = new moodle_url('/local/eabcprogramas/certificados.php');
    $programas_otorgados = new moodle_url('/local/eabcprogramas/programs/list_programs.php');
    echo '<h1> ' . get_string('manage', 'local_eabcprogramas') . '</h1>';
    echo ' 
    <div class="row">
        <div class="col-md-3">
            <a href="' . $programas . '" class="list-group-item">
            ' . get_string('programs', 'local_eabcprogramas') . '
            </a>
        </div>
        <div class="col-md-3">
            <a href="' . $programas_otorgados . '" class="list-group-item">
            ' . get_string('programas_otorgados', 'local_eabcprogramas') . '
            </a>
        </div>
        <div class="col-md-3">
            <a href="' . $diplomas . '" class="list-group-item">
            ' . get_string('diplomas_modelo', 'local_eabcprogramas') . '
            </a>
        </div>
        <div class="col-md-3">
            <a href="' . $certificados . '" class="list-group-item">
            ' . get_string('certificados_modelo', 'local_eabcprogramas') . '
            </a>
        </div>        
    </div>
    ';

    $diploma =  $DB->get_records('local_diplomas', ['status' => 1]);
    if (!$diploma) {
        $sin_diploma = "Debe haber un modelo de diploma activo";
        echo '
        <div class="alert alert-danger">
            <strong>Alerta!</strong> ' . $sin_diploma . '.
        </div>';
    }
    $certificado = $DB->get_records('local_certificados', ['status' => 1]);
    if (!$certificado) {
        $sin_certificado = "Debe haber un modelo de certificado activo";
        echo '
        <div class="alert alert-danger">
            <strong>Alerta!</strong> ' . $sin_certificado . '.
        </div>';
    }
}

echo $OUTPUT->footer();
