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
 * Orchestrates the execution of all enabled migration strategies.
 *
 * @package   tool_eabcetlbridge
 * @category  tasks
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_eabcetlbridge\tasks;

use Exception;
use core\task\scheduled_task;
use tool_eabcetlbridge\persistents\configs;
use tool_eabcetlbridge\tasks\adhoc\migrate_automatic_start;

/**
 * Orchestrates the execution of all enabled migration strategies.
 */
class migration_automatic_start_queue extends scheduled_task {

    /**
     * Get a descriptive name for this task.
     * @return string
     */
    public function get_name() {
        return 'e-ABC Inicio de Encolamiento de Registros para Estrategias de Migración Automáticas';
    }

    /**
     * Execute the task.
     */
    public function execute() {

        // Get the list of enabled migration configurations.
        $configs = configs::get_automatic_configs();

        if (empty($configs)) {
            // No hay migraciones activas.
            mtrace('No hay migraciones automáticas activas');
            return;
        }

        foreach ($configs as $config) {
            mtrace("Procesando configuración {$config->get('id')}");

            // Get the strategy.
            $strategyclass = $config->get('strategyclass');
            if (!class_exists($strategyclass)) {
                throw new Exception("La estrategia {$strategyclass} no existe");
            }

            try {
                $strategy = new $strategyclass($config);
                $strategy->process();
            } catch (Exception $e) {
                mtrace("Error al procesar configuración automática: {$e->getMessage()}");
            }

        }
    }

}
