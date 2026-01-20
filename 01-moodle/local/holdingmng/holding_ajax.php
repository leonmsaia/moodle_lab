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

$holdingid = optional_param('holdingid', 0, PARAM_INT); 
$sort = optional_param('sort', 'name', PARAM_TEXT); 
$dir = optional_param('dir', 'asc', PARAM_TEXT); 
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('size', 0, PARAM_INT);
$table = 'holding';

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$output = $PAGE->get_renderer('core');

$total = $DB->count_records($table);
// $total =   ($total) ? ceil($total / $perpage) : 1; //ultima pagina
// $records = $DB->get_records($table, null, $sort, '*', ($page-1)*$perpage, $perpage);
$records = $DB->get_records($table, null, $sort, '*' );


$rows = [];
if(is_array($records) && count($records)){
	foreach ($records as $record) {
		
		//Edit action
		$editlink = '';
		$editlink .= html_writer::start_tag('p', array());
		$editlink .= html_writer::link(new moodle_url('/local/holdingmng/holdings.php', 
			                                          array('holdingid' => $record->id, 
			                                          	    'action'=>'edit')),
		      get_string('edit', 'local_holdingmng'), []);
		$editlink .= html_writer::end_tag('p');

		//Deleteaction
		$deletelink = '';
		$deletelink .= '<a id="'.$record->id.'" 
		                   holdingid="'. $record->id.'"
		                   href="javascript:void(0);"
					       class="item-delete">'.get_string('deletestr', 'local_holdingmng').'</a>';

		//View users
		$adduserlink = '';
		$adduserlink .= html_writer::start_tag('p', array());
		$adduserlink .= html_writer::link(new moodle_url('/local/holdingmng/users.php', 
			                             array('holdingid' => $record->id, 'action'=>'view')),
		      get_string('viewusers', 'local_holdingmng'), []);
		$adduserlink .= html_writer::end_tag('p');

		//View companies
		$addcompanylink = '';
		$addcompanylink .= html_writer::start_tag('p', array());
		$addcompanylink .= html_writer::link(new moodle_url('/local/holdingmng/companies.php', 
			                             array('holdingid' => $record->id, 'action'=>'view')),
		      get_string('viewcompanies', 'local_holdingmng'), []);
		$adduserlink .= html_writer::end_tag('p');

		$row = [];
		$row['id'] = $record->id;
		$row['name'] = $record->name;
		$row['addusers'] = $adduserlink;
		$row['addcompanies'] = $addcompanylink;
		$row['edit'] = $editlink;
		$row['deletestr'] = $deletelink;
		$rows[] = $row;
	}
}
if(!empty($rows)){
	echo(json_encode(["last_page"=>$total, "data"=>$rows]));
}else{
	echo(json_encode(["last_page"=>$total, "data"=>[]]));
}