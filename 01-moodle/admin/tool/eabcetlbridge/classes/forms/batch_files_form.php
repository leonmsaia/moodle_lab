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

global $CFG;

require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/repository/lib.php');

use core_text;
use csv_import_reader;
use tool_eabcetlbridge\persistents\batch_files as persistent;

/**
 * Upload a CVS file with information.
 *
 * @package   tool_eabcetlbridge
 * @category  forms
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class batch_files_form extends base_persistent_form {

    protected static $persistentclass = 'tool_eabcetlbridge\\persistents\\batch_files';

    protected static $foreignfields = ['file'];

    /**
     * Define fields elements.
     * @return array
     */
    protected $fields = array(
        'main_header',
        'user',
        'file_filemanager',
        'component',
        'filearea',
        'delimiter',
        'encoding',
        'configid',
        'courseid',
        'status',
    );

    /**
     * Adds a filemanager element to the form, which requires a file to be chosen.
     */
    protected function add_field_file_filemanager() {
        global $SITE;
        $mform = $this->_form;
        $fileoptions = [
            'subdirs' => 0,
            'maxbytes' => $SITE->maxbytes,
            'maxfiles' => 1,
            'accepted_types' => ['.csv'],
            'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
        ];
        $mform->addElement('filemanager', 'file', get_string('file'), null, $fileoptions);
        if (empty($this->_customdata['data']->id)) {
            $mform->addRule('file', null, 'required');
        }
    }

    /**
     * Adds a static element to the form to display the component name.
     * This is a static element because the component name is not editable by the user.
     * The element will contain the string 'column_component' translated and the value of the component name.
     */
    protected function add_field_component() {
        $mform = $this->_form;
        $mform->addElement('static', 'component', get_string('column_component', 'tool_eabcetlbridge'), persistent::COMPONENT);
    }

    /**
     * Adds a static element to the form to display the file area name.
     * This is a static element because the file area name is not editable by the user.
     * The element will contain the string 'column_filearea' translated and the value of the file area name.
     *
     * @return void
     */
    protected function add_field_filearea() {
        $mform = $this->_form;
        $mform->addElement('static', 'filearea', get_string('column_filearea', 'tool_eabcetlbridge'), persistent::FILEAREA);
    }

    /**
     * Add a select element to the form to select the delimiter of the uploaded file.
     * The select element will contain all the available delimiters.
     * @return void
     */
    protected function add_field_delimiter() {
        $mform = $this->_form;
        // Delimiter name field.
        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
    }

    /**
     * Add a select element to the form to select the encoding of the uploaded file.
     * The select element will contain all the available encodings.
     * The default encoding is UTF-8.
     * @return void
     */
    protected function add_field_encoding() {
        $mform = $this->_form;
        // Encoding field.
        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');
    }

    /**
     * Add a select element to the form to select a configid.
     * The select element will contain all the available configids for manual upload.
     * @return void
     */
    protected function add_field_configid() {
        $mform = $this->_form;
        $choices = \tool_eabcetlbridge\persistents\configs::get_configs_for_manual_upload();
        $mform->addElement('select', 'configid', get_string('form_strategy', 'tool_eabcetlbridge'), $choices);
        $mform->setType('configid', PARAM_TEXT);
    }

    /**
     * Adds a course element to the form to select a course to upload the information to.
     * The course element will allow the user to select a single course to upload the information to.
     * The course element will not include the front page in the list of available courses.
     * @return void
     */
    protected function add_field_courseid() {
        $mform = $this->_form;
        $options = array(
            'multiple' => false,
            'includefrontpage' => false,
            'showhidden' => true
        );
        $mform->addElement('course', 'courseid', get_string('courses'), $options);
    }

    /**
     * Status.
     * @return void
     */
    protected function add_field_status() {
        $mform = $this->_form;
        $choices = \tool_eabcetlbridge\persistents\batch_files::get_status_for_manual_upload();
        $mform->addElement(
            'select',
            'status',
            'Estado inicial luego de la subida',
            $choices,
            persistent::STATUS_PREVIEW
        );
        $mform->setType('status', PARAM_TEXT);
    }

    /**
     * Set form data.
     *
     * @param mixed $data
     * @param bool $clean
     * @return bool
     */
    public function set_data($data, $clean = true) {
        /*if (!empty($data->id)) {
            $itemid = $data->file_filemanager;
            $context = \core\context\system::instance();
            \file_prepare_draft_area($itemid, $context->id, 'tool_eabcetlbridge', 'batchfile', $data->id, ['subdirs' => 0]);
            $data->file_filemanager = $itemid;
        }*/
        return parent::set_data($data, $clean);
    }
}
