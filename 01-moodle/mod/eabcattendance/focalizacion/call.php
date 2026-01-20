<?php

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'../../classes/focalizacion_call_form.php');

global $PAGE, $OUTPUT, $CFG, $USER, $DB ;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/mod/eabcattendance/focalizacion/view.php'));
$PAGE->set_title('Focalización llamada');
$PAGE->set_heading('Focalización llamada');   

$courseid = required_param('id', PARAM_INT);
$instructorid = required_param('instructorid', PARAM_INT);
$sesionid = required_param('sesionid', PARAM_INT);

echo $OUTPUT->header();
$ruta = 'call.php?id='.$courseid.'&instructorid='.$instructorid.'&sesionid='.$sesionid;
$data = array(
    'sesionid' => $sesionid,
    'instructorid' => $instructorid
);
$mform = new focalizacion_call_form($ruta, $data);
if ($formdata = $mform->get_data()) {
    $record = new \stdClass();
    $record->sesionid = $sesionid;
    $record->instructorid = $instructorid;
    $record->callemp = (int) $formdata->yesno;
    $record->calldate = date('d-m-Y H:i');
    
    $focalizacion = $DB->get_record('focalizacion',array('sesionid'=>$sesionid, 'instructorid'=>$instructorid));
    if (empty($focalizacion)){
        $DB->insert_record('focalizacion', $record);
    }else{
        $record->id     = (int) $focalizacion->id;                    
        $DB->update_record('focalizacion', $record);
    }
    redirect($CFG->wwwroot, 'Registrado', null, \core\output\notification::NOTIFY_SUCCESS);
}
echo "<p>¿Se contactó vía telefónica con la empresa donde tiene que realizar la actividad?</p>";
$mform->display();

echo $OUTPUT->footer();