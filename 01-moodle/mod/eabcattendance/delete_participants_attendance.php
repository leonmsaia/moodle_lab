<?php
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
 * Delete user Eabcattendance
 *
 * @package    mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE, $COURSE;
require_once($CFG->dirroot.'/mod/eabcattendance/locallib.php');


$pageparams = new mod_eabcattendance_take_page_params();

$id                     = required_param('id', PARAM_INT);
$pageparams->sessionid  = required_param('sessionid', PARAM_INT);
$pageparams->grouptype  = required_param('grouptype', PARAM_INT);
$pageparams->sort       = optional_param('sort', EABCATT_SORT_DEFAULT, PARAM_INT);
$pageparams->copyfrom   = optional_param('copyfrom', null, PARAM_INT);
$pageparams->viewmode   = optional_param('viewmode', null, PARAM_INT);
$pageparams->gridcols   = optional_param('gridcols', null, PARAM_INT);
$pageparams->page       = optional_param('page', 1, PARAM_INT);
$pageparams->perpage    = optional_param('perpage', get_config('eabcattendance', 'resultsperpage'), PARAM_INT);
$pageparams->studentid   = optional_param('studentid', null, PARAM_INT);
$pageparams->action   = optional_param('action', null, PARAM_INT);
$pageparams->name   = optional_param('name', null, PARAM_TEXT);

//Parametros GET para buscar por nombre y apellido (24/11/2019 FHS)
$buscar_nombre			= optional_param('nombre', null, PARAM_TEXT);
$buscar_apellido		= optional_param('apellido', null, PARAM_TEXT);


//Argumentos de la plantilla del form de busqueda (25/11/2019 FHS)
$datos_plantilla_busqueda = array('nombre' => $buscar_nombre,
								 'apellido' => $buscar_apellido,
								 'id' => $id,
								 'sessionid' => $pageparams->sessionid,
								 'grouptype' => $pageparams->grouptype,
								 'studentid' => $pageparams->studentid,
								 'action' => $pageparams->action,
								 'header_label' => 'Buscar usuario',
								 'name_label' => 'Nombre',
								 'surname_label' => 'Apellido',
								 'search_label' => get_string('search', 'eabcattendance'),
								 'ruta' => 'delete_participants_attendance.php'
								);

$cm             = get_coursemodule_from_id('eabcattendance', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$att            = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);
// Check this is a valid session for this eabcattendance.
$session        = $DB->get_record('eabcattendance_sessions', array('id' => $pageparams->sessionid, 'eabcattendanceid' => $att->id),
                                  '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/eabcattendance:takeeabcattendances', $context);

$pageparams->group = groups_get_activity_group($cm, true);

$pageparams->init($course->id);
$att = new mod_eabcattendance_structure($att, $cm, $course, $PAGE->context, $pageparams);

$allowedgroups = groups_get_activity_allowed_groups($cm);
if (!empty($pageparams->grouptype) && !array_key_exists($pageparams->grouptype, $allowedgroups)) {
     $group = groups_get_group($pageparams->grouptype);
     throw new moodle_exception('cannottakeforgroup', 'eabcattendance', '', $group->name);
}

$PAGE->set_url($att->url_delete_participants_attendance());
$PAGE->set_title($course->shortname. ": Eliminar participante");
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(true);
$PAGE->navbar->add($att->name);

$output = $PAGE->get_renderer('mod_eabcattendance');
$tabs = new eabcattendance_tabs($att);
$sesstable = new eabcattendance_delete_data($att);


if ($pageparams->action == mod_eabcattendance_sessions_page_params::DELETE_USER){
	$sessionid = required_param('sessionid', PARAM_INT);
	$confirm   = optional_param('confirm', null, PARAM_INT);

	$action2 = mod_eabcattendance_sessions_page_params::TAB_DELETE_USER_ATTENDANCE;

	$params2 = array('sessionid' => $sessionid, 'action' => $action2, 'grouptype' => $pageparams->grouptype);
	
	if (isset($confirm) && confirm_sesskey()) {
		try {
			$att->delete_user_sesion($pageparams->studentid, $pageparams->grouptype, $pageparams->sessionid, $course->id);
		} catch (Exception $e) {
			echo $OUTPUT->header();
			echo '<div class="alert alert-danger">Error eliminando al participante, no tiene idInterno de Dynamics" </div>';
            $redirect_url = new moodle_url('/mod/eabcattendance/delete_participants_attendance.php', array('id'=> $cm->id, 'grouptype' => $pageparams->grouptype ,'action' => mod_eabcattendance_sessions_page_params::TAB_DELETE_USER_ATTENDANCE, 'sessionid' => $pageparams->sessionid));
            redirect($redirect_url,'Será redirigido en unos segundos al listado de participantes',10);
            echo $OUTPUT->footer();
			exit();
		}
		redirect($att->url_delete_participants_attendance($params2), 'Usuario eliminado con exito');
	}
	
	$sessinfo = $att->get_session_info($sessionid);

	$message = '¿Seguro desea eliminar al participante? <br><span style="font-weight: 800;color: #1f4a79;">'.$pageparams->name.'</span>';
	$message .= str_repeat(html_writer::empty_tag('br'), 2);
	$message .= 'De la sesión: ';
	$message .= '<span style="font-weight: 800;color: #1f4a79;">'.userdate($sessinfo->sessdate, get_string('strftimedmyhm', 'eabcattendance')).'</span>';
	$message .= html_writer::empty_tag('br');
	
	$params = array('action' => $pageparams->action, 'sessionid' => $sessionid, 'confirm' => 1, 'sesskey' => sesskey(), 'grouptype' => $pageparams->grouptype, 'studentid' => $pageparams->studentid );

	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string('eabcattendanceforthecourse', 'eabcattendance').' :: ' .format_string($course->fullname));
	echo $OUTPUT->confirm($message, $att->url_delete_participants_attendance($params), $att->url_delete_participants_attendance($params2));
	?>
	<script>
	document.addEventListener("DOMContentLoaded", function() {
		const forms = document.querySelectorAll("form");

		forms.forEach(form => {
			form.addEventListener("submit", function(event) {
				const submitButton = form.querySelector("button[type='submit']");
				if (submitButton) {
					submitButton.disabled = true;
				}
			});
		});
	});
	</script>
	<?php
	echo $OUTPUT->footer();
	exit;
}

echo $output->header();
echo $output->heading(get_string('eabcattendanceforthecourse', 'eabcattendance').' :: ' .format_string($course->fullname));
echo $output->render($tabs);
echo $output->render_from_template('eabcattendance/search_user_form',$datos_plantilla_busqueda); //despliega el formulario de busqueda de usuarios (25/11/21019 FHS)


if ($buscar_nombre){
	$var_auxiliar = array();
	
	foreach ($sesstable->users as $usuario){		
		if (stristr($usuario->firstname, $buscar_nombre)){
			$var_auxiliar[]=$usuario;
		}
	}
	
	$sesstable->users=$var_auxiliar;
}


if ($buscar_apellido){
	$var_auxiliar = array();
	
	foreach ($sesstable->users as $usuario){		
		if (stristr($usuario->lastname, $buscar_apellido)){
			$var_auxiliar[]=$usuario;
		}
	}	
	$sesstable->users=$var_auxiliar;
}

echo $output->render($sesstable);
echo $output->footer();
