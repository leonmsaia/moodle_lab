<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once('../eabcpanelcursos/classes/eabcpanelcursos_sessions.php');
require_once('classes/feedback_filter_dates.php');
require_once('classes/data_table.php');
require_once('classes/feedback_facilitador.php');

require_login();
/**
 * @var moodle_page $PAGE
 * @var core_renderer $OUTPUT;
 */
global $PAGE, $OUTPUT, $CFG, $DB;

$PAGE->requires->css('/local/feedback_facilitador/assets/main.css');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/feedback_facilitador/view.php'));
$PAGE->set_pagetype('local-feedback-facilitador');

$fecha_desde = strtotime(date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-1 day" );
$fecha_hasta = strtotime(date("Y-m-d")) ;
$facilitador = 0;
$rangos = 0;

$download = optional_param('download', '', PARAM_ALPHA);
$table = new data_table('id');
$table->is_downloading($download, 'Feedback Report', 'Feedback Report');

if (!$table->is_downloading()) {
    $PAGE->set_title('Feedback Report');
    $PAGE->set_heading('Reporte');   
    echo $OUTPUT->header();
    echo $OUTPUT->single_button(new moodle_url('totales.php'), 'Ver resultados generales y gráficas');
    
    $mostra_grafico = false;

    $mform = new simplehtml_form();
    if ($formdata = $mform->get_data()) {
        
        $fecha_desde = $formdata->startdate;
        $fecha_hasta = $formdata->enddate;
        $facilitador = $formdata->selinstructor;
        $rangos      = $formdata->rangos;

        $facilitadores = feedback_facilitador::set_table_panel_facilitadores($fecha_desde,$fecha_hasta, $facilitador, $rangos);

        $fields = 'aa.*';
        $from = '{panel_feedback_facilitadores} aa';
        $where = '1=1';
        $table->set_sql($fields, $from, $where);
        $table->define_baseurl("$CFG->wwwroot/local/feedback_facilitador/view.php");
        $table->out(100, true);

        $mostra_grafico = true;
    }
    $mform->display();
}



if (!$table->is_downloading()) {
    if($mostra_grafico){
        $chart = feedback_facilitador::show_grafiph($facilitadores);    
        echo $OUTPUT->render($chart);
    }
    echo $OUTPUT->footer();
}