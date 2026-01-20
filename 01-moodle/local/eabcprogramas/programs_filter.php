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
class programs_filter extends moodleform
{
    public function definition()
    {
        global $USER, $DB;
        $mform = $this->_form;

        $mform->addElement('header', 'filtro', 'Filtrar');
        $mform->setExpanded('filtro', false);

        $mform->addElement('text', 'description', get_string('description', 'local_eabcprogramas'));
        $mform->setType('description', PARAM_NOTAGS);
        $mform->addHelpButton('description', 'description', 'local_eabcprogramas');

        $mform->addElement('text', 'codigo', get_string('codigo', 'local_eabcprogramas'));
        $mform->setType('codigo', PARAM_NOTAGS);
        $mform->addHelpButton('codigo', 'codigo', 'local_eabcprogramas');

        $mform->addElement('text', 'caducidad', get_string('meses', 'local_eabcprogramas'));
        $mform->setType('caducidad', PARAM_NOTAGS);
        $mform->addHelpButton('caducidad', 'caducidad', 'local_eabcprogramas');
        $mform->addRule('caducidad', get_string('numeric', 'local_eabcprogramas'), 'numeric');

        $mform->addElement('text', 'horas', get_string('horas', 'local_eabcprogramas'));
        $mform->setType('horas', PARAM_NOTAGS);
        $mform->addHelpButton('horas', 'horas', 'local_eabcprogramas');
        $mform->addRule('horas', get_string('numeric', 'local_eabcprogramas'), 'numeric');

        $mform->addElement('text', 'responsable', get_string('responsable', 'local_eabcprogramas'));
        $mform->setType('responsable', PARAM_NOTAGS);
        $mform->addHelpButton('responsable', 'responsable', 'local_eabcprogramas');

        //from lanzamiento
        $mform->addElement('date_time_selector', 'startdate_lanzamiento', get_string('from_lanzamiento', 'local_eabcprogramas'), array('optional' => true));
        //$mform->setDefault('startdate_lanzamiento', time());
        $mform->addHelpButton('startdate_lanzamiento', 'fromdate', 'local_eabcprogramas');

        //to
        $mform->addElement('date_time_selector', 'todate_lanzamiento', get_string('to', 'local_eabcprogramas'), array('optional' => true));

        //from vencimiento
        $mform->addElement('date_time_selector', 'startdate_vencimiento', get_string('from_vencimiento', 'local_eabcprogramas'), array('optional' => true));
        $mform->addHelpButton('startdate_vencimiento', 'from_vencimiento', 'local_eabcprogramas');

        //to
        $mform->addElement('date_time_selector', 'todate_vencimiento', get_string('to', 'local_eabcprogramas'), array('optional' => true));

        $radioarray = array();
        $radioarray[] = &$mform->createElement('radio', 'status', '', 'Activo', 1, []);
        $radioarray[] = &$mform->createElement('radio', 'status', '', 'Inactivo', 0, []);
        $radioarray[] = &$mform->createElement('radio', 'status', '', 'En Preparación', 2, []);
        $radioarray[] = &$mform->createElement('checkbox', 'solouno', '', 'Habilitar');
        $mform->addGroup($radioarray, 'radioar', 'Estado', ' ', false);
        $mform->disabledIf('radioar', 'solouno');

        $mform->addHelpButton('radioar', 'status', 'local_eabcprogramas');

        $this->add_action_buttons(false, 'Buscar');
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
