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
use tool_eabcetlbridge\grades\pending_grades_manager;
use tool_eabcetlbridge\external\sync_user_grades;

/**
 * Web service to get a paginated list of users pending grade synchronization.
 *
 * @package   tool_eabcetlbridge
 * @category  external
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_pending_users_for_grade_sync extends external_api {

    /**
     * Define parameters for the execute method.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'page' => new external_value(PARAM_INT, 'Page number for pagination, starting from 0.', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Number of users per page. Use 0 to get all users.', VALUE_DEFAULT, 50),
            'fromdate' => new external_value(PARAM_INT, 'Optional start Unix timestamp to filter enrollments.', VALUE_DEFAULT),
            'todate' => new external_value(PARAM_INT, 'Optional end Unix timestamp to filter enrollments.', VALUE_DEFAULT),
            'courseshortname' => new external_value(PARAM_TEXT, 'Filter by a specific course shortname.', VALUE_DEFAULT, null),
            'companyrut' => new external_value(PARAM_TEXT, 'Filter users by a specific company RUT.', VALUE_DEFAULT, null),
        ]);
    }

    /**
     * Get a paginated list of users pending grade sync.
     *
     * @param int $page
     * @param int $perpage
     * @param int|null $fromdate
     * @param int|null $todate
     * @param string|null $courseshortname
     * @param string|null $companyrut
     * @return array An array of user objects.
     */
    public static function execute(
            $page = 0,
            $perpage = 50,
            $fromdate = null,
            $todate = null,
            $courseshortname = null,
            $companyrut = null) {
        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'page' => $page,
            'perpage' => $perpage,
            'fromdate' => $fromdate,
            'todate' => $todate,
            'courseshortname' => $courseshortname,
            'companyrut' => $companyrut
        ]);

        // Check capabilities.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        try {
            $manager = new pending_grades_manager();
            return $manager->get_pending_users(
                $params['page'],
                $params['perpage'],
                $params['fromdate'],
                $params['todate'],
                $params['courseshortname'],
                $params['companyrut']
            );
        } catch (\Exception $e) {
            throw new moodle_exception('error_getting_pending_users', 'tool_eabcetlbridge', '', null, $e->getMessage());
        }
    }

    /**
     * Define the return structure for the web service.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'username' => new external_value(PARAM_TEXT, 'Username'),
                'useremail' => new external_value(PARAM_TEXT, 'User email'),
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'coursename' => new external_value(PARAM_TEXT, 'Course short name'),
                'finalgrade' => new external_value(PARAM_FLOAT, 'Final grade'),
                'timecreated' => new external_value(PARAM_INT, 'Time created'),
                'status' => new external_value(PARAM_TEXT, 'Status text'),
            ]),
            'List of users pending grade synchronization.'
        );
    }
}
