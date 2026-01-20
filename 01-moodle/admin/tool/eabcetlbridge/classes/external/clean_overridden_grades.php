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
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/lib/grade/grade_grade.php');

use external_value;
use external_single_structure;
use external_function_parameters;
use context_system;
use grade_grade;

/**
 * Clean overridden grades web service.
 *
 * @package   tool_eabcetlbridge
 * @category  external
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean_overridden_grades extends \external_api {

    /**
     * Returns parameters for the execute method.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'startdate' => new external_value(
                PARAM_INT,
                'Optional start date (timestamp) to filter grades. Defaults to system setting.', 
                VALUE_DEFAULT,
                0
            ),
            'enddate' => new external_value(
                PARAM_INT,
                'Optional end date (timestamp) to filter grades. Defaults to system setting.', 
                VALUE_DEFAULT,
                0
            ),
            'limit' => new external_value(
                PARAM_INT,
                'Optional limit for the number of grades to process. Defaults to system setting.', 
                VALUE_DEFAULT,
                100
            )
        ]);
    }

    /**
     * Web service that cleans overridden grades.
     *
     * @param int $startdate Optional start date.
     * @param int $enddate Optional end date.
     * @param int $limit Optional limit.
     * @return array A summary of the processing.
     */
    public static function execute($startdate = 0, $enddate = 0, $limit = 0) {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'startdate' => $startdate,
            'enddate' => $enddate,
            'limit' => $limit
        ]);

        // The service must be executed by a user with the right capabilities.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $now = time();
        $sqlparams = [];

        // Use provided limit or get from config.
        $limitnum = $params['limit'] ?: (int)get_config('tool_eabcetlbridge', 'clean_overridden_grades_limitnum');
        if (!$limitnum) {
            $limitnum = 100;
        }

        // Build date clauses.
        $limitstartdatesql = '';
        $startdateparam = $params['startdate'] ?: (int)get_config('tool_eabcetlbridge', 'clean_overridden_grades_startdate');
        if ($startdateparam) {
            $limitstartdatesql = ' AND timemodified >= :startdate';
            $sqlparams['startdate'] = $startdateparam;
        }

        $limitendatesql = '';
        $enddateparam = $params['enddate'] ?: (int)get_config('tool_eabcetlbridge', 'clean_overridden_grades_endate');
        if ($enddateparam) {
            $limitendatesql = ' AND timemodified < :enddate';
            $sqlparams['enddate'] = $enddateparam;
        }

        $sql = "SELECT COUNT(id)
                    FROM {grade_grades}
                    WHERE overridden > 0
                        $limitstartdatesql
                        $limitendatesql";

        $count = $DB->count_records_sql($sql, $sqlparams);
        if ($count == 0) {
            return [
                'status' => 'success',
                'message' => 'No overridden grades found to clean.',
                'totalfound' => 0,
                'cleanedcount' => 0,
                'remaining' => 0
            ];
        }

        $sql = "SELECT *
                    FROM {grade_grades}
                    WHERE overridden > 0
                        $limitstartdatesql
                        $limitendatesql";

        $grades = [];
        $rs = $DB->get_recordset_sql($sql, $sqlparams, 0, $limitnum);
        foreach ($rs as $grade) {
            $grades[] = new grade_grade($grade, true);
        }
        $rs->close();

        $i = 0;
        foreach ($grades as $grade) {
            $grade->overridden = 0;
            $grade->timemodified = $now;
            $grade->update();
            $i++;
        }

        return [
            'status' => 'success',
            'message' => "Cleaned $i overridden grades.",
            'totalfound' => $count,
            'cleanedcount' => $i,
            'remaining' => $count - $i
        ];


    }

    /**
     * Define the return structure for the web service.
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'The status of the operation (e.g., success).'),
            'message' => new external_value(PARAM_TEXT, 'A summary message of the operation.'),
            'totalfound' => new external_value(PARAM_INT, 'Total number of overridden grades found.'),
            'cleanedcount' => new external_value(PARAM_INT, 'Number of grades cleaned in this execution.'),
            'remaining' => new external_value(PARAM_INT, 'Number of remaining grades to be cleaned.')
        ]);
    }
}
