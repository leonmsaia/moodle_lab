<?php
//(12/11/2019 FHS)
//Form to create a new user

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
 * User sign-up form.
 *
 * @package    core
 * @subpackage auth
 * @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_eabcattendance;

use coding_exception;
use dml_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot . '/user/editlib.php');

class enroll_form extends \moodleform {

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    function definition() {
        $mform = $this->_form;

        $filtercontrols = $this->_customdata["filtercontrols"];

        $mform->addElement('header', 'createuserandpass', get_string('enrolluser', 'eabcattendance'), '');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'tname');
        $mform->setType('tname', PARAM_RAW);
        $mform->addElement('hidden', 'tipodoc');
        $mform->setType('tipodoc', PARAM_RAW);
        $mform->addElement('hidden', 'empresarazonsocial');
        $mform->setType('empresarazonsocial', PARAM_RAW);

        $mform->addElement('hidden', 'user_id');
        $mform->setType('user_id', PARAM_TEXT);
        

        $mform->addElement('text', 'apellidomaterno', "Apellido materno");
        $mform->setType('apellidomaterno', PARAM_RAW);
        $mform->addRule('apellidomaterno', get_string('required'), 'required', null, 'client');

        $mform->addElement('select', 'pais', "Nacionalidad", [1 => "Chilena", 2 => "Extranjera"]);
        $mform->addElement('select', 'participantesexo', "Género", [1 => "M", 2 => "F", 3 => "O"]);

        $mform->addElement('date_selector', 'participantefechanacimiento', "Fecha de nacimiento");

        \mod_eabcattendance\metodos_comunes::add_extrafields($mform);

        if($filtercontrols->get_sess_groups_list()){
            if(is_siteadmin()) {
                $options = $filtercontrols->get_sess_groups_list();
            } else {
                $options = \mod_eabcattendance\utils\frontutils::get_my_groups_selector();
            }
            $select = $mform->addElement('select', 'group', get_string('groups', 'eabcattendance'), $options);
            $select->setSelected($filtercontrols->get_current_sesstype());
        }
		
        // button
        $this->add_action_buttons(false, get_string('enrolluser', 'eabcattendance'));

    }

    public function export_for_template(renderer_base $output) {
        ob_start();
        $this->display();
        $formhtml = ob_get_contents();
        ob_end_clean();
        $context = [
            'formhtml' => $formhtml
        ];
        return $context;
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!preg_match('/^[0-9]*$/', $data['nroadherente'])) {
            $errors['nroadherente'] = "Solo permite números";
        } 
        return $errors;
    }

}
