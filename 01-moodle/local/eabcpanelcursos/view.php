<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/classes/eabcpanelcursos_sessions.php');
require_once(dirname(__FILE__).'/classes/eabcpanelcursos_filter_dates.php');
require_once(dirname(__FILE__).'/classes/eabcpanelcursos_sendmail.php');
require_login();
/**
 * @var moodle_page $PAGE
 * @var core_renderer $OUTPUT;
 */
global $PAGE, $OUTPUT, $CFG, $DB, $USER;
define('HORAS_PLAZO', 86400);

$PAGE->requires->css('/local/eabcpanelcursos/assets/main.css');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/eabcpanelcursos/view.php'));
echo $OUTPUT->header();
$fecha_desde = '';
$fecha_hasta = '';
$mform = new simplehtml_form();
if ($formdata = $mform->get_data()) {
    $fecha_desde = $formdata->startdate;
    $fecha_hasta = $formdata->enddate;
}
$mform->display();
$actividades = new cursos_session();
$cu = $actividades->get_sessions($fecha_desde,$fecha_hasta, $USER->id);
echo $OUTPUT->render_from_template('local_eabcpanelcursos/panel_estatus', array('actividades' => $cu));
echo $OUTPUT->footer();