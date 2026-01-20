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
use core\context\system as context_system;
use tool_eabcetlbridge\completion\completion_migrator;
use tool_eabcetlbridge\grades\export_grades_manager;
use tool_eabcetlbridge\output_handlers\ws_output_handler;
use tool_eabcetlbridge\strategies\user_grades_strategy;

/**
 * Unified web service to sync different types of user data.
 *
 * @package   tool_eabcetlbridge
 * @category  external
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_user_data extends external_api {

    /**
     * Define parameters for the execute method.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'username' => new external_value(PARAM_RAW, 'The username to migrate.'),
            'migrations' => new external_multiple_structure(
                new external_value(
                    PARAM_ALPHA,
                    'Migration type (e.g., "completions", "grades"). If empty, all migrations are run.',
                    VALUE_OPTIONAL
                )
            ),
            'options' => new external_single_structure([
                'completions' => new external_single_structure([
                    'dry_run' => new external_value(PARAM_BOOL, 'Simulate completion migration', VALUE_DEFAULT, false),
                    'force' => new external_value(PARAM_BOOL, 'Overwrite existing completions', VALUE_DEFAULT, false),
                    'verbose' => new external_value(PARAM_BOOL, 'Verbose output for completions', VALUE_DEFAULT, false),
                    'migrate_course_date' => new external_value(PARAM_BOOL, 'Migrate course completion date', VALUE_DEFAULT, false),
                    'force_course_date' => new external_value(PARAM_BOOL, 'Force course completion date', VALUE_DEFAULT, false),
                    'clear_cache' => new external_value(PARAM_BOOL, 'Clear cache after completion migration', VALUE_DEFAULT, true),
                ], 'Options for completion migration', VALUE_OPTIONAL),
                'grades' => new external_single_structure([
                    'courseshortname' => new external_value(PARAM_RAW, 'Filter grades by course shortname', VALUE_DEFAULT, null),
                    'forceregrade' => new external_value(PARAM_BOOL, 'Force regrade for all items', VALUE_DEFAULT, false),
                    'fullregrade' => new external_value(PARAM_BOOL, 'Force full regrade', VALUE_DEFAULT, false),
                    'forceoverride' => new external_value(PARAM_BOOL, 'Force override on final grade', VALUE_DEFAULT, false)
                ], 'Options for grades migration', VALUE_OPTIONAL)
            ], 'Specific options for each migration type', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Execute user data migrations.
     *
     * @param string $username
     * @param array $migrations
     * @param array|null $options
     * @return array
     */
    public static function execute($username, $migrations = array(), $options = array()) {
        $params = self::validate_parameters(self::execute_parameters(), [
            'username' => $username,
            'migrations' => $migrations,
            'options' => $options
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $availablemigrations = ['grades', 'completions'];
        $migrationstorun = $params['migrations'];

        // If no migrations are specified, run all available migrations.
        if (empty($migrationstorun)) {
            $migrationstorun = $availablemigrations;
        }

        $results = [];

        foreach ($migrationstorun as $migrationtype) {
            try {
                switch ($migrationtype) {
                    case 'grades':
                        $opts = $params['options']['grades'] ?? [];
                        $results['grades'] = self::run_grades_migration(
                            $params['username'],
                            $opts
                        );
                        $results['grades']['type'] = 'grades';
                        break;
                    case 'completions':
                        $opts = $params['options']['completions'] ?? [];
                        $results['completions'] = self::run_completion_migration(
                            $params['username'],
                            $opts
                        );
                        $results['completions']['type'] = 'completions';
                        break;
                    default:
                        $results[$migrationtype] = [
                            'status' => 'error',
                            'message' => 'Unknown migration type: ' . $migrationtype
                        ];
                }
            } catch (\Exception $e) {
                $results[$migrationtype] = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return ['results' => $results];
    }

    /**
     * Runs the completion migration for a user.
     *
     * @param string $username
     * @param array $options
     * @return array
     */
    private static function run_completion_migration($username, $options) {
        $wshandler = new ws_output_handler();
        $migratoroptions = [
            'dry_run' => $options['dry_run'] ?? false,
            'force' => $options['force'] ?? false,
            'verbose' => $options['verbose'] ?? true,
            'migrate_course_date' => $options['migrate_course_date'] ?? true,
            'force_course_date' => $options['force_course_date'] ?? false,
            'clear_cache' => $options['clear_cache'] ?? true,
            'output_handler' => $wshandler
        ];

        $migrator = new completion_migrator($migratoroptions);
        $stats = $migrator->migrate_user_completions($username);

        return [
            'status' => 'success',
            'stats' => $stats,
            'logs' => $wshandler->get_logs()
        ];
    }

    /**
     * Runs the grade migration for a user.
     *
     * @param string $username
     * @param array $options
     * @return array
     */
    private static function run_grades_migration($username, $options) {
        $gradesmanager = new export_grades_manager($username, $options['courseshortname'] ?? null);
        $gradedata = $gradesmanager->get_grades();

        if (empty($gradedata) || empty($gradedata['courses'])) {
            return ['status' => 'success', 'message' => 'No grades found for user.', 'stats' => []];
        }

        $strategy = new user_grades_strategy();
        $strategy->set_grades_data($gradedata);
        $result = $strategy->process_grades_data(
            $options['forceregrade'] ?? false,
            $options['fullregrade'] ?? false,
            $options['forceoverride'] ?? false
        );

        return [
            'status' => 'success',
            'stats' => $result
        ];
    }

    /**
     * Define the return structure for the web service.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'results' => new external_multiple_structure(
                new external_single_structure([
                    'type'   => new external_value(PARAM_ALPHA, 'Type of migration (grades, completions)', VALUE_OPTIONAL),
                    'status'  => new external_value(PARAM_ALPHA, 'Status of the migration'),
                    'message' => new external_value(PARAM_RAW, 'Error message if any', VALUE_OPTIONAL),
                    'stats'   => new external_single_structure([

                        // Completions fields.
                        'courses_processed'    => new external_value(PARAM_INT, 'Count of courses processed', VALUE_OPTIONAL),
                        'activities_migrated'  => new external_value(PARAM_INT, 'Count of migrated activities', VALUE_OPTIONAL),
                        'activities_skipped'   => new external_value(PARAM_INT, 'Count of skipped activities', VALUE_OPTIONAL),
                        'activities_not_found' => new external_value(PARAM_INT, 'Count of missing activities', VALUE_OPTIONAL),
                        'activities_protected' => new external_value(PARAM_INT, 'Count of protected activities', VALUE_OPTIONAL),
                        'errors'               => new external_value(PARAM_INT, 'Count of errors', VALUE_OPTIONAL),
                        'courses_migrated'     => new external_value(PARAM_INT, 'Count of migrated courses', VALUE_OPTIONAL),
                        'courses_skipped'      => new external_value(PARAM_INT, 'Count of skipped courses', VALUE_OPTIONAL),
                        'courses_notvalid'     => new external_value(PARAM_INT, 'Count of not valid courses', VALUE_OPTIONAL),

                        // Grades fields.
                        'grades_updated'       => new external_value(PARAM_INT, 'Total number of grade items updated.', VALUE_OPTIONAL),
                        'warnings'             => new external_multiple_structure(
                            new external_single_structure([
                                'warningcode' => new external_value(PARAM_ALPHANUMEXT, 'A code for the warning.'),
                                'message'     => new external_value(PARAM_TEXT, 'The warning message.')
                            ]),
                            'List of warnings during processing.',
                            VALUE_OPTIONAL
                        )
                    ], 'Statistics object combining fields from both strategies', VALUE_OPTIONAL),

                    'logs' => new external_multiple_structure(new external_value(PARAM_RAW, 'Log entry'), 'Logs', VALUE_OPTIONAL)
                ]),
                'Results for each migration type'
            )
        ]);
    }


}
