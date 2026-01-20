<?php

require_once('../../config.php');


global $PAGE, $OUTPUT, $DB, $USER, $SESSION;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

//$context = context_system::instance();
require_login();
//require_capability('local/enrolcompany:enrol', $context, $USER->id);
$url = new moodle_url('/local/enrolcompany/index.php');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'local_enrolcompany'));
$PAGE->set_heading(get_string('pluginname', 'local_enrolcompany'));
$output = $PAGE->get_renderer('local_enrolcompany');

$stdfields = \local_enrolcompany\enrolcompany_utils::array_colums();
$columns = \local_enrolcompany\enrolcompany_utils::array_colums();
$prffields = array();

echo $OUTPUT->header();
echo \html_writer::tag('h1', get_string('pluginname', 'local_takeattendance'));

$mform = new \local_enrolcompany\uploadcsv_form($url, null);
$toform = new stdClass();

if ($mform->is_cancelled()) {
} else if ($formdata = $mform->get_data()) {

    $iid = csv_import_reader::get_new_iid('uploaduser');
    $cir = new csv_import_reader($iid, 'uploaduser');
    $mform = new \local_enrolcompany\uploadcsv_form($url, null);
    $content = $mform->get_file_content('userfile');

    $readcount = $cir->load_csv_content(
        $content,
        $formdata->encoding,
        $formdata->delimiter_name,
        '\local_enrolcompany\enrolcompany_utils::validate_user_upload_columns'
    );
    unset($content);

    if ($readcount === false) {
        echo $cir->get_error();
        notice('Error al procesar archivo por favor verifique el archivo .csv');
        die;
    } else if ($readcount == 0) {
        notice('Archivo vacio');
        die;
    }

    $userserrors  = 0;
    $process  = 0;
    $erroredusers = array();
    $createdusers = array();
    $dataerror = array();
    $data = [];
    $estadoInscripcion = null;
    $observacionInscripcion = '';
    $enrolid = null;
    // Init csv import helper.
    $cir->init();
    $linenum = 1; // Column header is first line.

    $returndata = [];
    while ($line = $cir->next()) {
        $linenum++;
        $errornum = 1;
        $inscripcion_elearning = new stdClass();
        $usernamedata = '';
        $data_row = \local_enrolcompany\enrolcompany_utils::process_data_row($line, $cir->get_columns());

        $user = $data_row['data'];
        $username = $data_row['username'][0] . '-' . $data_row['username'][1];
        $validation_row = \local_enrolcompany\enrolcompany_utils::validation_row($user, $username);
        $error = $validation_row['error'];
        $course = $DB->get_record('course', array('shortname' => $user['shortname']));
        $hoy = \local_pubsub\utils::date_utc();
        if(empty($error)){
            $registro = \local_pubsub\metodos_comunes::register_participant_elearning($username, $user, $course->id);
            if ($registro['estatus'] == 'nuevo'){
                $estadoInscripcion = 1;
                $observacionInscripcion = 'Registro exitoso';
            }else if ($registro['estatus'] == 'rematriculado'){
                $estadoInscripcion = 1;
                $observacionInscripcion = 'El Alumno fue Rematriculado';
            }else{
                $estadoInscripcion = 0;
                $observacionInscripcion = 'Alumno ya tiene el curso activo y con estado cursando';
            }
            $enrolid = $registro['enrolid']->id;
            \local_pubsub\back\inscripcion_elearning::insert_update_inscripciones_elearning_back($user, $course->id, $registro['newuserid']);

            $createdusers[] = array_merge($line, array($observacionInscripcion));

            $data = [
                "IdRegistroParticipante" => $user['ParticipanteIdRegistroParticipante'],
                "IdInscripcion" => $enrolid,
                "EstadoInscripcion" => $estadoInscripcion,
                "FechaInscripcion" => $hoy,
                "Observacion" => $observacionInscripcion
            ];

        } else {
            $errornum++;
            $userserrors++;
            $dataerror[] = get_string('missingfield', 'error', 'username');
            $erroredusers[] = array_merge($line, $validation_row['erroredusers']);

            $data = [
                "IdRegistroParticipante" => $user['ParticipanteIdRegistroParticipante'],
                "IdInscripcion" => $enrolid,
                "EstadoInscripcion" => $estadoInscripcion,
                "FechaInscripcion" => $hoy,
                "Observacion" => $observacionInscripcion
            ];
        }
        $returndata[] = \local_enrolcompany\lib::create_item_for_file($line, $data);
    }

    $file = \local_enrolcompany\lib::create_file($returndata);
    $cir->close();
    $cir->cleanup(true);

    if (!empty($erroredusers)) {
        echo get_string('erroredusers', 'block_comp_company_admin');
        $erroredtable = new html_table();
        $erroredtable->data[] = $cir->get_columns();
        foreach ($erroredusers as $erroreduser) {
            $erroredtable->data[] = $erroreduser;
        }
        echo html_writer::table($erroredtable);
    }

    if (!empty($createdusers)) {
        echo '<h1>Creados</h1>';
        $createdtable = new html_table();
        $createdtable->data[] = $cir->get_columns();
        foreach ($createdusers as $createduser) {
            $createdtable->data[] = $createduser;
            $process++;
        }
        echo html_writer::table($createdtable);
    }

    echo $OUTPUT->box_start('boxwidthnarrow boxaligncenter generalbox', 'uploadresults');
    echo '<p>';

    echo 'Filas procesadas' . ': ' . $process . '<br />';
    echo get_string('errors', 'tool_uploaduser') . ': ' . $userserrors . '<br />';
    echo $OUTPUT->box_end();
    echo $OUTPUT->continue_button($url);
    echo $OUTPUT->footer();
    die;

}
$mform->display();

echo $OUTPUT->footer();
die;
