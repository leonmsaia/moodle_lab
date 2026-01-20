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

namespace tool_eabcetlbridge\forms;

use core\form\persistent;

/**
 * Base persistent form
 *
 * @package   tool_eabcetlbridge
 * @category  forms
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class base_persistent_form extends persistent {

    /**
     * Define fields elements.
     * @return array
     */
    protected $fields = [];

    /**
     * Main header.
     * @return void
     */
    protected function add_field_main_header() {
        $mform = $this->_form;
        $mform->addElement('header', 'header_general', get_string('general'));
    }

    /**
     * Description header.
     * @return void
     */
    protected function add_field_description_header() {
        $mform = $this->_form;
        $mform->addElement('header', 'header_description', get_string('description'));
    }

    /**
     * User field ID.
     * @return void
     */
    protected function add_field_user() {
        global $USER;
        $userid = $this->_customdata['userid'] ?? $USER->id;
        $mform = $this->_form;
        $mform->addElement('hidden', 'userid');
        $mform->setConstant('userid', $userid);
        $mform->setType('userid', PARAM_INT);
    }

    /**
     * Name.
     * @return void
     */
    protected function add_field_name() {
        $mform = $this->_form;
        $mform->addElement('text', 'name', get_string('name'), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);
    }

    /**
     * Shortname.
     * @return void
     */
    protected function add_field_shortname() {
        $mform = $this->_form;
        $mform->addElement('text', 'shortname', get_string('shortname'), 'maxlength="254" size="50"');
        $mform->addRule('shortname', null, 'required', null, 'client');
        $mform->setType('shortname', PARAM_TEXT);
    }

    /**
     * Confirm field.
     * @return void
     */
    protected function add_confirm_start() {
        global $OUTPUT;
        $mform = $this->_form;

        // HTML Modal.
        $message = $this->_customdata['confirmdata']['message'] ?? get_string('confirm_update', 'tool_eabcetlbridge');
        $confirmtitle = $this->_customdata['confirmdata']['confirmtitle'] ?? get_string('confirm_update', 'tool_eabcetlbridge');
        $submitstr = $this->_customdata['confirmdata']['submitstr'] ?? get_string('savechanges');
        $submitbutton = $mform->createElement('submit', 'submitbutton', $submitstr, 'class="btn btn-primary"');
        $submitbutton = $submitbutton->toHtml();
        $html = $OUTPUT->render_from_template('tool_eabcetlbridge/confirm-edition', [
            'submitbutton' => $submitbutton,
            'message' => $message,
            'confirmtitle' => $confirmtitle,
            'cancel' => get_string('cancel')
        ]);
        $mform->addElement('html', $html);

    }

    /**
     * Confirm field.
     * @return void
     */
    protected function add_confirm_end() {
        $mform = $this->_form;

        // Normal button, and button to activate modal.
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('button', 'submitbutton', get_string('savechanges'),
                'data-toggle="modal" data-target="#confirm_update_element"');
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
        $mform->closeHeaderBefore('submitbutton');
    }

    /**
     * Form Definition.
     *
     * For adding a new field
     * 1. Override $fields and add the new field "newfieldname".
     * 2. Include method add_field_newfieldname();
     * @return void
     */
    public function definition() {

        $this->add_confirm_start();

        foreach ($this->fields as $field) {
            $methodname = 'add_field_' . $field;
            if (method_exists($this, $methodname)) {
                $this->$methodname();
            }
        }

        $data = $this->_customdata['data'];
        $this->set_data($data);

        $idnumber = $this->_customdata['data']->id ?? false;
        if ($idnumber) {
            $this->add_confirm_end();
        } else {
            $this->add_action_buttons();
        }
    }

}
