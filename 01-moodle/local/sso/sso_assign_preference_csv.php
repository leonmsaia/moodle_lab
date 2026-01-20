<?php
require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once('lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

require_login();

// $param = required_param('param', PARAM_RAW);
$status = optional_param('status', '2', PARAM_TEXT);

$context = context_system::instance();
require_capability('moodle/site:config', $context);

// use local_sso\login;


class uploadcsv_form extends \moodleform {
    public function definition () {
        global $CFG, $USER;

        $mform =& $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('upload'));

        $mform->addElement('filepicker', 'userfile', get_string('file'), null, array('accepted_types' => array('.csv')));
        $mform->addRule('userfile', null, 'required');

        $choices = \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        $mform->setDefault('delimiter_name', 'semicolon');

        $choices = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');
        $mform->addElement('text', 'migrado', 'Migrado');
        $mform->setType('migrado', PARAM_TEXT);
        $mform->setDefault('migrado', 'migrado');

        $mform->addElement('text', 'migradovalue', 'Migrado Value');
        $mform->setType('migradovalue', PARAM_TEXT);
        $mform->setDefault('migradovalue', '2');




        $this->add_action_buttons(false, get_string('uploadusers', 'tool_uploaduser'));
        
    }
}


$login = new \local_sso\login();

$url = new moodle_url('/local/sso/sso_assign_preference_csv.php');
$PAGE->set_url($url);
$title = "Marcar migrado";
$PAGE->set_title($title);
$PAGE->set_heading($title);
echo $OUTPUT->header();

echo \html_writer::tag('h1', $title);
echo '<br>' . \html_writer::link(new moodle_url('/local/sso/examples/migrados.csv'), 'Ejemplo CSV', ['target' => '_blank']) . '<br><br><br>';
$mform = new uploadcsv_form($url, null);
$toform = new stdClass();

if ($mform->is_cancelled()) {
} else if ($formdata = $mform->get_data()) {
    set_time_limit(0);
    \core_php_time_limit::raise();
    raise_memory_limit(MEMORY_EXTRA);



    // $iid = csv_import_reader::get_new_iid('uploaduser');
    // $cir = new csv_import_reader($iid, 'uploaduser');
    // $content = $mform->get_file_content('userfile');
    
    // $readcount = $cir->load_csv_content(
    //     $content,
    //     $formdata->encoding,
    //     $formdata->delimiter_name
    //     //,'\local_enrolcompany\enrolcompany_utils::validate_user_upload_columns'
    // );
    // unset($content);

    // if ($readcount === false) {
    //     echo $cir->get_error();
    //     notice('Error al procesar archivo por favor verifique el archivo .csv');
    //     die;
    // } else if ($readcount == 0) {
    //     notice('Archivo vacio');
    //     die;
    // } 
    
    // $userserrors  = 0;
    // $erroredusers = array();
    // $createdusers = array();
    // $dataerror = array();
    // $data = [];
    
    // $enrolid = null;
    // // Init csv import helper.
    // $cir->init();
    // $actualizados = 0;
    // $errornum = 0;
    // $procesados = 0;
    // $linenum = 1; // Column header is first line.

    // $returndata = [];
    // while ($line = $cir->next()) {
    //     $linenum++;
    //     // $errornum = 1;
    //     $data_row = $login->process_data_row_migrado($line, $cir->get_columns());
    //     $data_row_set = $data_row['data'];
    //     // $validation_row = \local_mutual\front\utils::validation_row($data_row_set);
    //     // $error = $validation_row['error'];

    //     try {
    //         $get_user = $DB->get_record("user", ['username' => $data_row_set['username']]);

    //         if (!empty($get_user)) {
    //             $actualizados++;
    //             set_user_preference($formdata->migrado, $formdata->migradovalue, $get_user);
    //         } else {
    //             $data = [];
    //             $data['username'] = $data_row_set['username'];
    //             $data['password'] = $data_row_set['password'];
    //             $data['firstname'] = $data_row_set['firstname'];
    //             $data['lastname'] = $data_row_set['lastname'];
    //             $data['email'] = $data_row_set['email'];
               

    //             // Create user.
    //             $newuserObj = $login->create_user($data_row_set);

    //             // Set user preference.
    //             set_user_preference($formdata->migrado, $formdata->migradovalue, $newuserObj);

    //             $array_aditional_files = array(
    //                 "empresarut"            => (string) $data_row_set['profile_field_empresarut'],
    //                 "empresarazonsocial"    => (string) $data_row_set['profile_field_empresarazonsocial'],
    //                 "empresacontrato"       => (string) $data_row_set['profile_field_empresacontrato'],
    //             ); 


    //             //Guardo en campos personalizados del usuario
    //             profile_save_custom_fields($newuserObj->id, $array_aditional_files);

                
    //             // Add to created users.
    //             // $createdusers[] = $newuserid;
    //             $procesados++;
    //         }
    //     } catch (Exception $ex) {
    //        $errornum++;
    //             $userserrors++;
    //             $dataerror[] = 'faltan campos en el la linea ' . $ex->getMessage() . ' en la linea ' . $linenum;
    //     }


        
    // }

    // $cir->close();
    // $cir->cleanup(true);
    
    $draftitemid = $formdata->userfile; // <-- este es el correcto
    $usercontext = context_user::instance($USER->id);
    $fs = get_file_storage();

    $files = $fs->get_area_files(
        $usercontext->id,
        'user',
        'draft',
        $draftitemid,
        'id',
        false // skip directories
    );

    if (empty($files)) {
        print_error('No se encontró el archivo subido.');
    }

    foreach ($files as $file) {
       $filename = clean_param($file->get_filename(), PARAM_FILE);
        $temppath = 'migrado_' . time() . '_' . str_replace(' ', '', $filename);
        file_put_contents($temppath, $file->get_content());

       // Ejecutar script CLI
         $cmd = "php {$CFG->dirroot}/local/sso/cli/process_csv_migrado.php --file={$CFG->dirroot}/local/sso/$temppath --encoding={$formdata->encoding} --delimiter={$formdata->delimiter_name} --migrado={$formdata->migrado} --migradovalue={$formdata->migradovalue}";

        shell_exec($cmd . " > /dev/null 2>&1 &");
        echo '<pre>' . ($cmd) . '</pre><br>';
        // echo '<pre>' . htmlspecialchars($output) . '</pre>';
        //  if (file_exists($temppath)) {
        //     unlink($temppath);
        // }
    }

    // echo 'Error: ' . $errornum . '<br>';
    // echo 'Error de usuarios: ' . print_r($dataerror, true) . '<br>';
    // echo 'Procesados: ' . $procesados . '<br>';
    // echo 'Actualizados: ' . $actualizados . '<br>';

    
    
    /* echo $OUTPUT->box_end(); */
    echo $OUTPUT->continue_button($url);
    echo $OUTPUT->footer();
    die;
    
    
}
$mform->display();

echo $OUTPUT->footer();
die;