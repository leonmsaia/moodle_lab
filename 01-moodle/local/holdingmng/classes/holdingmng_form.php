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
 
class holdingmng_form extends moodleform {

    public function definition() {
        global $CFG;
 
        $mform = $this->_form;
        $mform->addElement('text', 'name', get_string('holding', 'local_holdingmng'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('hidden', 'action', '');
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden', 'holdingid', 0);
        $mform->setType('holdingid', PARAM_INT);
        $mform->addElement('submit', 'submitbutton', get_string('savechanges'));
        $mform->addElement('cancel');    
    }

    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}