<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('../eabcpanelcursos/classes/eabcpanelcursos_sessions.php');
require_once('classes/feedback_filter_dates.php');
require_once('classes/feedback_facilitador.php');

require_login();
/**
 * @var moodle_page $PAGE
 * @var core_renderer $OUTPUT;
 */
global $PAGE, $OUTPUT, $CFG, $DB;

$PAGE->requires->css('/local/feedback_facilitador/assets/main.css');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/feedback_facilitador/totales.php'));

$PAGE->set_title('Feedback Report');
$PAGE->set_heading(get_string('reporte_totales','local_feedback_facilitador'));  
echo $OUTPUT->header();
echo $OUTPUT->single_button(new moodle_url('view.php'), 'Regresar');
$fecha_desde = strtotime(date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-1 month" );
$fecha_hasta = strtotime(date("Y-m-d")) ;
$facilitador = 0;
$rangos = 0;
$mform = new simplehtml_form();
if ($formdata = $mform->get_data()) {
    $fecha_desde = $formdata->startdate;
    $fecha_hasta = $formdata->enddate;
    $facilitador = $formdata->selinstructor;
    $rangos      = $formdata->rangos;
}
$mform->display();


feedback_facilitador::set_table_panel_facilitadores($fecha_desde,$fecha_hasta,$facilitador,$rangos);
$totales = feedback_facilitador::get_totales();

echo $OUTPUT->render_from_template('local_feedback_facilitador/totales', array('totales' => $totales));

$chart = new \core\chart_bar(); // Create a bar chart instance.
$series1 = new \core\chart_series('Totales', [$totales['total_cumplimiento'], $totales['recomienda_curso'], $totales['envio_correo'], $totales['llamo_empresa'], $totales['encuesta_curso']]);
$chart->add_series($series1);
$chart->set_labels(['Total cumplimiento', 'recomienda_curso', 'envio_correo','llamo_empresa','encuesta_curso']);
echo $OUTPUT->render($chart);
echo $OUTPUT->footer();