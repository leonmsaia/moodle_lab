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

$holdingid = optional_param('holdingid', 0, PARAM_INT);

require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('title','local_holdingmng'));
$PAGE->set_url(new moodle_url($CFG->wwwroot .'/local/holdingmng/holdings.php'));

if(is_siteadmin() or has_capability('local/holdingmng:delete', context_system::instance())){
    if($holdingid){

    	/*
    	********************************************************** 
    	* Proceso de borrado
    	**********************************************************
    	*/ 
    	//Primero borro asignaciones de usuarios al holding
    	$DB->delete_records("holding_users", ['holdingid'=>$holdingid]);

    	//Luego borro asignaciones de compañias al holding
    	$DB->delete_records("holding_companies", ['holdingid'=>$holdingid]);

	    //Por último borro el registro holding
	    $DB->delete_records("holding", ['id'=>$holdingid]);

	    $record = $DB->get_record("holding", ['id'=>$holdingid]);
	    if(!$record){
            echo json_encode(["message"=>"ok"]);
	    }else{
            echo json_encode(["message"=>"fail"]);
	    }
    }else{
    	echo json_encode(["message"=>"fail"]);
    }

}else{
   echo json_encode(["message"=>"fail"]);
}