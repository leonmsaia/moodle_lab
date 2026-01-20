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
use tool_eabcetlbridge\persistents\batch_files;
use tool_eabcetlbridge\tasks\adhoc\migrate_data_batch;

/**
 * Orchestrates the execution of all enabled migration strategies.
 *
 * @package   tool_eabcetlbridge
 * @category  tasks
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migration_orchestrator_task extends scheduled_task {

    /** @var int The batch size for processing records. */
    const BATCH_SIZE = 500;

    /**
     * Get a descriptive name for this task.
     * @return string
     */
    public function get_name() {
        return get_string('migrationorchestratortask', 'tool_eabcetlbridge');
    }

    /**
     * Execute the task.
     */
    public function execute() {

        // Get the list of enabled migration configurations.
        $files = batch_files::get_pending_files_for_queue();

        if (empty($files)) {
            // No hay migraciones activas.
            mtrace('No hay migraciones activas');
            return;
        }

        foreach ($files as $file) {
            mtrace("Procesando archivo {$file->get('id')}");

            $task = migrate_data_batch::instance($file->get('id'));
            $id = \core\task\manager::queue_adhoc_task($task);

            // Mark the file as sent to the queue.
            $file->set('status', $file::STATUS_SENTTOQUEUE);
            $file->set('logid', $id);
            $file->save();

            mtrace("Archivo {$file->get('id')} enviado a la cola");
        }
    }

}
