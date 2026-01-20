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
global $PAGE, $OUTPUT, $CFG, $USER, $DB;

defined('MOODLE_INTERNAL') || die();
require_login();

$holdingid = required_param('holdingid', PARAM_INT); 
$sort = optional_param('sort', 'userid', PARAM_TEXT); 
$dir = optional_param('dir', 'asc', PARAM_TEXT); 
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('size', 0, PARAM_INT);
$table = 'holding_users';

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$output = $PAGE->get_renderer('core');

$total = $DB->count_records($table, ["holdingid"=>$holdingid]);
// $total =   ($total) ? ceil($total / $perpage) : 1; //ultima pagina
// $records = $DB->get_records($table, ["holdingid"=>$holdingid], $sort, '*', ($page-1)*$perpage, $perpage);

$records = $DB->get_records($table, ["holdingid"=>$holdingid], $sort, '*');

$rows = [];
if(is_array($records) && count($records)){
	foreach ($records as $record) {

		//Deleteaction
		$deluserparam = ['userid' => $record->userid, 'holdingid' => $holdingid];
		$deletelink = '';
		$deletelink .= '<a id="'.$record->userid.'_'.$holdingid.'" 
		                   userid="'. $record->userid.'" 
		                   holdingid="'. $holdingid.'"
		                   href="javascript:void(0);"
					       class="item-delete">'.get_string('deleteuserstr', 'local_holdingmng').'</a>';

		//User
		$user = $DB->get_record('user', ['id'=>$record->userid]);
		$userlink = '';
		$userlink .= html_writer::start_tag('p', array());
		$userlink .= html_writer::link(new moodle_url('/user/profile.php', array('id' => $record->userid)),
		      fullname($user), ['target'=>'_blank']);
		$userlink .= html_writer::end_tag('p');

		//holding name
		$holdingname = '';
		$holding = $DB->get_record('holding', ['id'=>$record->holdingid]);
		if($holding){
			$holdingname = $holding->name;
		}

		$row = [];
		$row['holding'] = $holdingname;
		$row['user'] = $userlink;
		$row['deletestr'] = $deletelink;
		$rows[] = $row;
	}
}
if(!empty($rows)){
	echo(json_encode(["last_page"=>$total, "data"=>$rows]));
}else{
	echo(json_encode(["last_page"=>$total, "data"=>[]]));
}