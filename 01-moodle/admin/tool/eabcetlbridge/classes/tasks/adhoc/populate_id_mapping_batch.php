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

use \core\task\adhoc_task;
use tool_eabcetlbridge\persistents\mappers\id_map;
use Exception;

/**
 * Populate id mapping
 *
 * @package   tool_eabcetlbridge
 * @category  tasks
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class populate_id_mapping_batch extends adhoc_task {

    /**
     * Get a descriptive name for this task.
     * @return string
     */
    public function get_name() {
        return get_string('populate_id_mapping_batch_task', 'tool_eabcetlbridge');
    }

    /**
     * Get an instance of the task with the given custom data.
     *
     * @param int $action The action to perform on the id map.
     * @param class-string<id_map>[] $class The class of id map to use.
     * @return self
     */
    public static function instance(
        int $action,
        string $class,
    ): self {
        $task = new self();
        $task->set_custom_data((object) [
            'action' => $action,
            'class' => $class,
        ]);

        return $task;
    }

    /**
     * Execute the task.
     */
    public function execute() {

        $data = $this->get_custom_data();
        /** @var int $action The action to perform */
        $action = $data->action ?? false;
        /** @var class-string<id_map> $class The class of id map */
        $class = $data->class ?? false;

        if (!$action || !$class) {
            mtrace("No se han recibido los datos necesarios");
            return;
        }

        if (class_exists($class)) {
            switch ($action) {
                case id_map::ACTION_INSERT:
                    mtrace("Insertando registros de {$class}");
                    $class::populate_new_records();
                    break;
                case id_map::ACTION_DELETE:
                    mtrace("Borrando registros de {$class}");
                    $class::delete_deleted_records();
                    break;
            }
        } else {
            throw new Exception("La clase {$class} no existe");
        }

    }

}
