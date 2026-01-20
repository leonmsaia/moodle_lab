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

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . '/externallib.php');

use core_external\external_value;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core\context\system as context_system;
use core\exception\moodle_exception;
use tool_eabcetlbridge\grades\export_grades_manager;
use tool_eabcetlbridge\strategies\user_grades_strategy;

/**
 * Sync user grades from external Moodle DB.
 *
 * @package   tool_eabcetlbridge
 * @category  external
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_user_grades extends \core_external\external_api {

    /**
     * Returns parameters for the execute method.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'username' => new external_value(
                PARAM_RAW,
                'The username from the source Moodle instance.',
                VALUE_DEFAULT,
                null
            ),
            'courseshortname' => new external_value(
                PARAM_RAW,
                'The course shortname from the source Moodle instance.',
                VALUE_DEFAULT,
                null
            ),
            'groupname' => new external_value(
                PARAM_RAW,
                'The group name from the source Moodle instance.',
                VALUE_DEFAULT,
                null
            ),
            'source' => new external_value(
                PARAM_ALPHA,
                'The source of the users: "local" or "external".',
                VALUE_DEFAULT,
                'local'
            ),
            'forceregrade' => new external_value(
                PARAM_BOOL,
                'Force regrade for all items.',
                VALUE_DEFAULT,
                false
            ),
            'fullregrade' => new external_value(
                PARAM_BOOL,
                'Force regrade for all items.',
                VALUE_DEFAULT,
                false
            ),
            'forceoverride' => new external_value(
                PARAM_BOOL,
                'If true, sets the final grade as overridden.',
                VALUE_DEFAULT,
                false
            ),
        ]);
    }

    /**
     * Web service that fetches grades for a single user from an external DB and processes them.
     *
     * @param string|null $username The username from the source system.
     * @param string|null $courseshortname The course shortname.
     * @param string|null $groupname The group name.
     * @param string $source The source of the users ('local' or 'external').
     * @param bool $forceregrade Force regrade.
     * @param bool $fullregrade Full regrade.
     * @param bool $forceoverride Force override on final grade.
     * @return array A summary of the processing.
     */
    public static function execute(
            $username = null,
            $courseshortname = null,
            $groupname = null,
            $source = 'local',
            $forceregrade = false,
            $fullregrade = false,
            $forceoverride = false) {

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'username' => $username,
            'courseshortname' => $courseshortname,
            'groupname' => $groupname,
            'source' => $source,
            'forceregrade' => $forceregrade,
            'fullregrade' => $fullregrade,
            'forceoverride' => $forceoverride
        ]);

        // The service must be executed by a user with the right capabilities.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $usernames = [];
        $aggregatedresult = [
            'courses_processed' => 0,
            'grades_updated' => 0,
            'warnings' => []
        ];

        $coursetofilter = null;

        try {
            if (!empty($params['username'])) {
                $usernames[] = $params['username'];
                // If a course is also specified, we'll use it to filter grades for this single user.
                $coursetofilter = $params['courseshortname'];
            } else if (!empty($params['courseshortname'])) {
                $coursetofilter = $params['courseshortname'];
                if (!empty($params['groupname'])) {
                    if ($params['source'] === 'local') {
                        $usernames = export_grades_manager::get_local_users_by_group(
                            $params['courseshortname'],
                            $params['groupname']
                        );
                    } else {
                        // Get users from a specific group in a course (external).
                        $usernames = export_grades_manager::get_users_by_group(
                            $params['courseshortname'],
                            $params['groupname']
                        );
                    }
                } else {
                    if ($params['source'] === 'local') {
                        $usernames = export_grades_manager::get_local_users_by_course(
                            $params['courseshortname']
                        );
                    } else {
                        // Get all users from a course (external).
                        $usernames = export_grades_manager::get_users_by_course(
                            $params['courseshortname']
                        );
                    }
                }
            } else {
                throw new moodle_exception('missingparameter', 'webservice', '', 'username or coursename must be provided');
            }

            if (empty($usernames)) {
                $aggregatedresult['warnings'][] = [
                    'warningcode' => 'nousersfound',
                    'message' => 'No users found for the given criteria.'
                ];
            }

            foreach ($usernames as $user) {
                $username = is_object($user) ? $user->username : $user;
                try {
                    // 1. Fetch grades data from the external Moodle DB using the existing manager.
                    $gradesmanager = new export_grades_manager($username, $coursetofilter);
                    $gradedata = $gradesmanager->get_grades();

                    if (empty($gradedata) || empty($gradedata['courses'])) {
                        continue; // Skip user if no grades found.
                    }

                    // 2. Instantiate and use the strategy to process the data.
                    $strategy = new user_grades_strategy();
                    $strategy->set_grades_data($gradedata);
                    $result = $strategy->process_grades_data(
                        $params['forceregrade'],
                        $params['fullregrade'],
                        $params['forceoverride']
                    );

                    // Aggregate results.
                    $aggregatedresult['courses_processed'] += $result['courses_processed'];
                    $aggregatedresult['grades_updated'] += $result['grades_updated'];
                    $aggregatedresult['warnings'] = array_merge(
                        $aggregatedresult['warnings'],
                        $result['warnings']
                    );

                } catch (\Exception $e) {
                    $aggregatedresult['warnings'][] = [
                        'warningcode' => 'userprocessing_exception',
                        'message' => "Error processing user '{$username}': " . $e->getMessage()
                    ];
                }
            }

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'coursesprocessed' => 0,
                'gradesupdated' => 0,
                'warnings' => [[
                    'warningcode' => 'exception',
                    'message' => $e->getMessage()
                ]]
            ];
        }

        return [
            'status' => 'success',
            'coursesprocessed' => $aggregatedresult['courses_processed'],
            'gradesupdated' => $aggregatedresult['grades_updated'],
            'warnings' => $aggregatedresult['warnings']
        ];
    }

    /**
     * Define the return structure for the web service.
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_TEXT, 'The status of the operation (e.g., success).'),
                'coursesprocessed' => new external_value(PARAM_INT, 'Number of courses processed from the CSV.'),
                'gradesupdated' => new external_value(PARAM_INT, 'Total number of grade items updated.'),
                'warnings' => new external_multiple_structure(
                    new external_single_structure([
                        'warningcode' => new external_value(PARAM_ALPHANUMEXT, 'A code for the warning.'),
                        'message' => new external_value(PARAM_TEXT, 'The warning message.')
                    ]),
                    'List of warnings during processing.',
                    VALUE_DEFAULT
                )
            ]
        );
    }
}
