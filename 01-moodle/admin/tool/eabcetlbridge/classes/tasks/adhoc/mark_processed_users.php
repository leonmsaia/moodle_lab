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

namespace tool_eabcetlbridge\tasks\adhoc;

use core\task\adhoc_task;
use tool_eabcetlbridge\persistents\planners\user_grade_migration\users_by_course;
use Exception;

/**
 * Mark processed users
 *
 * @package   tool_eabcetlbridge
 * @category  tasks
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mark_processed_users extends adhoc_task {

    /**
     * Get a descriptive name for this task.
     * @return string
     */
    public function get_name() {
        return get_string('mark_processed_users_task', 'tool_eabcetlbridge');
    }

    /**
     * Get an instance of the task with the given custom data.
     *
     * @param int $action The action to perform on the id map.
     * @param class-string<id_map>[] $class The class of id map to use.
     * @return self
     */
    public static function instance(
        int $batchfileid,
        int $courseid,
        array $processedusers = [],
    ): self {
        $task = new self();
        $task->set_custom_data((object) [
            'batchfileid' => $batchfileid,
            'courseid' => $courseid,
            'processedusers' => $processedusers
        ]);

        return $task;
    }

    /**
     * Execute the task.
     */
    public function execute() {

        $data = $this->get_custom_data();
        $batchfileid = $data->batchfileid ?? false;
        $courseid = $data->courseid ?? false;
        $processedusers = $data->processedusers ?? [];

        if (!$batchfileid || !$courseid || empty($processedusers)) {
            mtrace("No se han recibido los datos necesarios");
            return;
        }

        try {
            users_by_course::mark_processed_users($batchfileid, $courseid, $processedusers);
        } catch (Exception $ex) {
            mtrace("Error al marcar los usuarios como procesados: {$ex->getMessage()}");
        }

    }

}
