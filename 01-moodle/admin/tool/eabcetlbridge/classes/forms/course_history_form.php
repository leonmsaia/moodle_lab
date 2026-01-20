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

use tool_eabcetlbridge\persistents\batch_files as persistent;
use tool_eabcetlbridge\persistents\configs;

/**
 * Upload a Course History CSV file.
 *
 * @package   tool_eabcetlbridge
 * @category  forms
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_history_form extends base_persistent_form
{

    protected static $persistentclass = 'tool_eabcetlbridge\\persistents\\batch_files';
    protected static $foreignfields = ['file'];

    /**
     * Define fields elements.
     * @return array
     */
    protected $fields = array(
        'main_header',
        'file_filemanager',
        'component',
        'filearea',
        'delimiter',
        'encoding',
        'configid',
        'status',
        'mapping_header',
        'map_username',
        'map_course',
        'map_grade',
        'map_startdate',
        'map_enddate',
    );

    /**
     * Adds a filemanager element to the form.
     */
    protected function add_field_file_filemanager()
    {
        global $SITE;
        $mform = $this->_form;
        $fileoptions = [
            'subdirs' => 0,
            'maxbytes' => $SITE->maxbytes,
            'maxfiles' => 1,
            'accepted_types' => ['.csv', '.txt'],
            'return_types' => FILE_INTERNAL | FILE_EXTERNAL,
        ];
        $mform->addElement('filemanager', 'file', get_string('file'), null, $fileoptions);
        if (empty($this->_customdata['data']->id)) {
            $mform->addRule('file', null, 'required');
        }
    }

    protected function add_field_component()
    {
        $mform = $this->_form;
        $mform->addElement('hidden', 'component', persistent::COMPONENT);
        $mform->setType('component', PARAM_TEXT);
    }

    protected function add_field_filearea()
    {
        $mform = $this->_form;
        $mform->addElement('hidden', 'filearea', persistent::FILEAREA);
        $mform->setType('filearea', PARAM_TEXT);
    }

    protected function add_field_delimiter()
    {
        $mform = $this->_form;
        $choices = \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
    }

    protected function add_field_encoding()
    {
        $mform = $this->_form;
        $choices = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');
    }

    protected function add_field_configid()
    {
        $mform = $this->_form;
        // Logic to get configs that use the course_history_strategy
        // For now, we get all manual upload configs, user must choose correct one.
        // Ideally filter by strategyclass 'tool_eabcetlbridge\strategies\course_history_strategy'
        // But get_configs_for_manual_upload returns all enabled.
        $choices = configs::get_configs_for_manual_upload();
        $mform->addElement('select', 'configid', get_string('form_strategy', 'tool_eabcetlbridge'), $choices);
        $mform->setType('configid', PARAM_INT);
        $mform->addRule('configid', null, 'required');
    }

    protected function add_field_mapping_header()
    {
        $mform = $this->_form;
        $mform->addElement('header', 'mapping_header', 'Mapeo de Columnas (Encabezados del CSV)');
    }

    protected function add_field_map_username()
    {
        $mform = $this->_form;
        $mform->addElement('text', 'map_username', 'Columna Usuario (RUT/ID)');
        $mform->setType('map_username', PARAM_TEXT);
        $mform->setDefault('map_username', 'RUT Persona');
    }

    protected function add_field_map_course()
    {
        $mform = $this->_form;
        $mform->addElement('text', 'map_course', 'Columna Curso');
        $mform->setType('map_course', PARAM_TEXT);
        $mform->setDefault('map_course', 'Producto (curso)');
    }

    protected function add_field_map_grade()
    {
        $mform = $this->_form;
        $mform->addElement('text', 'map_grade', 'Columna Calificación');
        $mform->setType('map_grade', PARAM_TEXT);
        $mform->setDefault('map_grade', 'Nota Evaluacion (1-7)');
    }

    protected function add_field_map_startdate()
    {
        $mform = $this->_form;
        $mform->addElement('text', 'map_startdate', 'Columna Fecha Inicio');
        $mform->setType('map_startdate', PARAM_TEXT);
        $mform->setDefault('map_startdate', 'Fecha Inscripción Portal Mutual');
    }

    protected function add_field_map_enddate()
    {
        $mform = $this->_form;
        $mform->addElement('text', 'map_enddate', 'Columna Fecha Fin');
        $mform->setType('map_enddate', PARAM_TEXT);
        $mform->setDefault('map_enddate', 'Fecha Termino Actividad');
    }


    protected function add_field_status()
    {
        $mform = $this->_form;
        $choices = persistent::get_status_for_manual_upload();
        $mform->addElement('select', 'status', 'Estado inicial', $choices, persistent::STATUS_PREVIEW);
        $mform->setType('status', PARAM_INT);
    }

    /**
     * Override get_data to pack mapping fields into settings json
     */
    public function get_data()
    {
        $data = parent::get_data();
        if ($data) {
            $settings = [
                'map_username' => $data->map_username,
                'map_course' => $data->map_course,
                'map_grade' => $data->map_grade,
                'map_startdate' => $data->map_startdate,
                'map_enddate' => $data->map_enddate,
            ];
            $data->settings = json_encode($settings);
            // Ensure courseid is stored as null if not present (element removed)
            if (!isset($data->courseid)) {
                $data->courseid = null;
            }
        }
        return $data;
    }

    // Override to populate form from settings json
    public function set_data($data, $clean = true)
    {
        if (!empty($data->settings)) {
            $settings = json_decode($data->settings, true);
            if (is_array($settings)) {
                foreach ($settings as $key => $value) {
                    $data->$key = $value;
                }
            }
        }
        return parent::set_data($data, $clean);
    }
}
