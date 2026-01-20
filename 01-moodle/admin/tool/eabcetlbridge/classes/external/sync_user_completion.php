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

namespace tool_eabcetlbridge\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/externallib.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_description;
use core\context\system as context_system;
use core\exception\moodle_exception;

use tool_eabcetlbridge\output_handlers\ws_output_handler;
use tool_eabcetlbridge\completion\completion_migrator;

class sync_user_completion extends external_api {

    /**
     * Definición de parámetros de entrada
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'username' => new external_value(PARAM_RAW, 'Username to migrate'),
            'dry_run'  => new external_value(PARAM_BOOL, 'Simulate without writing', VALUE_DEFAULT, false),
            'force'    => new external_value(PARAM_BOOL, 'Overwrite existing completions', VALUE_DEFAULT, false),
            'verbose'  => new external_value(PARAM_BOOL, 'Verbose output', VALUE_DEFAULT, false),
            'migrate_course_date' => new external_value(PARAM_BOOL, 'Migrate course date', VALUE_DEFAULT, false),
            'force_course_date' => new external_value(PARAM_BOOL, 'Force course date', VALUE_DEFAULT, false),
            'clear_cache' => new external_value(PARAM_BOOL, 'Clear cache after migration', VALUE_DEFAULT, true)
        ]);
    }

    /**
     * Lógica de ejecución
     */
    public static function execute(
            $username,
            $dryrun = false,
            $force = false,
            $verbose = false,
            $migrate_course_date = false,
            $force_course_date = false,
            $clear_cache = true
            ) {
        global $CFG;

        // 1. Validación de parámetros
        $params = self::validate_parameters(self::execute_parameters(), [
            'username' => $username,
            'dry_run'  => $dryrun,
            'force'    => $force,
            'verbose'  => $verbose,
            'migrate_course_date' => $migrate_course_date,
            'force_course_date' => $force_course_date,
            'clear_cache' => $clear_cache
        ]);

        // 2. Validación de contexto (Admin solamente por seguridad)
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        // 3. Preparar el Output Handler para capturar logs
        $wshandler = new ws_output_handler();

        // 4. Configurar opciones
        $migratoroptions = [
            'dry_run'        => $params['dry_run'],
            'force'          => $params['force'],
            'verbose'        => $params['verbose'],
            'migrate_course_date' => $params['migrate_course_date'],
            'force_course_date' => $params['force_course_date'],
            'clear_cache'    => $params['clear_cache'],
            'output_handler' => $wshandler
        ];

        $stats = [];
        $status = 'success';
        $errormsg = '';

        try {
            // 5. Ejecutar la migración.
            $migrator = new completion_migrator($migratoroptions);
            $stats = $migrator->migrate_user_completions($params['username']);

        } catch (\Exception $e) {
            $status = 'error';
            $errormsg = $e->getMessage();
            // Capturamos el error en los logs también.
            $wshandler->output("CRITICAL ERROR: " . $e->getMessage());
        }

        // 6. Retornar estructura.
        return [
            'status' => $status,
            'message' => $errormsg,
            'logs' => $wshandler->get_logs(),
            'stats' => [
                'courses_processed'    => $stats['courses_processed'] ?? 0,
                'activities_migrated'  => $stats['activities_migrated'] ?? 0,
                'activities_skipped'   => $stats['activities_skipped'] ?? 0,
                'activities_not_found' => $stats['activities_not_found'] ?? 0,
                'activities_protected' => $stats['activities_protected'] ?? 0,
                'errors'               => $stats['errors'] ?? 0,
                'courses_migrated'     => $stats['courses_migrated'] ?? 0,
                'courses_skipped'      => $stats['courses_skipped'] ?? 0,
                'courses_notvalid'     => $stats['courses_notvalid'] ?? 0
            ]
        ];
    }

    /**
     * Definición de estructura de retorno
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status'  => new external_value(PARAM_ALPHA, 'Status: success or error'),
            'message' => new external_value(PARAM_RAW, 'Error message if any'),
            'logs'    => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Log entry')
            ),
            'stats'   => new external_single_structure([
                'courses_processed'    => new external_value(PARAM_INT, 'Count of courses'),
                'activities_migrated'  => new external_value(PARAM_INT, 'Count of migrated activities'),
                'activities_skipped'   => new external_value(PARAM_INT, 'Count of skipped activities'),
                'activities_not_found' => new external_value(PARAM_INT, 'Count of missing activities'),
                'errors'               => new external_value(PARAM_INT, 'Count of errors'),
                'courses_migrated'     => new external_value(PARAM_INT, 'Count of migrated courses'),
                'courses_skipped'      => new external_value(PARAM_INT, 'Count of skipped courses'),
                'courses_notvalid'     => new external_value(PARAM_INT, 'Count of not valid courses')
            ])
        ]);
    }
}
