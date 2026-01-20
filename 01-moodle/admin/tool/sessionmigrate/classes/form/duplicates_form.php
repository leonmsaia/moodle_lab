<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute and/or modify
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
 * Duplicate sessions search form.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_sessionmigrate\form;

defined('MOODLE_INTERNAL') || die();
use moodleform;

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class duplicates_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $searchtypeoptions = [
            'grupoid' => get_string('searchbygrupoid', 'tool_sessionmigrate'),
            'idevento' => get_string('searchbyidevento', 'tool_sessionmigrate'),
            'sessionguid' => get_string('searchbysessionguid', 'tool_sessionmigrate'),
        ];

        $mform->addElement('select', 'searchtype', get_string('searchtype', 'tool_sessionmigrate'), $searchtypeoptions);
        $mform->setDefault('searchtype', 'grupoid');

        $mform->addElement('text', 'searchvalue', get_string('searchvalue', 'tool_sessionmigrate'));
        $mform->setType('searchvalue', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('search'));
    }
}
