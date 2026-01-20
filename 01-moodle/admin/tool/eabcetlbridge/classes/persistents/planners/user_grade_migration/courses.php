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

namespace tool_eabcetlbridge\persistents\planners\user_grade_migration;

use tool_eabcetlbridge\persistents\planners\planner;

/**
 * User Planner persistent.
 *
 * @package   tool_eabcetlbridge
 * @category  planners
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courses extends planner {

    /**
     * Table name for the persistent.
     * @var string
     */
    const TABLE = 'eabcetlbridge_planner';

    /** @var string The source type of the records (e.g., 'user', 'course') */
    const TYPE = 'course';

    /** @var string The source objective of the records (e.g., 'generate_grade_csv', 'process_user_record') */
    const OBJECTIVE = 'user_grade_migration';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'configid' => ['type' => PARAM_INT, 'null' => NULL_ALLOWED, 'default' => 0],
            'parenttaskid' => ['type' => PARAM_INT, 'null' => NULL_ALLOWED, 'default' => 0],
            'courseid' => ['type' => PARAM_INT, 'null' => NULL_ALLOWED, 'default' => 0],
            'batchfileid' => ['type' => PARAM_INT, 'null' => NULL_ALLOWED, 'default' => 0],
            'type' => ['type' => PARAM_TEXT, 'default' => self::TYPE],
            'objective' => ['type' => PARAM_TEXT, 'default' => self::OBJECTIVE],
            'itemidentifier' => ['type' => PARAM_INT], // Courseid.
            'status' => ['type' => PARAM_INT, 'default' => self::STATUS_PENDING]
        ];
    }

    /**
     * Generates a SQL query to retrieve the next records from the database that
     * should be processed by the planner.
     *
     * @return array An array containing the SQL query and an array of parameters.
     */
    public static function get_new_records_sql() {

        /** @global \moodle_database $DB */
        global $DB;

        $type = self::TYPE;
        $objective = self::OBJECTIVE;

        $notsiteid = $DB->sql_equal('c.courseid', ':siteid', false, false, true);

        $sql = "SELECT DISTINCT c.courseid AS itemidentifier,
                       c.courseid AS courseid
                  FROM {eabcetlbridge_planner} c
                 WHERE NOT EXISTS (
                           SELECT 1
                             FROM {eabcetlbridge_planner} plan
                            WHERE plan.itemidentifier = c.courseid
                                  AND plan.courseid = c.courseid
                                  AND plan.type = :type
                                  AND plan.objective = :objective
                                  AND $notsiteid)
              ORDER BY c.timecreated ASC";

        $params = [
            'type' => $type,
            'objective' => $objective,
            'siteid' => SITEID
        ];

        return [$sql, $params];

    }

    /**
     * Retrieves records from the database with a specific status.
     *
     * This function will execute a SQL query that retrieves records from the database that have the specified status.
     * The function takes three optional parameters: $status, $limitfrom, and $limitnum. $status is the status of the records to retrieve.
     * $limitfrom is the number of records to skip before starting to retrieve records.
     * $limitnum is the number of records to retrieve. If not specified, the default value is 0.
     *
     * @param int $status The status of the records to retrieve.
     * @param int $limitfrom The number of records to skip before starting to retrieve records.
     * @param int $limitnum The number of records to retrieve.
     * @return self[] An array of objects, each containing the retrieved records.
     */
    public static function get_records_by_status($status = self::STATUS_PENDING, $limitfrom = 0, $limitnum = 10) {
        /** @global \moodle_database $DB */
        global $DB;

        if ($limitnum <= 0 || $limitnum == 10) {
            $configlimitnum = get_config('tool_eabcetlbridge', 'adhoc_for_getting_external_grades_limitnum');
            if ($configlimitnum > 0) {
                $limitnum = $configlimitnum;
            } else {
                $limitnum = 10;
            }
        }

        [$sql, $params] = static::get_records_by_status_sql($status);

        $persistents = [];
        $records = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        foreach ($records as $row) {
            $record = static::extract_record($row, self::PREFIXTABLE);
            $persistents[] = new static(0, $record);
        }

        return $persistents;
    }


}
