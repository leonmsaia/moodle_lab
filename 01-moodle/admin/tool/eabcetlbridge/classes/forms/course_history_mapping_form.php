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
 * Map Course History Columns Form (Step 2)
 *
 * @package   tool_eabcetlbridge
 * @category  forms
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_history_mapping_form extends \moodleform
{

    public function definition()
    {
        $mform = $this->_form;

        $header = $this->_customdata['header'];
        $iid = $this->_customdata['iid'];

        $mform->addElement('header', 'general', 'Mapeo de Columnas');
        $mform->addElement('html', '<p>Seleccione las columnas del archivo CSV que corresponden a los datos requeridos.</p>');

        $options = array();
        if ($header) {
            foreach ($header as $i => $h) {
                // Use index as value to easily map later
                $options[$i] = s($h);
            }
        }

        // Define required fields for mapping
        $mapping_fields = [
            'map_username' => ['label' => 'Usuario (RUT)', 'default_regex' => '/rut|user|usuario/i'],
            'map_course' => ['label' => 'Curso (Nombre)', 'default_regex' => '/curso|course|producto/i'],
            'map_grade' => ['label' => 'Calificación', 'default_regex' => '/nota|calificaci|grade/i'],
            'map_startdate' => ['label' => 'Fecha Inicio', 'default_regex' => '/inicio|start|inscripci|enroll/i'],
            'map_enddate' => ['label' => 'Fecha Fin (Término)', 'default_regex' => '/fin|end|termino|completado/i'],
        ];

        foreach ($mapping_fields as $field_name => $info) {
            $mform->addElement('select', $field_name, $info['label'], $options);

            // Try to guess default
            foreach ($options as $idx => $label) {
                if (preg_match($info['default_regex'], $label)) {
                    $mform->setDefault($field_name, $idx);
                    break;
                }
            }
        }

        $mform->addElement('checkbox', 'override_grade', 'Opciones de Calificación', 'Actualizar calificación solo si es mayor (Override)');
        $mform->setDefault('override_grade', 1);

        $mform->addElement('hidden', 'iid', $iid);
        $mform->setType('iid', PARAM_INT);

        $this->add_action_buttons(false, 'Procesar Importación');
    }
}
