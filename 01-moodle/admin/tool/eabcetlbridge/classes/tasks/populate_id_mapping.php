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

namespace tool_eabcetlbridge\tasks;

use core\task\scheduled_task;
use tool_eabcetlbridge\persistents\{user_map, course_map};
use tool_eabcetlbridge\tasks\adhoc\populate_id_mapping_batch as batch;

/**
 * Populate id mapping
 *
 * @package   tool_eabcetlbridge
 * @category  tasks
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class populate_id_mapping extends scheduled_task {

    /**
     * Get a descriptive name for this task.
     * @return string
     */
    public function get_name() {
        return get_string('populate_id_mapping_task', 'tool_eabcetlbridge');
    }

    /**
     * Execute the task.
     */
    public function execute() {

        $count = user_map::count_new_records();
        if ($count > 0) {
            mtrace("Hay {$count} registros de eabcetlbridge_id_map para insertar");
            $task = new batch();
            $task->set_custom_data([
                'action' => user_map::ACTION_INSERT,
                'class' => user_map::class
            ]);
            \core\task\manager::queue_adhoc_task($task);
        } else {
            mtrace("No hay registros de eabcetlbridge_id_map de usuarios para insertar");
        }

        $count = user_map::count_deleted_records();
        if ($count > 0) {
            mtrace("Hay {$count} registros de eabcetlbridge_id_map de usuarios para borrar");

            $task = new batch();
            $task->set_custom_data([
                'action' => user_map::ACTION_DELETE,
                'class' => user_map::class
            ]);
            \core\task\manager::queue_adhoc_task($task);

        } else {
            mtrace("No hay registros de eabcetlbridge_id_map de usuarios para borrar");
        }

        $count = course_map::count_new_records();
        if ($count > 0) {
            mtrace("Hay {$count} registros de eabcetlbridge_id_map para insertar");
            $task = new batch();
            $task->set_custom_data([
                'action' => course_map::ACTION_INSERT,
                'class' => course_map::class
            ]);
            \core\task\manager::queue_adhoc_task($task);
        } else {
            mtrace("No hay registros de eabcetlbridge_id_map de cursos para insertar");
        }

        $count = course_map::count_deleted_records();
        if ($count > 0) {
            mtrace("Hay {$count} registros de eabcetlbridge_id_map de cursos para borrar");
            $task = new batch();
            $task->set_custom_data([
                'action' => course_map::ACTION_DELETE,
                'class' => course_map::class
            ]);
            \core\task\manager::queue_adhoc_task($task);
        } else {
            mtrace("No hay registros de eabcetlbridge_id_map de cursos para borrar");
        }

    }

}
