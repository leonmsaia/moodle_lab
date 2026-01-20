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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Upload Course History File Form (Step 1)
 *
 * @package   tool_eabcetlbridge
 * @category  forms
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_history_upload_form extends \moodleform
{

    public function definition()
    {
        $mform = $this->_form;

        if (isset($this->_customdata)) {
            $features = $this->_customdata;
        } else {
            $features = array();
        }

        $mform->addElement('header', 'general', 'Subir archivo de historial');

        // File upload.
        $mform->addElement('filepicker', 'userfile', get_string('file'), null, array('accepted_types' => ['.csv', '.txt']));
        $mform->addRule('userfile', null, 'required');

        $encodings = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $encodings);
        $mform->setDefault('encoding', 'UTF-8');

        $choices = \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        $mform->setDefault('delimiter', 'comma');

        $options = array('10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '100000' => 100000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'grades'), $options);
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(false, 'Subir y Previsualizar');
    }
}
