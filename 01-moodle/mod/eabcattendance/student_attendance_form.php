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
 * Student form class.
 *
 * @package    mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');

/**
 * Class mod_eabcattendance_student_eabcattendance_form
 *
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_eabcattendance_student_eabcattendance_form extends moodleform {
    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        global $USER;

        $mform  =& $this->_form;

        $attforsession = $this->_customdata['session'];
        $attblock = $this->_customdata['eabcattendance'];
        $password = $this->_customdata['password'];

        $statuses = $attblock->get_statuses();
        // Check if user has access to all statuses.
        $disabledduetotime = false;
        foreach ($statuses as $status) {
            if ($status->studentavailability === '0') {
                unset($statuses[$status->id]);
            }
            if (!empty($status->studentavailability) &&
                time() > $attforsession->sessdate + ($status->studentavailability * 60)) {
                unset($statuses[$status->id]);
                $disabledduetotime = true;
            }
        }

        $mform->addElement('hidden', 'sessid', null);
        $mform->setType('sessid', PARAM_INT);
        $mform->setConstant('sessid', $attforsession->id);

        $mform->addElement('hidden', 'sesskey', null);
        $mform->setType('sesskey', PARAM_INT);
        $mform->setConstant('sesskey', sesskey());

        // Set a title as the date and time of the session.
        $sesstiontitle = userdate($attforsession->sessdate, get_string('strftimedate')).' '
                .eabcattendance_strftimehm($attforsession->sessdate);

        $mform->addElement('header', 'session', $sesstiontitle);

        // If a session description is set display it.
        if (!empty($attforsession->description)) {
            $mform->addElement('html', $attforsession->description);
        }
        if (!empty($attforsession->studentpassword)) {
            $mform->addElement('text', 'studentpassword', get_string('password', 'eabcattendance'));
            $mform->setType('studentpassword', PARAM_TEXT);
            $mform->addRule('studentpassword', get_string('passwordrequired', 'eabcattendance'), 'required');
            $mform->setDefault('studentpassword', $password);
        }
        if (!$attforsession->autoassignstatus) {

            // Create radio buttons for setting the eabcattendance status.
            $radioarray = array();
            foreach ($statuses as $status) {
                $name = html_writer::span($status->description, 'statusdesc');
                $radioarray[] =& $mform->createElement('radio', 'status', '', $name, $status->id, array());
            }
            if ($disabledduetotime) {
                $warning = html_writer::span(get_string('somedisabledstatus', 'eabcattendance'), 'somedisabledstatus');
                $radioarray[] =& $mform->createElement('static', '', '', $warning);
            }
            // Add the radio buttons as a control with the user's name in front.
            $radiogroup = $mform->addGroup($radioarray, 'statusarray', $USER->firstname.' '.$USER->lastname.':', array(''), false);
            $radiogroup->setAttributes(array('class' => 'statusgroup'));
            $mform->addRule('statusarray', get_string('eabcattendancenotset', 'eabcattendance'), 'required', '', 'client', false, false);
        }
        $this->add_action_buttons();
    }

    /**
     * Validate Form.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = array();
        if (!($this->_customdata['session']->autoassignstatus)) {
            // Check if this status is allowed to be set.
            if (empty($data['status'])) {
                $errors['statusarray'] = get_string('invalidstatus', 'eabcattendance');
            }
        }

        return $errors;
    }
}