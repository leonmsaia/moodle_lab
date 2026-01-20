<?php
// (08/11/2019 FHS)

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
 * Form for creating temporary users.
 *
 * @package    mod_eabcattendance
 * @copyright  2013 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_eabcattendance;


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/formslib.php');
use mod_eabcattendance\metodos_comunes;
/**
 * Class temp_form
 * @copyright  2013 Davo Smith, Synergy Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adduser_form extends \moodleform {
    /**
     * Define form.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'validaterut', '1');
        $mform->setType('validaterut', PARAM_INT);
        $mform->addElement('header', 'attheader', get_string('searchuser', 'eabcattendance'));
        $mform->addElement('text', 'tname', get_string('tusernamedocu', 'eabcattendance'));
        $mform->addElement('select', 'tipodoc', get_string('tusernametipo', 'eabcattendance'), [1 => "RUT", 2 => "Pasaporte"]);
        //$mform->addRule('tname', 'Required', 'required', null, 'client');
        $mform->setType('tname', PARAM_TEXT);

        $mform->addElement('submit', 'submitbutton', get_string('search', 'eabcattendance'));
        $mform->closeHeaderBefore('submit');
    }

    /**
     * Do stuff to form after creation.
     */
    public function definition_after_data() {
        $mform = $this->_form;
        $mform->applyFilter('tname', 'trim');
    }
    
    //Custom validation should be added here
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if(empty($data['tname'])){
            $errors['tname'] = get_string('notnull', 'eabcattendance');
        } else {
            if ($data['tipodoc'] == 1){
                if (metodos_comunes::validar_rut($data['tname']) == false) {
                    $errors['tname'] = get_string('invalidrutparticipante', 'eabcattendance');
                }
            }            
        }
        
        return $errors;
    }
       
}
