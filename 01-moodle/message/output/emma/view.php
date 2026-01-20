<?php

require('../../../config.php');
require_once('classes/message_procesor.php');
require_once('classes/reporte_envio.php');
require_login();
$PAGE->set_url(new moodle_url('/message/output/emma/view.php'));
$PAGE->set_context(context_system::instance());
$strheading = get_string('reportes', 'message_emma');
$PAGE->navbar->add(get_string('administrationsite'));
$PAGE->navbar->add(get_string('plugins', 'admin'));
$PAGE->navbar->add(get_string('messageoutputs', 'message'));
$PAGE->navbar->add($strheading);
$PAGE->set_heading($strheading);
$PAGE->set_title($strheading);

echo $OUTPUT->header();

$message_procesor   = new \emma\message\message_procesor();
$devueltos          = $message_procesor->campanadevueltos();
$campanainfo        = $message_procesor->campanainfo();
$campanaresultadoevento        = $message_procesor->campanaresultadoevento();

echo $OUTPUT->render_from_template('message_emma/reporte', array('devueltos'=>$devueltos, 'campanainfo'=>$campanainfo, 'campanaresultadoevento'=>$campanaresultadoevento));

$mform = new simplehtml_form();
if ($formdata = $mform->get_data()) {
    if($formdata->email){
        $desde = date('Ymd',$formdata->startdate); 
        $hasta = date('Ymd',$formdata->enddate);
        $message_procesor->campanareporteenvio($formdata->email,$desde,$hasta);
        echo get_string('enviado', 'message_emma');
    }else{
        echo get_string('noenviado', 'message_emma');
    }
}
echo $OUTPUT->render_from_template('message_emma/envioreportehead',array());
$mform->display();
echo $OUTPUT->render_from_template('message_emma/envioreportefooter',array());
echo $OUTPUT->footer();