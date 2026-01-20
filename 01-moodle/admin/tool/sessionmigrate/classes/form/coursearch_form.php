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
 * Defines the course search form for the session migration tool.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_sessionmigrate\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class coursearch_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'productoid', get_string('productoid', 'tool_sessionmigrate'));
        $mform->setType('productoid', PARAM_TEXT);

        $mform->addElement('text', 'shortname', get_string('shortname', 'tool_sessionmigrate'));
        $mform->setType('shortname', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('search', 'tool_sessionmigrate'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['productoid']) && empty($data['shortname'])) {
            $errors['productoid'] = get_string('err_atleastonefield', 'tool_sessionmigrate');
            $errors['shortname'] = get_string('err_atleastonefield', 'tool_sessionmigrate');
        }

        return $errors;
    }
}
