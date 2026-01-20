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

//use holdingmng\holdingmng_form;
require_once ('../../config.php');
global $PAGE, $OUTPUT, $CFG, $USER;
require_once ($CFG->dirroot .'/local/holdingmng/classes/holdingmng_form.php');

//tamaño de la página
define("HOLDINGPAGESIZE", 10);
$holdingid = optional_param('holdingid', 0, PARAM_INT);
$sort      = optional_param('sort', 'name', PARAM_TEXT);
$dir       = optional_param('dir', 'ASC', PARAM_TEXT);
$action    = optional_param('action', '', PARAM_TEXT);
$page      = optional_param('page', 0, PARAM_INT);
$perpage   = optional_param('perpage', HOLDINGPAGESIZE, PARAM_INT);

require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('title','local_holdingmng'));
$PAGE->set_url(new moodle_url($CFG->wwwroot .'/local/holdingmng/holdings.php'));

//strings js
$PAGE->requires->string_for_js('holdingid','local_holdingmng');
$PAGE->requires->string_for_js('holding','local_holdingmng');
$PAGE->requires->string_for_js('viewusers','local_holdingmng');
$PAGE->requires->string_for_js('viewcompanies','local_holdingmng');
$PAGE->requires->string_for_js('edit','local_holdingmng');
$PAGE->requires->string_for_js('deletestr','local_holdingmng');
$PAGE->requires->string_for_js('nodata','local_holdingmng');
$PAGE->requires->string_for_js('confirmtitleholding','local_holdingmng');
$PAGE->requires->string_for_js('confirmmessageholding','local_holdingmng');

$PAGE->requires->css("/local/help/scss/datatable.css");

// $PAGE->requires->js(new moodle_url('/local/holdingmng/js/tabulator.min.js'));

//call tabulator
$PAGE->requires->js_call_amd('local_holdingmng/holdings', 'init', 
		array($holdingid, $sort, $dir, $page, $perpage));
		

//css tabulator
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
    
    //fecha actual
    $time = new DateTime("now", core_date::get_user_timezone_object());
    $now = $time->getTimestamp();
    $table = 'holding';

    /****************************************************************
      Formulario de alta
    *****************************************************************/
 
	//Instantiate holdingmng_form 
	$mform = new holdingmng_form();
	 
	//Form processing and displaying is done here
	if ($mform->is_cancelled()) {
	    //Handle form cancel operation, if cancel button is present on form
	    // Create a new holding button
		$data = [];
		$link = $CFG->wwwroot .'/local/holdingmng/holdings.php?action=create';
		$strlink = get_string('createholding', 'local_holdingmng');
		$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];
		$html .= $renderer->render_menu($data);

	} else if ($fromform = $mform->get_data()) {

		// Actions 
		// Update
		$holdingid = $fromform->holdingid;
		$action = $fromform->action;
		if($holdingid && $action == 'edit'){

			$holdingobj = $DB->get_record($table, ['id'=>$holdingid]);
			if($holdingobj){
				$holdingobj->name = $fromform->name;
				$holdingobj->timemodified = $now;
				$holdingobj->action = '';
				$DB->update_record($table, $holdingobj);
				
				// Create a new holding button
				$data = [];
				$link = $CFG->wwwroot .'/local/holdingmng/holdings.php?action=create';
				$strlink = get_string('createholding', 'local_holdingmng');
				$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];

			    $html .= $renderer->render_alert(['message'=>get_string('update', 'local_holdingmng')]);
				$html .= $renderer->render_menu($data);
			}
		}else{
			// Create
			if($action == 'create'){
		        $errormessage = get_string('createerror', 'local_holdingmng');
		        $okmessage = get_string('createok', 'local_holdingmng');
			    $record = new stdclass();
			    $record->name = $fromform->name;
			    $record->timecreated = $now;
			    $record->timemodified = $now;
			    $newrecordid = $DB->insert_record($table, $record, true);
			    if($newrecordid){
			    	$html .= $renderer->render_alert(['message'=>$okmessage]);
			    }else{
			    	$html .= $renderer->render_alert(['message'=>$errormessage]);
			    }

				// Create a new holding button
				$data = [];
				$link = $CFG->wwwroot .'/local/holdingmng/holdings.php?action=create';
				$strlink = get_string('createholding', 'local_holdingmng');
				$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];
				$html .= $renderer->render_menu($data);
			}
		}
	} else {

		//Display Forms
	    //update
		if($holdingid && $action == 'edit'){
			$html .= html_writer::start_tag('h2', []);
			$html .= get_string('editholding', 'local_holdingmng');
			$html .= html_writer::end_tag('h2');
			$holdingobj = $DB->get_record($table, ['id'=>$holdingid]);
			if($holdingobj){
				$data = new stdclass();
				$data->name = $holdingobj->name;
				$data->timecreated = $holdingobj->timecreated;
				$data->timemodified = $now;
				$data->holdingid = $holdingid;
				$data->action = 'edit';
				$mform->set_data($data);
				$html .= $mform->render();
			}
		}else{

			if($action == 'create'){
				//Set default data (if any)
				$html .= html_writer::start_tag('h2', []);
				$html .= get_string('createholding', 'local_holdingmng');
				$html .= html_writer::end_tag('h2');
				$toform = new stdclass();
				$toform->name = get_string('defaultname', 'local_holdingmng');
				$toform->action = 'create';
				$mform->set_data($toform);
				$html .= $mform->render();
			}else{
				$data = [];
				$link = $CFG->wwwroot .'/local/holdingmng/holdings.php?action=create';
				$strlink = get_string('createholding', 'local_holdingmng');
				$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];
				$html .= $renderer->render_menu($data);
			}
		}
	}
}else{
    $html = $renderer->render_alert($alert);
}


echo $html;
echo $OUTPUT->footer();