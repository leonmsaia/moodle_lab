<?php

require_once('../../config.php');


global $PAGE, $OUTPUT, $DB, $USER, $SESSION;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

$context = context_system::instance();
require_login();
require_capability('local/enrolcompany:enrol', $context, $USER->id);
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
echo \html_writer::tag('h1', get_string('pluginname', 'local_enrolcompany'));

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
        $username = '';
        $estadoInscripcion = 0;
        $observacionInscripcion = '';
        $data_row = \local_enrolcompany\enrolcompany_utils::process_data_row($line, $cir->get_columns());
        
        $user = $data_row['data'];
        $parse_document = \local_enrolcompany\enrolcompany_utils::parse_document_int($user['ParticipanteTipoDocumento']);
        if ($parse_document == 1){
            if (!empty($user['ParticipanteRUT'])){
                $username = strtolower(trim($data_row['username'][0]) . "-" . trim($data_row['username'][1]));
            }          
        } else if ($parse_document == 100) {
            $username = strtolower(trim($user['ParticipantePasaporte']));           
        } 
        
        $validation_row = \local_enrolcompany\enrolcompany_utils::validation_row($user, $username);
        $error = $validation_row['error'];
        
        try {
            if(empty($error)){
                $curso_back = $DB->get_records('curso_back', ['codigocurso' => $user['CodigoCurso']]);
                $curso_back = end($curso_back);
                $course = $DB->get_record('course', array('id' => $curso_back->id_curso_moodle));
                $user['ParticipanteProductId'] = $curso_back->productoid;
                $hoy = \local_pubsub\utils::date_utc();
                $user['ParticipanteTipoDocumento'] = $parse_document;
                $user['ResponsableTipoDocumento'] =  \local_enrolcompany\enrolcompany_utils::parse_document_int($user['ResponsableTipoDocumento']);

                $registro = \local_enrolcompany\enrolcompany_utils::register_participant_elearning($username, $user, $course->id);            
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
                \local_enrolcompany\enrolcompany_utils::insert_update_inscripciones_elearning_back($user, $course->id, $registro['newuserid']);
                $createdusers[] = array_merge($line, array($observacionInscripcion));
                $data = [
                    "GUID Inscripción Proveedor" => $enrolid,
                    "Fecha Inscripción Proveedor" => $hoy,
                    "Observaciones Inscripcion Externa" => $observacionInscripcion,
                    "Estado Inscripción Proveedor" => $estadoInscripcion
                ];

            } else {
                $errornum++;
                $userserrors++;
                $dataerror[] = get_string('missingfield', 'error', 'username');
                $erroredusers[] = array_merge($line, $validation_row['erroredusers']);

                $data = [
                    "GUID Inscripción Proveedor" => '',
                    "Fecha Inscripción Proveedor" => '',
                    "Observaciones Inscripcion Externa" => 'ERROR: Alguno de estos campos requeridos no estan presentes',
                    "Estado Inscripción Proveedor" => $estadoInscripcion
                ];
                $event = \local_enrolcompany\event\enrolcompany_csv::create(
                    array(
                        'context' => \context_system::instance(),
                        'other' => array(
                            'rut' => $username,
                            'error' => 'ERROR: Alguno de estos campos requeridos no estan presentes: ' . implode(',', $validation_row['erroredusers'])
                        ),
                    )
                );
                $event->trigger();
            }
        } catch (Exception $ex) {
            //catch
            $event = \local_enrolcompany\event\enrolcompany_csv::create(
                array(
                    'context' => \context_system::instance(),
                    'other' => array(
                       'error' => $ex->getMessage(),
                    ),
                )
            );
            $event->trigger();
        }

        $returndata[] = \local_enrolcompany\lib::create_item_for_file($line, $data, $cir->get_columns());
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
    
    if(!empty($file)){
        $fileurl = \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            true
        );
        echo \html_writer::tag('a', 'Descargar archivo', ['href' => $fileurl->out(), 'class' => 'btn btn-success']);
    }
    
    echo $OUTPUT->box_end();
    echo $OUTPUT->continue_button($url);
    echo $OUTPUT->footer();
    die;
    
    
}
$mform->display();

echo $OUTPUT->footer();
die;
