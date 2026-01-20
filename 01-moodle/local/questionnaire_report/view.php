<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

require_login();
/**
 * @var moodle_page $PAGE
 * @var core_renderer $OUTPUT;
 */
global $PAGE, $OUTPUT, $CFG, $DB;

$PAGE->set_context(context_system::instance());
require_once($CFG->libdir.'/tablelib.php');
require_once('classes/data_table.php');
require_once('classes/questionnaire.php');
require_once('classes/questionnaire_selecform.php');

$download = optional_param('download', '', PARAM_ALPHA);
$PAGE->set_url(new moodle_url('/local/questionnaire_report/view.php'));
$table = new data_table('id');
$table->is_downloading($download, 'Feedback Report', 'Feedback Report');
$where = '1=1';

$fecha_desde = strtotime(date('d-m-Y'));
$fecha_hasta = strtotime(date('d-m-Y'));
$tipo_respuesta = 'response_text';

if (!$table->is_downloading()) {        
    $PAGE->set_title('Feedback Report');
    $PAGE->set_heading('Reporte');   
    echo $OUTPUT->header(); 
    
    $mform = new simplehtml_form();
    if ($formdata = $mform->get_data()) {
        $tipo_respuesta = $formdata->respuesta;
        $fecha_desde = $formdata->startdate;
        $fecha_hasta = $formdata->enddate;   
    }    
    $questionnaires = questionnaire::getData($fecha_desde,$fecha_hasta,$tipo_respuesta);
    $mform->display();          
}

$fields = 'aa.*';
$from = '{questionnaire_report} aa';
$table->set_sql($fields, $from, $where);
$table->define_baseurl("$CFG->wwwroot/local/questionnaire_report/view.php");
$table->out(10, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
