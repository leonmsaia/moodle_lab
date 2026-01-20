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

use core_text;
use csv_import_reader;
use tool_eabcetlbridge\persistents\planners\planner as persistent;

/**
 * Upload a CVS file with information.
 *
 * @package   tool_eabcetlbridge
 * @category  forms
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planner_form extends base_persistent_form {

    protected static $persistentclass = 'tool_eabcetlbridge\\persistents\\planners\\planner';

    protected static $foreignfields = ['file'];

    /**
     * Define fields elements.
     * @return array
     */
    protected $fields = array(
        'main_header',
        'user',
        'status',
    );

    /**
     * Status.
     * @return void
     */
    protected function add_field_status() {
        $mform = $this->_form;
        $choices = persistent::get_status_options();
        $mform->addElement(
            'select',
            'status',
            'Desea reiniciar el planificador?',
            $choices,
            persistent::STATUS_PREVIEW
        );
        $mform->setType('status', PARAM_TEXT);
    }

}
