<?php

require_once(dirname(__FILE__).'/../../../config.php');
require_once(dirname(__FILE__).'../../classes/focalizacion_mail_form.php');
require_once(dirname(__FILE__).'/../../../message/output/emma/classes/message_procesor.php');

/**
 * @var moodle_page $PAGE
 * @var core_renderer $OUTPUT;
 */
global $PAGE, $OUTPUT, $CFG, $USER, $DB ;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/mod/eabcattendance/focalizacion/view.php'));
$PAGE->set_title('Focalización');
$PAGE->set_heading('Focalización');   

$courseid = required_param('id', PARAM_INT);
$sesionid = optional_param('sesionid','', PARAM_INT);
$fecha = optional_param('fecha','', PARAM_RAW);
$hora = optional_param('hora','', PARAM_RAW);

echo $OUTPUT->header();

$instructor = $DB->get_record('user',array('id'=>$USER->id));

$curso = $DB->get_record('course',array('id'=>$courseid));

//Busco la sesion para obtener los datos del contacto
$nombrecontacto = null;
$emailcontacto  = null;
$fonocontacto   = null;
$nombrempresa   = null;
$direccion      = null;

$session_back = $DB->get_record('sesion_back',array('id_sesion_moodle'=>$sesionid));

if ($session_back){
        $nombrecontacto = $session_back->nombrecontacto;
        $emailcontacto  = $session_back->emailcontacto;
        $fonocontacto   = $session_back->fonocontacto;
        $nombrempresa   = $session_back->nombreadherente;
        $direccion      = $session_back->direccion;            
}

$datos = array(
    'nombre'        => $instructor->firstname,
    'apellido'      => $instructor->lastname,
    'curso'         => $curso->fullname,
    'fecha'   => $fecha,
    'hora'    => $hora,
    'email'         => $instructor->email,
    'nombrecontacto' =>  $nombrecontacto,
    'emailcontacto'  =>  $emailcontacto,
    'fonocontacto'   =>  $fonocontacto,
    'nombrempresa'   =>  $nombrempresa,
    'direccion'      =>  $direccion,
    'sesionid'      =>   $sesionid,
);
$ruta = 'view.php?id='.$courseid;

$mform = new focalizacion_mail_form($ruta,$datos);

if ($formdata = $mform->get_data()) {
    
    $user = new \stdClass();
    $user->id = $instructor->id;
    $user->email = $formdata->emailempresa;
    $user->username = 'username'; 
    $user->auth = 'manual';
    $user->suspended = 0;
    $user->mailformat = 1;   

    $texto_sin_formato = strip_tags($formdata->contenido['text']);
    try{
        $message_procesor   = new \emma\message\message_procesor();
        $messageEmma = new \stdClass();
        $messageEmma->userfrom = new \stdClass();
        $messageEmma->userto = new \stdClass();
        $messageEmma->subject           = 'Información de curso';
        $messageEmma->fullmessagehtml   = $formdata->contenido['text'];
        $messageEmma->fullmessage       = $formdata->contenido['text'];
        $messageEmma->userfrom->id      = $instructor->id;
        $messageEmma->userto->id        = 1;
        $messageEmma->userto->email     = $formdata->emailempresa;
        $messageEmma->userfrom->email   = $instructor->email;
        $messageEmma->fullmessageformat = 1;
        $attachment='';

        $send = $message_procesor->enviadirecto($messageEmma, $attachment);
        
        if ($send){
            $record = new \stdClass();
            $record->sesionid = $formdata->sesionid;
            $record->instructorid = $instructor->id;
            $record->email = 1;
            $record->emaildate = date('d-m-Y H:i');
            $focalizacion = $DB->get_record('focalizacion',array('sesionid'=>$sesionid, 'instructorid'=>$instructor->id));
            if (empty($focalizacion)){
                $DB->insert_record('focalizacion', $record);
            }else{
                $record->id     = $focalizacion->id;                    
                $DB->update_record('focalizacion', $record);
            }
        }        
        redirect('call.php?id='.$courseid.'&instructorid='.$instructor->id.'&sesionid='.$formdata->sesionid , 'Correo enviado', null, \core\output\notification::NOTIFY_SUCCESS);

    }catch (coding_exception $e) {
        throw new moodle_exception("errormsg", "eror", '', $e->getMessage(), $e->debuginfo);
    } 
}

$mostrardireccion = '';
if (\local_mutual\front\utils::is_course_presencial($courseid)){
    $mostrardireccion = '<tr>
        <th>Dirección empresa</th>
        <td>'.$direccion.'</td>
    </tr>';
}


echo "<fieldset> <legend>Información del contacto del cliente</legend>";
echo ' <table class="table table-responsive table-bordered ">
        <tr>
            <th>Nombre del contacto cliente</th>
            <td>'.$nombrecontacto.'</td>
        </tr>
        <tr>
            <th>Correo del contacto cliente</th>
            <td>'.$emailcontacto.'</td>
        </tr>
        <tr>
            <th>Teléfono del contacto cliente</th>
            <td>'.$fonocontacto.'</td>
        </tr>
        '.$mostrardireccion.'
      </table>
    ';
echo "</fieldset>";
echo "<hr>";
echo "<h4>Contenido del correo</h4><br>";
$mform->display();

echo $OUTPUT->footer();
