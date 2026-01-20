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

namespace local_pubsub\task;

use local_pubsub\back\inscripcion_elearning;
use local_pubsub\back\inscripcion_elearning_batch;

class finalizar_pendientes_elearning extends \core\task\scheduled_task
{
    /** @var int $days Quantity of days to process */
    public $days;

    /** @var int $last_execute Timestamp of last execution of the task */
    public $last_execute;

    /** @var int $batchsize Quantity of records to process in a batch */
    public $batchsize = 500;

    /** @var string|null $datestart Optional start date filter */
    public $datestart = null;

    /** @var string|null $dateend Optional end date filter */
    public $dateend = null;

    /** @var bool $usebatch Whether to use batch processing */
    public $usebatch = false;

    /**
     * @var stdClass Almacenamiento en caché de la configuración del plugin.
     */
    public $config = null;

    /**
     * Constructor for the finalizar_pendientes_elearning task.
     *
     * This method
     *  - retrieves the quantity of days to process.
     *  - sets the last execution timestamp of the task.
     */
    public function __construct()
    {
        global $DB;

        $config = get_config('local_pubsub');
        $this->config = $config;

        // Get the days to process.
        $days = (int) $config->days;
        if (empty($days)) {
            $days = 30;
        }
        $this->days = $days;

        // Get the batch size.
        $batchsize = (int) $config->batchsize;
        if (empty($batchsize)) {
            $batchsize = 500;
        }
        $this->batchsize = $batchsize;

        // Get and validate date filters.
        $datestart = trim($config->datestart);
        if (!empty($datestart) && $this->validate_date($datestart)) {
            $this->datestart = $datestart;
        } else if (!empty($datestart)) {
            mtrace("ADVERTENCIA: La fecha de inicio '{$datestart}' no tiene un formato YYYY-MM-DD válido y será ignorada.");
        }

        $dateend = trim($config->dateend);
        if (!empty($dateend) && $this->validate_date($dateend)) {
            $this->dateend = $dateend;
        } else if (!empty($dateend)) {
            mtrace("ADVERTENCIA: La fecha de fin '{$dateend}' no tiene un formato YYYY-MM-DD válido y será ignorada.");
        }

        $usebatch = $config->use_batch_processing_for_elearning;
        if (empty($usebatch)) {
            $usebatch = false;
        }
        $this->usebatch = $usebatch;

        // Get the last execution timestamp of the task.
        $lastruntime = $DB->get_record('task_scheduled', array(
                'classname' => '\local_pubsub\task\finalizar_pendientes_elearning'
            ),
            'lastruntime'
        );
        $this->last_execute = (!empty($lastruntime)) ? $lastruntime->lastruntime : 0;
    }

    public function get_name()
    {
        return get_string('task_end_training_pendientes', 'local_pubsub');
    }

    public function execute()
    {
        /* 
         *  Se llama funcion para los "******* PENDIENTES *************"
         *  Participantes que tienen timecompleted en NULL en la tabla course_completions
         */
        $human_execution_date = userdate($this->last_execute);
        mtrace('', "\n\n");
        mtrace("La ultima ejecución fue: $this->last_execute ($human_execution_date)");

        if ($this->usebatch) {
            mtrace("Usando la implementación de procesamiento por lotes (inscripcion_elearning_batch).");
            mtrace(" - Cantidad de dias a procesar: $this->days");
            mtrace(" - Tamaño del lote: $this->batchsize");
            if ($this->datestart) {
                mtrace(" - Filtro de fecha de inicio: $this->datestart");
            }
            if ($this->dateend) {
                mtrace(" - Filtro de fecha de fin: $this->dateend");
            }
            $processingmode = !empty($this->config->processing_mode) ? $this->config->processing_mode : inscripcion_elearning_batch::PROCESSING_MODE_SYNC;
            mtrace(" - Modo de procesamiento: $processingmode");
            if ($processingmode === inscripcion_elearning_batch::PROCESSING_MODE_PARALLEL ||
                $processingmode === inscripcion_elearning_batch::PROCESSING_MODE_WEBHOOK
            ) {
                $parallelbatchsize = !empty($this->config->parallel_batch_size) ? $this->config->parallel_batch_size : 20;
                mtrace(" - Tamaño de lote para API: $parallelbatchsize");
            }
            mtrace('', "\n\n");
            $manager = new inscripcion_elearning_batch($this->config);
            $processedcount = $manager->finalizar_elearning_batch(
                $this->last_execute,
                $this->days,
                'pendientes',
                $this->batchsize,
                $this->datestart,
                $this->dateend
            );
        } else {
            mtrace("Usando la implementación original (inscripcion_elearning).");
            mtrace(" - Cantidad de dias a procesar: $this->days");
            mtrace('', "\n\n");
            // The original function does not return a count, so we can't assign it.
            inscripcion_elearning::finalizar_elearning(
                $this->last_execute,
                $this->days,
                'pendientes'
            );
            $processedcount = 'N/A (implementación original)';
        }

        mtrace("-------------------------------------------------");
        mtrace("Tarea finalizada. Total de registros procesados en esta ejecución: $processedcount");
        mtrace("-------------------------------------------------");
    }

    /**
     * Validates a date string to be in YYYY-MM-DD format.
     *
     * @param string $date The date string to validate.
     * @return bool True if the date is valid, false otherwise.
     */
    private function validate_date($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

}
