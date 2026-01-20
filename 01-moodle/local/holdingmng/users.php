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
 * Plugin administration pages are defined here.
 *
 * @package     holdingmng
 * @category    admin
 * @copyright   2020 e-ABC Learning <contacto@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once ('../../config.php');
global $PAGE, $OUTPUT, $CFG, $USER;
require_once ($CFG->dirroot .'/local/holdingmng/classes/holdingusers_form.php');

//tamaño de la página
define("HOLDINGPAGESIZE", 10);
$holdingid  = optional_param('holdingid', 0, PARAM_INT);
$userid     = optional_param_array('userid', [], PARAM_RAW);
$sort       = optional_param('sort', 'userid', PARAM_TEXT);
$dir        = optional_param('dir', 'ASC', PARAM_TEXT);
$action     = optional_param('action', '', PARAM_TEXT);
$removeuser = optional_param('removeuser', '', PARAM_TEXT);
$page       = optional_param('page', 0, PARAM_INT);
$perpage    = optional_param('perpage', HOLDINGPAGESIZE, PARAM_INT);

require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('title','local_holdingmng'));
$PAGE->set_url(new moodle_url($CFG->wwwroot .'/local/holdingmng/users.php'));

// strings js
$PAGE->requires->string_for_js('holding','local_holdingmng');
$PAGE->requires->string_for_js('user','local_holdingmng');
$PAGE->requires->string_for_js('edit','local_holdingmng');
$PAGE->requires->string_for_js('deleteuserstr','local_holdingmng');
$PAGE->requires->string_for_js('nodata','local_holdingmng');
$PAGE->requires->string_for_js('confirmtitle','local_holdingmng');
$PAGE->requires->string_for_js('confirmmessage','local_holdingmng');
$PAGE->requires->string_for_js('remove','local_holdingmng');

// call tabulator

$PAGE->requires->css("/local/help/scss/datatable.css");
$PAGE->requires->js_call_amd('local_holdingmng/users', 'init',  array($holdingid, $sort, $dir, $page, $perpage));

// css tabulator
// $PAGE->requires->css('/local/holdingmng/css/tabulator.min.css');

$renderer = $PAGE->get_renderer('local_holdingmng');

// Header
$header = [];
$header['title'] = get_string('title', 'local_holdingmng');
$header['description'] = get_string('description', 'local_holdingmng');

// Alert
$alert = ['message' => get_string('nopermissions', 'local_holdingmng')];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname','local_holdingmng'));

