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

namespace tool_eabcetlbridge\strategies;

use Exception;
use tool_eabcetlbridge\persistents\planners\user_grade_migration\{users_by_course, courses};
use tool_eabcetlbridge\tasks\adhoc\populate_planner_batch;
use tool_eabcetlbridge\tasks\adhoc\get_external_grades_and_create_data_batch;

/**
 * Concrete strategy for migrating Moodle Grades data.
 *
 * @package   tool_eabcetlbridge
 * @category  strategies
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auto_by_users_in_course_grades_strategy extends grades_strategy {

    /**
     * {@inheritdoc}
     */
    public static function get_name() {
        return 'Migración Automática de Calificaciones (username, course_shortname, finalgrade)';
    }

    /**
     * {@inheritdoc}
     */
    public function process() {

        if (!$this->config) {
            mtrace("[auto_by_users_in_course_grades_strategy] La configuración no existe");
            return;
        }

        if ($this->config->get('isenabled') != $this->config::STATUS_ENABLED) {
            mtrace("[auto_by_users_in_course_grades_strategy] La configuración no esta activa");
            return;
        }

        if ($this->config->get('isautomatic') != $this->config::STATUS_ENABLED) {
            mtrace("[auto_by_users_in_course_grades_strategy] La configuración no es automática");
            return;
        }

        // Check new records.
        $count = users_by_course::count_new_records();
        if ($count > 0) {
            mtrace("[users_by_course_planner] Hay {$count} registros de users_by_course para insertar");
            $task = populate_planner_batch::instance(
                users_by_course::ACTION_INSERT, users_by_course::class, $this->config->get('id')
            );
            \core\task\manager::queue_adhoc_task($task);
        } else {
            mtrace("[users_by_course_planner] No hay registros de users_by_course para insertar");
        }

        // Check course records.
        $count = courses::count_new_records();
        if ($count > 0) {
            mtrace("[populate_courses_planner] Hay {$count} registros de courses para insertar");
            $task = populate_planner_batch::instance(
                courses::ACTION_INSERT, courses::class, $this->config->get('id')
            );
            \core\task\manager::queue_adhoc_task($task);
        } else {
            mtrace("[populate_courses_planner] No hay registros de courses para insertar");
        }

        $courseplanners = courses::get_records_by_status(courses::STATUS_PENDING);
        if (empty($courseplanners)) {
            mtrace('[courses_planner] No hay cursos pendientes para solicitar calificaciones externas');
            return;
        }

        foreach ($courseplanners as $planner) {
            mtrace("[courses_planner] Procesando curso {$planner->get('courseid')} asociado al planificar {$planner->get('id')}");

            $task = get_external_grades_and_create_data_batch::instance(
                $planner->get('id'),
                courses::class
            );
            $id = \core\task\manager::queue_adhoc_task($task);

            // Mark the course as sent to the queue.
            if ($id) {
                $planner->set('status', $planner::STATUS_SENTTOQUEUE);
                $planner->save();
                mtrace("[courses_planner] Archivo {$planner->get('courseid')} asociado al " .
                       "planificar {$planner->get('id')} enviado a la cola");
            } else {
                mtrace("[courses_planner] No se pudo crear adhoc {$planner->get('courseid')} " .
                       "asociado al planificar {$planner->get('id')} a la cola");
            }

        }

    }

}
