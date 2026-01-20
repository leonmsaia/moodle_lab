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

require_once("$CFG->libdir/formslib.php");
 
class holdingusers_form extends moodleform {

     /** @var int */
     protected $holdingid;

    public function __construct($holdingid) {
        $this->holdingid = $holdingid;
        parent::__construct();
    }

    public function definition() {
        global $CFG, $DB;
 
        $mform = $this->_form;

	//usuarios
	/* Esta query genera demasiados registros en mutual
	 * se modifica para que limite la búsqueda a usuarios con el rol
	 * holding previamente asignados

        $query = "select * from {user} 
                  where username <> 'guest' 
                  and deleted = 0
                  and id not in (select userid 
                                     from {holding_users} 
				     where holdingid = ".$this->holdingid.")";
        */

	//usuarios
        $query = "select * from {user} 
                  where username <> 'guest' 
                  and deleted = 0
                  and id not in (select userid 
                                     from {holding_users} 
                                     where holdingid = ".$this->holdingid.")
                  and id in (select userid from {role_assignments} 
                                 where contextid = 1 
                                 and roleid in (select id from {role} where shortname like '%holding%'))";


        $users = $DB->get_records_sql($query);

        $options = array(                                                                                                           
            'multiple' => true,                                                  
            'noselectionstring' => get_string('selectuser', 'local_holdingmng'),                                                                
        );

        $records = [];
        foreach ($users as $userid => $user) {
		$records[$userid] = fullname($user).", ".$user->username.", ".$user->email;
        }

        $mform->addElement('autocomplete', 'userid', get_string('user', 'local_holdingmng'), $records, $options);
        $mform->setType('userid', PARAM_RAW);
        $mform->addElement('hidden', 'action', '');
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden', 'holdingid', 0);
        $mform->setType('holdingid', PARAM_INT);
        $mform->addElement('submit', 'submitbutton', get_string('savechanges'));
        $mform->addElement('cancel');
    }

    //Custom validation should be added here
    function validation($data, $files) {
        return [];
    }
}
