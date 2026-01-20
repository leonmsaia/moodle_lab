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

use core_external\external_api;
use core_external\external_value;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core\context\system as context_system;
use core\exception\moodle_exception;
use tool_eabcetlbridge\grades\newly_enrolled_manager;

/**
 * Web service to get a paginated list of newly enrolled users.
 *
 * @package   tool_eabcetlbridge
 * @category  external
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_newly_enrolled_users extends external_api {

    /**
     * Define parameters for the execute method.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'page' => new external_value(
                PARAM_INT,
                'Page number for pagination, starting from 0.',
                VALUE_DEFAULT,
                0
            ),
            'perpage' => new external_value(
                PARAM_INT,
                'Number of users per page.',
                VALUE_DEFAULT,
                500
            ),
            'fromdate' => new external_value(
                PARAM_INT,
                'Required start Unix timestamp to filter enrollments.',
                VALUE_REQUIRED
            ),
            'todate' => new external_value(
                PARAM_INT,
                'Optional end Unix timestamp to filter enrollments.',
                VALUE_DEFAULT,
                0
            ),
            'checkgrade35' => new external_value(
                PARAM_BOOL,
                'If true, checks if the user had a passing grade in Moodle 3.5.',
                VALUE_DEFAULT,
                false
            ),
        ]);
    }

    /**
     * Get a paginated list of newly enrolled users.
     *
     * @param int $page
     * @param int $perpage
     * @param int $fromdate
     * @param int|null $todate
     * @param bool $checkgrade35
     * @return array An array containing user enrollment objects and pagination info.
     */
    public static function execute(
            $page = 0,
            $perpage = 500,
            $fromdate = 0,
            $todate = null,
            $checkgrade35 = false) {

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'page' => $page,
            'perpage' => $perpage,
            'fromdate' => $fromdate,
            'todate' => $todate,
            'checkgrade35' => $checkgrade35,
        ]);

        // Check capabilities.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        try {
            $manager = new newly_enrolled_manager();
            $result = $manager->get_newly_enrolled_users(
                $params['page'],
                $params['perpage'],
                $params['fromdate'],
                $params['todate'],
                $params['checkgrade35']
            );

            $totalpages = $params['perpage'] > 0 ? ceil($result['total'] / $params['perpage']) : 0;
            $hasmorepages = $params['page'] < ($totalpages - 1);

            return [
                'users' => $result['users'],
                'total' => $result['total'],
                'page' => $params['page'],
                'perpage' => $params['perpage'],
                'totalpages' => $totalpages,
                'hasmorepages' => $hasmorepages,
            ];
        } catch (\Exception $e) {
            throw new moodle_exception('error_getting_newly_enrolled', 'tool_eabcetlbridge', '', null, $e->getMessage());
        }
    }

    /**
     * Define the return structure for the web service.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'users' => new external_multiple_structure(
                new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'username' => new external_value(PARAM_TEXT, 'Username'),
                    'courseid' => new external_value(PARAM_INT, 'Course ID'),
                    'courseshortname' => new external_value(PARAM_TEXT, 'Course short name'),
                    'enrolmenttime' => new external_value(PARAM_INT, 'User enrolment timestamp'),
                    'hasgrades35' => new external_value(
                        PARAM_BOOL,
                        'Whether the user had a passing grade in Moodle 3.5 for this course.',
                        VALUE_OPTIONAL
                    ),
                ]),
                'List of newly enrolled users.'
            ),
            'total' => new external_value(PARAM_INT, 'Total number of users found.'),
            'page' => new external_value(PARAM_INT, 'The current page number.'),
            'perpage' => new external_value(PARAM_INT, 'Number of users per page.'),
            'totalpages' => new external_value(PARAM_INT, 'Total number of pages.'),
            'hasmorepages' => new external_value(PARAM_BOOL, 'True if there are more pages of users to fetch.'),
        ]);
    }
}
