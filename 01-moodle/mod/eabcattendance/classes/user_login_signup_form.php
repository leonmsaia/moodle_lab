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
use renderer_base;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot . '/user/editlib.php');


class user_login_signup_form extends \moodleform {


    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    function definition() {
        global $USER, $CFG;

        $mform = $this->_form;

		$filtercontrols = $this->_customdata["filtercontrols"];

        /* echo print_r($filtercontrols, true); */

		$mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'tname');
        $mform->setType('tname', PARAM_RAW);
        $mform->addElement('hidden', 'tipodoc');
        $mform->setType('tipodoc', PARAM_RAW);
        $mform->addElement('hidden', 'empresarazonsocial');
        $mform->setType('empresarazonsocial', PARAM_RAW);
        
        $mform->addElement('hidden', 'username');
        $mform->setType('username', PARAM_RAW);
        $mform->addRule('username', get_string('missingusername'), 'required', null, 'client');
        
        if (!empty($CFG->passwordpolicy)){
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }
        
        $mform->addElement('hidden', 'password');
        $mform->setType('password', PARAM_TEXT);
        $mform->addRule('password', get_string('missingpassword'), 'required', null, 'client');

        $mform->addElement('text', 'firstname', get_string("firstname"));
        $mform->setType('firstname', PARAM_RAW);
        $mform->addRule('firstname', get_string('missingfirstname'), 'required', null, 'client');

        $mform->addElement('text', 'lastname', get_string("apellidopaterno",'eabcattendance'));
        $mform->setType('lastname', PARAM_RAW);
        $mform->addRule('lastname', get_string('missinglastname'), 'required', null, 'client');

        $mform->addElement('text', 'apellidomaterno', "Apellido materno");
        $mform->setType('apellidomaterno', PARAM_RAW);
        $mform->addRule('apellidomaterno', get_string('required'), 'required', null, 'client');


        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="25"');
        $mform->setType('email', PARAM_TEXT);
        $mform->addRule('email', get_string('missingemail'), 'email', '', 'client', false, false);
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'client');
        $mform->setForceLtr('email');

        $mform->addElement('text', 'city', get_string('city'), 'maxlength="120" size="20"');
        $mform->setType('city', PARAM_TEXT);
        if (!empty($CFG->defaultcity)) {
            $mform->setDefault('city', $CFG->defaultcity);
        }


        $mform->addElement('select', 'pais', "Nacionalidad", [0=> 'Seleccione',1 => "Chilena", 2 => "Extranjera"]);
        $mform->addRule('pais', get_string('required'), 'required', null, 'client');
        $mform->addElement('select', 'participantesexo', "Sexo", [0=> 'Seleccione', 1 => "M", 2 => "F"]);
        $mform->addRule('participantesexo', get_string('required'), 'required', null, 'client');

        $mform->addElement('date_selector', 'participantefechanacimiento', "Fecha de nacimiento");


        if($filtercontrols->get_sess_groups_list()){
		    if(is_siteadmin()) {
		        $options = $filtercontrols->get_sess_groups_list();
            } else {
		        $options = \mod_eabcattendance\utils\frontutils::get_my_groups_selector();
            }
			$select = $mform->addElement('select', 'group', get_string('groups', 'eabcattendance'), $options);
			$select->setSelected($filtercontrols->get_current_sesstype());
		}

        \mod_eabcattendance\metodos_comunes::add_extrafields($mform);
		// button
     //   $this->add_action_buttons(false, get_string('createaccount'));
		$mform->addElement('submit', 'submitbutton', get_string('createaccount'));

    }

    function definition_after_data(){
        $mform = $this->_form;
        $mform->applyFilter('username', 'trim');
    }

    /**
     * Validate user supplied data on the signup form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if($data['participantesexo'] == 0){
            $errors['participantesexo'] = 'Sexo requerido';
        }
        if($data['pais'] == 0){
            $errors['pais'] = 'Nacionalidad requerida';
        }
        if(empty($data['apellidomaterno'])){
            $errors['apellidomaterno'] = 'Apellido Materno requerido';
        }
        if(empty($data['email'])){
            $errors['email'] =  get_string('notnull', 'eabcattendance');
        } else {
             if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                 $errors['email'] =  get_string('invalidemail', 'eabcattendance');
             }
        }
        if(empty($data['firstname'])){
            $errors['firstname'] =  get_string('invalidname', 'eabcattendance');
        }
        if(empty($data['lastname'])){
            $errors['lastname'] =  get_string('invalidsurname', 'eabcattendance');
        }
        if(empty($data['lastname'])){
            $errors['lastname'] =  get_string('invalidsurname', 'eabcattendance');
        }
        if (empty($data['group']) || $data['group'] == '0' || $data['group'] == '-1') {
            $errors['group'] = get_string('invalidgroup', 'eabcattendance');
        }
        if (empty($data['nroadherente'])) {
            $errors['nroadherente'] = get_string('notnull', 'eabcattendance');
        } else {
            if (!preg_match('/^[0-9]*$/', $data['nroadherente'])) {
                $errors['nroadherente'] = "Solo permite números";
            }
        }
        if (empty($data['empresarut'])) {
            $errors['group'] = get_string('notnull', 'eabcattendance');
        }
        return $errors;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return array
     */
    /*
    public function export_for_template(renderer_base $output) {
        ob_start();
        $this->display();
        $formhtml = ob_get_contents();
        ob_end_clean();
        return [
            'formhtml' => $formhtml
        ];
    }*/
}
