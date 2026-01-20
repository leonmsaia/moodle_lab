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
 *
 * @package    local_eabcprogramas
 * @copyright  2020 e-abclearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_eabcprogramas\utils;

defined('MOODLE_INTERNAL') || die();

include_once("$CFG->libdir/formslib.php");
class diploma_form extends moodleform
{
    public function definition()
    {
        $action = optional_param('action', '', PARAM_TEXT);
        $id = optional_param('id', 0, PARAM_INT);
        global $USER, $DB;
        $mform = $this->_form;

        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_NOTAGS);

        if ($action == 'crear') {
            $anterior = $DB->get_record('local_diplomas', ['status' => 1]);
            if ($anterior) {
                echo '
                <div class="alert alert-info">
                    <strong>Alerta!</strong> Al ejecutar esta acción, el modelo anterior quedará inactivo.
                </div>';
            }
            $mform->addElement('header', 'diploma', get_string('diploma', 'local_eabcprogramas'));

            $mform->addElement(
                'filepicker',
                'img',
                get_string('img', 'local_eabcprogramas'),
                null,
                array('accepted_types' => array('.png', '.jpg', '.jpeg'))
            );
            $this->add_action_buttons(false, 'Aceptar');
        }
        if ($id > 0) {
            $mform->addElement('hidden', 'id', $id);
            $mform->setType('id', PARAM_RAW);
            if ($action == 'inactivar') {
                echo '
                <div class="alert alert-info">
                    <strong>Alerta!</strong> Inactivar modelo de diploma.
                </div>';

                $this->add_action_buttons(false, 'Inactivar');
            }
            if ($action == 'eliminar') {
                echo '
                <div class="alert alert-info">
                    <strong>Alerta!</strong> Eliminar modelo de diploma.
                </div>';

                $this->add_action_buttons(false, 'Eliminar');
            }
        }
    }

    /**
     * @param array $data
     * @param array $files
     * @return array
     * @throws dml_exception
     */
    function validation($data, $files)
    {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);
        return $errors;
    }
}
