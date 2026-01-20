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

/**
 * Web service to count users pending grade synchronization.
 *
 * @package   tool_eabcetlbridge
 * @category  external
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class count_pending_users_for_grade_sync extends external_api {

    /**
     * No parameters needed for this web service.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'fromdate' => new external_value(PARAM_INT, 'Optional start Unix timestamp to filter enrollments.', VALUE_DEFAULT),
            'todate' => new external_value(PARAM_INT, 'Optional end Unix timestamp to filter enrollments.', VALUE_DEFAULT),
            'courseshortname' => new external_value(PARAM_TEXT, 'Filter by a specific course shortname.', VALUE_DEFAULT),
            'companyrut' => new external_value(PARAM_TEXT, 'Filter users by a specific company RUT.', VALUE_DEFAULT)
        ]);
    }

    /**
     * Count the total number of users pending grade sync.
     *
     * @param int|null $fromdate
     * @param int|null $todate
     * @param string|null $courseshortname
     * @param string|null $companyrut
     * @return array
     */
    public static function execute($fromdate = null, $todate = null, $courseshortname = null, $companyrut = null) {
        $params = self::validate_parameters(self::execute_parameters(), [
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
            $count = $manager->count_pending_users(
                $params['fromdate'],
                $params['todate'],
                $params['courseshortname'],
                $params['companyrut']
            );
            return ['total' => $count];
        } catch (\Exception $e) {
            throw new moodle_exception('error_counting_pending_users', 'tool_eabcetlbridge', '', null, $e->getMessage());
        }
    }

    /**
     * Define the return structure for the web service.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'The total number of pending user-course enrollments.')
        ]);
    }
}
