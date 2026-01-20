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
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/lib/grade/grade_item.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_course;
use grade_item;

/**
 * Regrade final grades web service.
 *
 * @package   tool_eabcetlbridge
 * @category  external
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class regrade_final_grades extends external_api {

    /**
     * Returns parameters for the execute method.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The id of the course to regrade.'),
            'userid' => new external_value(
                PARAM_INT,
                'The id of the user to regrade. If not provided, all users will be regraded.', VALUE_DEFAULT,
                null
            ),
            'itemid' => new external_value(
                PARAM_INT,
                'The id of the grade_item to regrade. If not provided, all items will be regraded.', VALUE_DEFAULT,
                null
            )
        ]);
    }

    /**
     * Web service that regrades final grades for a course.
     *
     * @param int $courseid The course ID.
     * @param int|null $userid The user ID (optional).
     * @param int|null $itemid The grade item ID (optional).
     * @return array A summary of the operation.
     */
    public static function execute($courseid, $userid = null, $itemid = null) {
        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'userid' => $userid,
            'itemid' => $itemid
        ]);

        // Check capabilities. The user calling the service needs permission to regrade.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $gradeitem = null;
        if (!empty($params['itemid'])) {
            $gradeitem = grade_item::fetch(['id' => $params['itemid'], 'courseid' => $params['courseid']]);
            if (!$gradeitem) {
                throw new \moodle_exception('invalidgradeitem', 'grades');
            }
        }

        // The function expects null for optional parameters, not 0.
        $uid = !empty($params['userid']) ? $params['userid'] : null;

        // Force a regrade of the course.
        /*$courseitem = grade_item::fetch_course_item($courseid);
        $courseitem->needsupdate = true;
        $courseitem->update();*/

        $result = grade_regrade_final_grades($params['courseid'], $uid, $gradeitem);

        return [
            'status' => $result,
            'message' => 'Final grades regrading process finished.'
        ];
    }

    /**
     * Define the return structure for the web service.
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'True if the operation was successful, false otherwise.'),
            'message' => new external_value(PARAM_TEXT, 'A summary message of the operation.')
        ]);
    }
}
