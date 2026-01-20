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

use Exception;
use \core\task\adhoc_task;
use tool_eabcetlbridge\persistents\{batch_files, configs};
use tool_eabcetlbridge\strategies\base_strategy;;

/**
 * Migrates data from a batch file.
 *
 * @package   tool_eabcetlbridge
 * @category  tasks
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migrate_data_batch extends adhoc_task {

    /** @var batch_files */
    protected $batchfile = null;
    /** @var configs */
    protected $config = null;
    /** @var base_strategy */
    protected $strategy = null;

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
     * @param int $batchfileid The ID of the batch file to be processed.
     * @return self
     */
    public static function instance(
        int $batchfileid
    ): self {
        $task = new self();
        $task->set_custom_data((object) [
            'batchfileid' => $batchfileid
        ]);

        return $task;
    }

    /**
     * Initialize the task.
     *
     * @throws Exception If the batch file or its associated configuration does not exist.
     */
    public function init() {

        $data = $this->get_custom_data();
        $batchfileid = $data->batchfileid ?? false;
        if (!$batchfileid) {
            throw new Exception("No se han recibido los datos necesarios");
        }

        $this->batchfile = batch_files::get_record(['id' => $batchfileid]);
        if (!$this->batchfile) {
            throw new Exception("No existe el persistente: {$batchfileid}");
        }

        $this->config = configs::get_record(['id' => $this->batchfile->get('configid')]);
        if (!$this->config) {
            throw new Exception("No existe la configuración {$this->batchfile->get('configid')}");
        }

        // Get the strategy.
        $strategyclass = $this->config->get('strategyclass');
        if (!class_exists($strategyclass)) {
            throw new Exception("La estrategia {$strategyclass} no existe");
        }
        $this->strategy = new $strategyclass($this->config, $this->batchfile);

    }

    /**
     * Executes the migration strategy on the batch file.
     *
     * This method takes no parameters and returns no value. It simply
     * executes the migration strategy on the associated batch file.
     */
    public function execute() {

        try {
            self::init();
            $this->strategy->process_csv();
        } catch (Exception $e) {
            if ($this->batchfile) {
                mtrace("Error al procesar el archivo {$this->batchfile->get('id')}: {$e->getMessage()}");
                $this->batchfile->set('status', $this->batchfile::STATUS_FAILED);
                $this->batchfile->save();
            } else {
                mtrace("Error al procesar el archivo (Sin batchfile): {$e->getMessage()}");
            }
        }

    }

}
