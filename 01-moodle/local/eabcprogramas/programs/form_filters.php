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
class form_filters extends moodleform
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

        $mform->addElement('text', 'codigo', get_string('codigootorgado', 'local_eabcprogramas'));
        $mform->setType('codigo', PARAM_NOTAGS);
        $mform->addHelpButton('codigo', 'codigootorgado', 'local_eabcprogramas');

        if (is_siteadmin() || has_capability('local/eabcprogramas:holding', context_system::instance())) {
            $mform->addElement('text', 'trabajador', get_string('trabajador', 'local_eabcprogramas'));
            $mform->setType('trabajador', PARAM_NOTAGS);
            $mform->addHelpButton('trabajador', 'trabajador', 'local_eabcprogramas');

            $mform->addElement('text', 'ruttrabajador', get_string('ruttrabajador', 'local_eabcprogramas'));
            $mform->setType('ruttrabajador', PARAM_NOTAGS);
            $mform->addHelpButton('ruttrabajador', 'ruttrabajador', 'local_eabcprogramas');
        }

        $mform->addElement('text', 'empresa', get_string('empresa', 'local_eabcprogramas'));
        $mform->setType('empresa', PARAM_NOTAGS);
        $mform->addHelpButton('empresa', 'empresa', 'local_eabcprogramas');

        $mform->addElement('text', 'rutempresa', get_string('rutempresa', 'local_eabcprogramas'));
        $mform->setType('rutempresa', PARAM_NOTAGS);
        $mform->addHelpButton('rutempresa', 'rutempresa', 'local_eabcprogramas');

        //from otorgamiento
        $mform->addElement('date_time_selector', 'startdate_otorgamiento', get_string('from_otorgamiento', 'local_eabcprogramas'), array('optional' => true));
        //$mform->setDefault('startdate_otorgamiento', time());
        $mform->addHelpButton('startdate_otorgamiento', 'from_otorgamiento', 'local_eabcprogramas');

        //to
        $mform->addElement('date_time_selector', 'todate_otorgamiento', get_string('to', 'local_eabcprogramas'), array('optional' => true));

        //from endfirstcourse
        $mform->addElement('date_time_selector', 'startdate_endfirstcourse', get_string('from_endfirstcourse', 'local_eabcprogramas'), array('optional' => true));
        $mform->addHelpButton('startdate_endfirstcourse', 'from_endfirstcourse', 'local_eabcprogramas');

        //to
        $mform->addElement('date_time_selector', 'todate_endfirstcourse', get_string('to', 'local_eabcprogramas'), array('optional' => true));

        //from vencimiento
        $mform->addElement('date_time_selector', 'startdate_vencimiento', get_string('from_vencimiento', 'local_eabcprogramas'), array('optional' => true));
        $mform->addHelpButton('startdate_vencimiento', 'from_vencimiento', 'local_eabcprogramas');

        //to
        $mform->addElement('date_time_selector', 'todate_vencimiento', get_string('to', 'local_eabcprogramas'), array('optional' => true));

        $radioarray = array();
        $radioarray[] = &$mform->createElement('radio', 'status', '', get_string('no'), 0, []);
        $radioarray[] = &$mform->createElement('radio', 'status', '', get_string('yes'), 1, []);
        $radioarray[] = &$mform->createElement('checkbox', 'solouno', '', 'Habilitar');
        $mform->addGroup($radioarray, 'radioar', get_string('vigente', 'local_eabcprogramas'), ' ', false);
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