if(is_siteadmin() or has_capability('local/holdingmng:view', context_system::instance())){
	$html = "";
	$table = "";
    $html .= $renderer->render_table($table);
    
    //Fecha actual
    $time = new DateTime("now", core_date::get_user_timezone_object());
    $now = $time->getTimestamp();
    $table = 'holding';

    /****************************************************************
      Formulario de alta
    *****************************************************************/
 
	//Instantiate holdingmng_form 
	$mform = new holdingusers_form($holdingid);
	 
	//Form processing and displaying is done here
	if ($mform->is_cancelled()) {
	    //Handle form cancel operation, if cancel button is present on form
	    //show buttom to add more users and back to holdings
		$data = [];

		$link = new moodle_url('/local/holdingmng/holdings.php', array('holdingid' => $holdingid, 'action' => 'view'));
		$strlink = get_string('backtoholdings', 'local_holdingmng');
		$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];

		$link = new moodle_url('/local/holdingmng/users.php', array('holdingid' => $holdingid, 'action' => 'create'));
		$strlink = get_string('addusers', 'local_holdingmng');
		$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];
		$html .= $renderer->render_menu($data);
		
	} else if ($fromform = $mform->get_data()) {

		//fecha actual
	    $time = new DateTime("now", core_date::get_user_timezone_object());
	    $now = $time->getTimestamp();

	    //message
	    $errormessage = get_string('addusererror', 'local_holdingmng');
		$okmessage = get_string('adduserok', 'local_holdingmng');

		$holdingid = $fromform->holdingid;
		$users = (isset($fromform->userid))?$fromform->userid:[];
		if($holdingid){
			if(is_array($users)){
				foreach ($users as $userid) {
					$newrow = new stdclass();
					$newrow->holdingid = $holdingid;
					$newrow->userid = intval($userid);
					$newrow->timecreated = $now;
					$newrow->timemodified = $now;

					/*
					* Ugly hack !!
					* Get last id from table holding_users
					*/
					/*
					$lastrecord = $DB->get_records("holding_users", ['holdingid'=>$holdingid], $sort='id desc', $fields='id', 0, 1);
					var_dump($lastrecord);
					if(is_array($lastrecord)){
						//$lastid = intval($lastrecord[0]->id);
						$lastid = intval(key($lastrecord));
						if($lastid){
							$newid = $lastid++;
							$newrow->id = $newid;
							var_dump($newrow);
							//save
							$newrecordid = $DB->insert_record("holding_users", $newrow, true);
						    if($newrecordid){
						    	$html .= $renderer->render_alert(['message'=>$okmessage]);
						    }else{
						    	$html .= $renderer->render_alert(['message'=>$errormessage]);
						    }
						}
					}else{
				    	$html .= $renderer->render_alert(['message'=>$errormessage]);
				    }
				    */
				    $newrecordid = $DB->insert_record("holding_users", $newrow, true);
				    if($newrecordid){
				    	$html .= $renderer->render_alert(['message'=>$okmessage]);
				    }else{
				    	$html .= $renderer->render_alert(['message'=>$errormessage]);
				    }
				}
			}
		}

		//show buttom to add more users and back to holdings
		$data = [];

		$link = new moodle_url('/local/holdingmng/holdings.php', array('holdingid' => $holdingid, 'action' => 'view'));
		$strlink = get_string('backtoholdings', 'local_holdingmng');
		$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];

		$link = new moodle_url('/local/holdingmng/users.php', array('holdingid' => $holdingid, 'action' => 'create'));
		$strlink = get_string('addusers', 'local_holdingmng');
		$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];
		$html .= $renderer->render_menu($data);

	} else {

		//Display Forms
		if($holdingid and $action == 'create'){
			$holdingobj = $DB->get_record($table, ['id'=>$holdingid]);

			$html .= html_writer::start_tag('h2', []);
			$html .= get_string('addusers', 'local_holdingmng');
			$html .= html_writer::end_tag('h2');

			if($holdingobj){

				$html .= html_writer::start_tag('p', []);
				$html .= get_string('holding', 'local_holdingmng').": ";
				$html .= $holdingobj->name;
				$html .= html_writer::end_tag('p');

				$data = new stdclass();
				$data->name = $holdingobj->name;
				$data->timecreated = $holdingobj->timecreated;
				$data->timemodified = $now;
				$data->holdingid = $holdingid;
				$data->action = 'create';
				$mform->set_data($data);
				$html .= $mform->render();
			}
		}elseif($holdingid and $action == 'view'){

			$data = [];
			$link = new moodle_url('/local/holdingmng/holdings.php', array('holdingid' => $holdingid, 'action' => 'view'));
			$strlink = get_string('backtoholdings', 'local_holdingmng');
			$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];

			$link = new moodle_url('/local/holdingmng/users.php', array('holdingid' => $holdingid, 'action' => 'create'));
			$strlink = get_string('addusers', 'local_holdingmng');
			$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];
			$html .= $renderer->render_menu($data);

		}elseif($holdingid and !empty($removeuser)){

			if($removeuser == 'ok'){
				$alert = ['message' => get_string('deleteuser', 'holdingmng')];
			}
			if($removeuser == 'fail'){
				$alert = ['message' => get_string('deleteusererror', 'holdingmng')];
			}
			
			$data = [];

			$link = new moodle_url('/local/holdingmng/holdings.php', array('holdingid' => $holdingid, 'action' => 'view'));
			$strlink = get_string('backtoholdings', 'local_holdingmng');
			$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];

			$link = new moodle_url('/local/holdingmng/users.php', array('holdingid' => $holdingid, 'action' => 'create'));
			$strlink = get_string('addusers', 'local_holdingmng');
			$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];
			$html .= $renderer->render_menu($data);
			$html .= $renderer->render_alert($alert);
		}
	}
}else{
    $html = $renderer->render_alert($alert);
}
echo $html;
echo $OUTPUT->footer();