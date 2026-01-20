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

use tool_eabcetlbridge\persistents\configs as persistent;

/**
 * Upload a CVS file with information.
 *
 * @package   tool_eabcetlbridge
 * @category  forms
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class configs_form extends base_persistent_form {

    /** @var class-string<persistent> */
    protected static $persistentclass = 'tool_eabcetlbridge\\persistents\\configs';

    /**
     * Define fields elements.
     * @return array
     */
    protected $fields = array(
        'main_header',
        'user',
        'name',
        'shortname',
        'strategyclass',
        //'sourcequery',
        'mapping',
        'isenabled',
        'isautomatic',
    );

    /**
     * Strategy class.
     * @return void
     */
    protected function add_field_strategyclass() {
        $mform = $this->_form;
        $choices = \tool_eabcetlbridge\strategies\base_strategy::get_strategies();
        $mform->addElement('select', 'strategyclass', get_string('form_strategy', 'tool_eabcetlbridge'), $choices);
        $mform->setType('strategyclass', PARAM_TEXT);
    }

    /**
     * Source query.
     * @return void
     */
    protected function add_field_sourcequery() {
        $mform = $this->_form;
        $mform->addElement('textarea', 'sourcequery', 'Consulta SQL', 'rows="5" cols="50"');
        $mform->setType('sourcequery', PARAM_RAW);
    }

    /**
     * Mapping.
     * @return void
     */
    protected function add_field_mapping() {
        $mform = $this->_form;
        $mform->addElement('textarea', 'mapping', 'Datos de mapeo', 'rows="5" cols="50"');
        $mform->setType('mapping', PARAM_RAW);
    }

    /**
     * Status.
     * Every Program or Route is created in the Under Construction state and can then go to the next state.
     * @return void
     */
    protected function add_field_isenabled() {
        $mform = $this->_form;
        $choices = static::$persistentclass::get_isenabled_options();
        $mform->addElement('select', 'isenabled', '¿Está activo?', $choices);
        $mform->setType('isenabled', PARAM_INT);
    }

    /**
     * Add a select element to the form to select if the config is automatic or not.
     * The select element will contain two options: Activado and Desactivado.
     * @return void
     */
    protected function add_field_isautomatic() {
        $mform = $this->_form;
        $choices = static::$persistentclass::get_isautomatic_options();
        $mform->addElement('select', 'isautomatic', '¿Es automático?', $choices);
        $mform->setType('isautomatic', PARAM_INT);
    }

}
