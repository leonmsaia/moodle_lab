<?php

include_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

$context = context_system::instance();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once('lib.php');

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

        $this->add_action_buttons(false, get_string('uploadusers', 'tool_uploaduser'));
    }
}




$url = new moodle_url('/local/mutual/completion_quiz.php');
$PAGE->set_url($url);
$title = "Marcar completado quiz";
$PAGE->set_title($title);
$PAGE->set_heading($title);
echo $OUTPUT->header();

echo \html_writer::tag('h1', $title);
$mform = new uploadcsv_form($url, null);
$toform = new stdClass();

if ($mform->is_cancelled()) {
} else if ($formdata = $mform->get_data()) {

    $iid = csv_import_reader::get_new_iid('uploaduser');
    $cir = new csv_import_reader($iid, 'uploaduser');
    $content = $mform->get_file_content('userfile');
    
    $readcount = $cir->load_csv_content(
        $content,
        $formdata->encoding,
        $formdata->delimiter_name
        //,'\local_enrolcompany\enrolcompany_utils::validate_user_upload_columns'
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
        $data_row = \local_mutual\front\utils::process_data_row($line, $cir->get_columns());
        $data_row_set = $data_row['data'];
        $validation_row = \local_mutual\front\utils::validation_row($data_row_set);
        $error = $validation_row['error'];
        try {
            if(empty($error)){
                $course = get_course($data_row_set['courseid']);
                $info = new completion_info($course);
                $activities = $info->get_activities();
                foreach($activities as $cm) {
                    if($cm->id == $data_row_set['cmid']) {
                        $user = $DB->get_record('user', ['username' => $data_row_set['rut']]);
                        $info->update_state($cm, COMPLETION_UNKNOWN, $user->id);
                    }
                }
            } else {
                $errornum++;
                $userserrors++;
                $dataerror[] = 'faltan campos en el la linea ' . $linenum;
                
            }
        } catch (Exception $ex) {
           
        }
    }

    $cir->close();
    $cir->cleanup(true);
    
    
    /* echo $OUTPUT->box_end();
    echo $OUTPUT->continue_button($url); */
    echo $OUTPUT->footer();
    die;
    
    
}
$mform->display();

echo $OUTPUT->footer();
die;
