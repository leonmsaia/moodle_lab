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
 * utils class
 *
 * @package    local_eabcprogramas
 * @copyright 2020 Eimar Urbina <eimar@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eabcprogramas\task;

use coding_exception;
use dml_exception;
use grade_grade;
use grade_item;
use local_eabcprogramas\persistent\programasusuarios;
use local_eabcprogramas\utils;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/querylib.php');

class inactivar_programa extends \core\task\scheduled_task
{

    /**
     * @return string
     * @throws coding_exception
     */
    public function get_name()
    {
        return get_string("inactivarprograma", "local_eabcprogramas");
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function execute()
    {
        global $DB;
        $last_execution = $this->get_last_run_time();

        $programs = $DB->get_records('local_eabcprogramas_usuarios', ['status' => utils::activo()]);
        foreach ($programs as $key => $recording) {
            if ($recording->fecha_vencimiento < time()) {
                $recording->status = utils::inactivo();
                $DB->update_record('local_eabcprogramas_usuarios', $recording);
            }
        }
    }
}
