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

namespace tool_sessionmigrate\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Form for migrating sessions by GUIDs.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migrationbysessions_form extends \moodleform {
    /**
     * {@inheritdoc}
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('textarea', 'sessionguids', get_string('sessionguids', 'tool_sessionmigrate'), 'wrap="virtual" rows="15" cols="50"');
        $mform->setType('sessionguids', PARAM_RAW);
        $mform->addRule('sessionguids', null, 'required');

        $this->add_action_buttons(false, get_string('migratesessions', 'tool_sessionmigrate'));
    }
}
