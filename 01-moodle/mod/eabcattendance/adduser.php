<?php

// (06/11/2019 FHS)

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Add user to course management.
 *
 * @package    mod_eabcattendance
 * @copyright  2013 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*   

El flujo es el siguiente:
* Busco un usario con el formulario inicial
 - Si no existe y no esta matriculado: Lo creo y lo matriculo
 - si ya existe pero no esta matriculado al curso: Lo matriculo
 - Si ya existe y esta matriculado al curso: Notifico en pantalla
* Respuesta del ws en caso de exito
    $json = '{
            "IdInterno": "f4fa9586-a54e-ea11-a812-000d3a4f6db7",
            "IdSesion": "57c15a5b-c196-ea11-a811-000d3a4f62e7",
            "IdRolParticipante": "80af5dc9-fd15-ea11-a811-000d3a4f6db7",
            "ParticipanteIdentificador": "17654321-2",
            "ParticipanteTipoDocumento": 1,
            "ParticipanteNombre": "17654321",
            "ParticipanteApellido1": "17654321",
            "ParticipanteApellido2": "17654321",
            "ParticipantePais": 1,
            "ParticipanteEmail": "17654321@17654321.com",
            "ParticipanteFono": "17654321",
            "ResponsableIdentificador": "17654321-2",
            "ResponsableNombres": "Nombre Responsable",
            "ResponsableEmail": "responsable@email.com",
            "NumeroAdherente": "41670",
            "IdSexo": 1
    }'; 



*/
require_once(dirname(__FILE__) . '/../../config.php');
/**
 * @var core_renderer $OUTPUT
 */
global $CFG, $DB, $OUTPUT, $PAGE, $COURSE;
require_once($CFG->dirroot . '/mod/eabcattendance/locallib.php');
require_once($CFG->dirroot . '/group/lib.php');

$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', null, PARAM_TEXT);
$tname = optional_param('tname', null, PARAM_TEXT);
$tipodoc = optional_param('tipodoc', null, PARAM_TEXT);
$validaterut = optional_param('validaterut', 0, PARAM_TEXT);
$sing_up_form = optional_param('sing_up_form', null, PARAM_TEXT);
$enrol_passport = false;


$cm = get_coursemodule_from_id('eabcattendance', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$att = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);

$context = context_module::instance($cm->id);
$pageparams = new mod_eabcattendance_manage_page_params();
$pageparams->perpage = get_config('eabcattendance', 'resultsperpage');
$pageparams->init($cm);
$att = new mod_eabcattendance_structure($att, $cm, $course, $context, $pageparams);

$PAGE->set_url($att->url_adduser());

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/eabcattendance:managetemporaryusers', $context);
$PAGE->set_title($course->shortname . ": " . $att->name . ' - ' . get_string('useradd', 'eabcattendance'));
$PAGE->set_heading($course->fullname);
$PAGE->force_settings_menu(true);
$PAGE->set_cacheable(true);
$PAGE->navbar->add(get_string('useradd', 'eabcattendance'));

$output = $PAGE->get_renderer('mod_eabcattendance');
$tabs = new eabcattendance_tabs($att, eabcattendance_tabs::TAB_ADDUSER);

$filtercontrols = new eabcattendance_filter_controls($att);

$formdata = (object)array(
    'id' => $cm->id,
    'tname' => $tname,
    'tipodoc' => $tipodoc
);

$mform = new  \mod_eabcattendance\adduser_form();
$mform->set_data($formdata);
if(!empty($tname)){
    $filtercontrols->id = $cm->id;
    $filtercontrols->username = $tname;
    $filtercontrols->password = $tname;
    $filtercontrols->tname = $tname;
    $filtercontrols->tipodoc = $tipodoc;
    
    $datosNominativo = \local_mutual\back\utils::get_personas_nominativo($tname, $tipodoc);       
    if ( isset($datosNominativo->return->error) &&  ($datosNominativo->return->error == 0)){        
        $fecha_nac = (string) $datosNominativo->return->persona->fechaNacimiento; 
        $filtercontrols->lastname           = (string) $datosNominativo->return->persona->apellido1;
        $filtercontrols->apellidomaterno    = (string) $datosNominativo->return->persona->apellido2;
        $filtercontrols->email              = (string) $datosNominativo->return->persona->email;
        $filtercontrols->participantefechanacimiento    = \local_pubsub\utils::date_to_timestamp($fecha_nac);
        $filtercontrols->firstname          = (string) $datosNominativo->return->persona->nombres;
        $filtercontrols->rol                = (string) $datosNominativo->return->persona->rol;
        $filtercontrols->participantesexo   = (string) $datosNominativo->return->persona->sexo;

        foreach($datosNominativo->return->empresas as $empresa){
            if($empresa->activo == 1){
                $filtercontrols->nroadherente   = (string) $empresa->contrato;
                $filtercontrols->empresarut     = (string) $empresa->rut."-".$empresa->dv;
                $filtercontrols->empresarazonsocial    = (string) $empresa->razonSocial;
            }
        }    
    }    
}

$mform_signup = new \mod_eabcattendance\user_login_signup_form(null, ["filtercontrols" => $filtercontrols]);
$mform_signup->set_data($filtercontrols);
$enrollform = new \mod_eabcattendance\enroll_form(null, ["filtercontrols" => $filtercontrols]);
// Output starts here.
echo $output->header();
echo $output->heading(get_string('useradd', 'eabcattendance') . ' : ' . format_string($course->fullname));
echo $output->render($tabs);

if ($data = $mform->get_data() || (($tipodoc == 1) && \mod_eabcattendance\metodos_comunes::validar_rut($tname) == true) || ($tipodoc == 2 && $tname)) {
    if($tipodoc == 2) {
        $enrol_passport = true;
    }

    $mform->display();
    $user = $DB->get_record('user', array('username' => $tname));
    //verifico si el usuario ya existe
    if ($user && $data) {
        //si ya existe y no esta matriculado en en el curso lo matriculo y registro en la sesion y notifico a back
        //si ya existe y esta matriculado muestro un mensaje
        \mod_eabcattendance\metodos_comunes::validate_enrol_user_attendance_form($user, $course, $cm, $enrollform, $output, $filtercontrols);
    } else {
        //en caso contrario se realiza el proceso completo 
        //se crea el usuario se matricula y se registra en la sesion y notifico a back
        \mod_eabcattendance\metodos_comunes::create_and_enrol_attendanceform($mform_signup, $course, $output, $filtercontrols, $context, $enrol_passport);
    }
    
} else {
    $mform->display();
    
}

echo $output->footer($course);
