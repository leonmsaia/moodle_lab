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

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/editlib.php');

use moodleform;
use core\url;
use core\output\html_writer;
use csv_import_reader;
use core_text;
use tool_eabcetlbridge\strategies\base_strategy;

/**
 * Upload a CVS file with information.
 *
 * @package   tool_eabcetlbridge
 * @category  forms
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_form extends moodleform {

    /**
     * Sample file
     * @var string
     */
    protected $samplefile = 'example_upload.csv';

    /**
     * Submit label
     * @var string
     */
    protected $submitlabel = null;

    /**
     * Constructor
     * @param mixed $action
     * @param mixed $customdata
     * @param string $method
     * @param string $target
     * @param mixed $attributes
     * @param bool $editable
     * @param array $ajaxformdata
     * @param string $samplefile
     * @param string $submitlabel
     * @return void
     */
    public function __construct(
            $action = null,
            $customdata = null,
            $method = 'post',
            $target = '',
            $attributes = null,
            $editable = true,
            $ajaxformdata=null,
            $samplefile = 'example_upload.csv',
            $submitlabel = null ) {

        $this->samplefile = $samplefile;

        $this->submitlabel = $submitlabel;

        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Definition of the form
     */
    public function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('upload'));

        // Example CSV file field.
        $filetitle = $this->samplefile;
        $url = new url('samplefiles/' . $filetitle);
        $link = html_writer::link($url, $filetitle);
        $mform->addElement('static', 'examplecsv', get_string('examplecsv', 'tool_uploaduser'), $link);
        $mform->addHelpButton('examplecsv', 'examplecsv', 'tool_uploaduser');

        // User file field.
        $mform->addElement('filepicker', 'file', get_string('file'));
        $mform->addRule('file', null, 'required');

        // Delimiter name field.
        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        // Encoding field.
        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        // Preview rows field.
        $choices = array( '10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '5000' => 5000);
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'tool_uploaduser'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        $choices = \tool_eabcetlbridge\persistents\configs::get_configs_for_manual_upload();
        $mform->addElement('select', 'configid', get_string('form_strategy', 'tool_eabcetlbridge'), $choices);
        $mform->setType('configid', PARAM_TEXT);

        $this->add_action_buttons(true, $this->submitlabel);
    }

    /**
     * Returns list of elements and their default values, to be used in CLI
     *
     * @return array
     */
    public function get_form_for_cli() {
        $elements = array_filter($this->_form->_elements, function($element) {
            return !in_array($element->getName(), ['buttonar', 'memberfile', 'previewrows']);
        });
        return [$elements, $this->_form->_defaultValues];
    }
}
