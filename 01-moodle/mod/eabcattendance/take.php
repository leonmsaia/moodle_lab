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
 * Take Eabcattendance
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

//Parametros GET para buscar por nombre y apellido (24/11/2019 FHS)
$buscar_nombre			= optional_param('nombre', null, PARAM_TEXT);
$buscar_apellido		= optional_param('apellido', null, PARAM_TEXT);


//Argumentos de la plantilla del form de busqueda (25/11/2019 FHS)
$datos_plantilla_busqueda = array('nombre' => $buscar_nombre,
								 'apellido' => $buscar_apellido,
								 'id' => $id,
								 'sessionid' => $pageparams->sessionid,
								 'grouptype' => $pageparams->grouptype,
								 'header_label' => 'Buscar usuario',
								 'name_label' => 'Nombre',
								 'surname_label' => 'Apellido',
								 'search_label' => get_string('search', 'eabcattendance'),
                                 'ruta' => 'take.php'
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

if (($formdata = data_submitted()) && confirm_sesskey()) {
    $att->take_from_form_data($formdata);

    $group = 0;
    if ($att->pageparams->grouptype != mod_eabcattendance_structure::SESSION_COMMON) {
        $group = $att->pageparams->grouptype;
    } else {
        if ($att->pageparams->group) {
            $group = $att->pageparams->group;
        }
    }

    $totalusers = count_enrolled_users(context_module::instance($cm->id), 'mod/eabcattendance:canbelisted', $group);
    $usersperpage = $att->pageparams->perpage;

    if (!empty($att->pageparams->page) && $att->pageparams->page && $totalusers && $usersperpage) {
        $numberofpages = ceil($totalusers / $usersperpage);
        if ($att->pageparams->page < $numberofpages) {
            $params = array(
                'sessionid' => $att->pageparams->sessionid,
                'grouptype' => $att->pageparams->grouptype);
            $params['page'] = $att->pageparams->page + 1;
            redirect($att->url_take($params), get_string('moreeabcattendance', 'eabcattendance'));
        }
    }

    redirect($att->url_manage(), get_string('eabcattendancesuccess', 'eabcattendance'));
}

$PAGE->set_url($att->url_take());
$PAGE->set_title($course->shortname. ": ".$att->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(true);
$PAGE->navbar->add($att->name);

$output = $PAGE->get_renderer('mod_eabcattendance');
$tabs = new eabcattendance_tabs($att);
$sesstable = new eabcattendance_take_data($att);

// Output starts here.


echo $output->header();
echo $output->heading(get_string('eabcattendanceforthecourse', 'eabcattendance').' :: ' .format_string($course->fullname));
echo $output->render($tabs);
echo $output->render_from_template('eabcattendance/search_user_form',$datos_plantilla_busqueda); //despliega el formulario de busqueda de usuarios (25/11/21019 FHS)

//Aqui se hace la busqueda de los usuarios (24/11/2019 FHS)
if ($buscar_nombre){
//filtra por nombre (24/11/2019 FHS)
	$var_auxiliar = array();
	
	foreach ($sesstable->users as $usuario){		
		if (stristr($usuario->firstname, $buscar_nombre)){
			$var_auxiliar[]=$usuario;
		}
	}
	
	$sesstable->users=$var_auxiliar;
}


if ($buscar_apellido){
//filtra por apellido	(24/11/2019 FHS)
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
