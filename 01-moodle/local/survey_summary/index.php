<?php

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
//require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
require_once($CFG->libdir . '/datalib.php');

/** @var moodle_page $PAGE */
global $PAGE, $OUTPUT, $DB, $USER, $SESSION;

require_login();

$curso = optional_param('curso', '', PARAM_RAW);
$modalidadopresencial = optional_param('modalidadopresencial', '', PARAM_RAW);
$modalidadsemipresencial = optional_param('modalidadsemipresencial', '', PARAM_RAW);
$modalidaddistancia = optional_param('modalidaddistancia', '', PARAM_RAW);
$modalidadelearning = optional_param('modalidadelearning', '', PARAM_RAW);
$modalidadstreaming = optional_param('modalidadstreaming', '', PARAM_RAW);
$modalidadmobile = optional_param('modalidadmobile', '', PARAM_RAW);
$dateto = optional_param_array('dateto', array(), PARAM_RAW);
$datefrom = optional_param_array('datefrom', array(), PARAM_RAW);
$evaluacion = optional_param('evaluacion', '', PARAM_RAW);
$estadoabierto = optional_param('estadoabierto', '', PARAM_RAW);
$estadocerrado = optional_param('estadocerrado', '', PARAM_RAW);
$hours = optional_param('hours', '', PARAM_RAW);

//creo objeto para url de las paginas y data de formulario
$fromform = new stdClass();
$fromform->curso = $curso;
$fromform->modalidadopresencial = $modalidadopresencial;
$fromform->modalidadsemipresencial = $modalidadsemipresencial;
$fromform->modalidaddistancia = $modalidaddistancia;
$fromform->modalidadelearning = $modalidadelearning;
$fromform->modalidadstreaming = $modalidadstreaming;
$fromform->modalidadmobile = $modalidadmobile;
$fromform->evaluacion = $evaluacion;
$fromform->estadocerrado = $estadoabierto;
$fromform->estadocerrado = $estadocerrado;
$fromform->hours = $hours;

$download = optional_param('download', '', PARAM_ALPHA);
$tsort = optional_param('tsort', '', PARAM_ALPHA);
$page = optional_param('page', '', PARAM_ALPHA);
$url = new moodle_url('/local/survey_summary/index.php',(array)$fromform);
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);

if(empty($dateto)){
    $dateto = array(
        'day' => '1',
        'month' => '1',
        'year' => '2019',
        'enabled' => 0,
    );
}
if(empty($datefrom)){
    $datefrom = array(
        'day' => date('d'),
        'month' => date('m'),
        'year' => date('Y'),
        'enabled' => 0,
    );
}
$fromform->dateto = $dateto;
$fromform->datefrom = $datefrom;

$SESSION->filtersummary = $fromform;

$is_capability = false;
$is_capability_str = "";
if(!is_siteadmin()){
    $enrolled_courses = enrol_get_all_users_courses($USER->id);
    foreach($enrolled_courses as $enrolled_course){
        if (has_capability('local/resumencursos:access_course', \context_course::instance($enrolled_course->id))){
            $is_capability = true;
            $is_capability_str .= $enrolled_course->id.",";
        }
    }
}

$where = \local_resumencursos\utils\summary_utils::get_where($fromform, $is_capability, $is_capability_str);
$select = \local_resumencursos\utils\summary_utils::get_select_table_sql($USER->id);
$from = \local_resumencursos\utils\summary_utils::get_from_table_sql($USER->id, is_siteadmin());

$table = new \local_survey_summary\table\custom_table('uniqueid');
$table->is_downloading($download, $download);
$table->set_sql($select, $from, '1=1'.$where);
$table->define_baseurl($url);
if ($table->is_downloading()) {
    echo $table->out(10, true);
    exit;
}
echo $OUTPUT->header();
echo \html_writer::tag('h1', get_string('filter'));
$mform = new \local_survey_summary\form\filters_form();
$mform->set_data($fromform);

if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
    $SESSION->filtersummary = $fromform;
} 

//displays the form
ob_start();
echo $mform->display();
$outputmform_body = ob_get_contents();
ob_end_clean();

//the table_sql
ob_start();
echo $table->out(10, true);
$table_body = ob_get_contents();
ob_end_clean();

echo $OUTPUT->render_from_template('local_survey_summary/tabla', array("filters" => $outputmform_body, "table_body" => $table_body));

$PAGE->requires->js_call_amd('local_showallactivities/showfilters', 'init');

echo $OUTPUT->footer();
