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

namespace tool_eabcetlbridge\persistents\planners;

use stdClass;
use lang_string;
use tool_eabcetlbridge\persistents\base_persistent;

/**
 * Planner persistent.
 *
 * @package   tool_eabcetlbridge
 * @category  persistents
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planner extends base_persistent {

    /**
     * Table name for the persistent.
     * @var string
     */
    const TABLE = 'eabcetlbridge_planner';

    /** @var string The source type of the records (e.g., 'user', 'course') */
    const TYPE = '';

    /** @var string The source objective of the records (e.g., 'user_grade_migration', 'process_user_record') */
    const OBJECTIVE = '';

        /** @var string The table name for the prefix */
    const PREFIXTABLE = 'plan';

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
            'type' => ['type' => PARAM_TEXT, 'default' => static::TYPE],
            'objective' => ['type' => PARAM_TEXT, 'default' => static::OBJECTIVE],
            'itemidentifier' => ['type' => PARAM_INT], // Userid.
            'status' => ['type' => PARAM_INT, 'default' => static::STATUS_PENDING]
        ];
    }

    /**
     * Returns a SQL query string for retrieving new records from the database that
     * haven't been mapped yet.
     *
     * The query uses a LEFT JOIN to find records that don't have a corresponding
     * mapping in the eabcetlbridge_id_map table.
     * The WHERE clause filters out records that have been deleted.
     *
     * @return array An array containing the SQL query and an array of parameters.
     */
    public static function get_new_records_sql() {
        return ['', []];
    }

    /**
     * Generates a SQL query that retrieves records from the database with a specific status.
     *
     * The query retrieves records from the eabcetlbridge_planner table with the specified status.
     * It filters out records that have been deleted and don't have the specified type and objective.
     * The query is ordered by the time the record was created, with the most recently created records first.
     *
     * @param int $status The status of the records to retrieve.
     * @return array An array containing the SQL query and an array of parameters.
     */
    public static function get_records_by_status_sql($status) {

        $type = static::TYPE;
        $objective = static::OBJECTIVE;
        $prefix = static::PREFIXTABLE;
        $fields = static::get_sql_fields($prefix , $prefix);

        $sql = "SELECT $fields
                  FROM {eabcetlbridge_planner} $prefix
                 WHERE $prefix.status = :status
                       AND $prefix.type = :type
                       AND $prefix.objective = :objective
              ORDER BY $prefix.timecreated ASC";

        $params = [
            'type' => $type,
            'objective' => $objective,
            'status' => $status
        ];

        return [$sql, $params];
    }

    /**
     * Returns the number of records from the database that should be processed by the planner.
     *
     * This function will execute a SQL query that retrieves the count of records from the database that
     * should be processed by the planner.
     *
     * @return int The number of records from the database that should be processed by the planner.
     */
    public static function count_new_records() {
        /** @global \moodle_database $DB */
        global $DB;

        [$sql, $params] = static::get_new_records_sql();

        $sql = "SELECT COUNT(*) FROM ({$sql}) map";

        $count = $DB->count_records_sql($sql, $params);

        return $count;
    }

    /**
     * Returns the number of records from the database that have a specific status.
     *
     * This function will execute a SQL query that retrieves the count of records from the database that
     * have the specified status.
     *
     * @param int $status The status of the records to count.
     * @return int The number of records from the database that have the specified status.
     */
    public static function count_records_by_status($status) {
        /** @global \moodle_database $DB */
        global $DB;

        [$sql, $params] = static::get_records_by_status_sql($status);

        $sql = "SELECT COUNT(*) FROM ({$sql}) map";

        $count = $DB->count_records_sql($sql, $params);

        return $count;
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
    public static function get_records_by_status($status = self::STATUS_PENDING, $limitfrom = 0, $limitnum = 1000) {
        /** @global \moodle_database $DB */
        global $DB;

        if ($limitnum <= 0 || $limitnum == 1000) {
            $configlimitnum = get_config('tool_eabcetlbridge', 'planner_limitnum');
            if ($configlimitnum > 0) {
                $limitnum = $configlimitnum;
            } else {
                $limitnum = 1000;
            }
        }

        [$sql, $params] = static::get_records_by_status_sql($status);

        $persistents = [];
        $records = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        foreach ($records as $row) {
            $persistents[] = new static(0, $row);
        }

        return $persistents;
    }

    /**
     * Populates the eabcetlbridge_id_map table with new records from the database that haven't been mapped yet.
     *
     * Retrieves new records from the database and inserts them into the eabcetlbridge_id_map table.
     * The function takes two optional parameters: $limitfrom and $limitnum. $limitfrom is the number of records to skip before starting to retrieve records.
     * $limitnum is the number of records to retrieve. If not specified, the default value is 1000.
     *
     * @param int $limitfrom The number of records to skip before starting to retrieve records.
     * @param int $limitnum The number of records to retrieve.
     * @return void
     */
    public static function populate_new_records($limitfrom = 0, $limitnum = 1000, $params = []) {
        /** @global \moodle_database $DB */
        global $DB;

        if ($limitnum <= 0 || $limitnum == 1000) {
            $configlimitnum = get_config('tool_eabcetlbridge', 'planner_limitnum');
            if ($configlimitnum > 0) {
                $limitnum = $configlimitnum;
            } else {
                $limitnum = 1000;
            }
        }

        [$sql, $newparams] = static::get_new_records_sql();

        $type = $newparams['type'] ?? static::TYPE;
        $objective = $newparams['objective'] ?? static::OBJECTIVE;
        $configid = $params['configid'] ?? 0;

        $rs = $DB->get_recordset_sql($sql, $newparams, $limitfrom, $limitnum);
        $records = [];
        foreach ($rs as $row) {
            $mapping = new stdClass();
            $mapping->parenttaskid = null;
            $mapping->courseid = $row->courseid;
            $mapping->itemidentifier = $row->itemidentifier;
            $mapping->type = $type;
            $mapping->objective = $objective;
            $mapping->status = static::STATUS_PENDING;
            if ($configid > 0) {
                $mapping->configid = $configid;
            }
            $records[] = static::clean_record($mapping);
        }
        $rs->close();

        $DB->insert_records(static::TABLE, $records);
    }

    /**
     * Return the available type options with their translated names.
     *
     * The type options are:
     * - course: Course type.
     * - user: User type.
     *
     * @return array
     */
    public static function get_type_options() {
        return [
            'course' => get_string('planner_type_course', 'tool_eabcetlbridge'),
            'user' => get_string('planner_type_user', 'tool_eabcetlbridge'),
        ];
    }

    /**
     * Returns an array of type options for report view.
     * @return array
     */
    public static function get_type_options_for_view() {
        return [
            'default' => [
                'text' => 'Inconsistente',
                'color' => 'badge badge-warning'
            ],
            'checknull' => [
                'text' => 'NULL?',
                'color' => 'badge badge-warning'
            ],
            'options' => [
                'course' => [
                    'text' => new lang_string('planner_type_course', 'tool_eabcetlbridge'),
                    'color' => 'badge badge-info'
                ],
                'user' => [
                    'text' => new lang_string('planner_type_user', 'tool_eabcetlbridge'),
                    'color' => 'badge badge-black'
                ],
            ]
        ];
    }

    /**
     * Returns an array of type options for report view.
     * @return array
     */
    public static function get_objective_options_for_view() {
        return [
            'default' => [
                'text' => 'Inconsistente',
                'color' => 'badge badge-warning'
            ],
            'checknull' => [
                'text' => 'NULL?',
                'color' => 'badge badge-warning'
            ],
            'options' => [
                'user_grade_migration' => [
                    'text' => 'Migración de Calificaciones',
                    'color' => 'badge badge-info'
                ],
            ]
        ];
    }

    /**
     * Return the available status options with their translated names for manual upload.
     *
     * These status options are used when the user uploads a file manually.
     * The status options are:
     * - STATUS_PREVIEW (Pending): The file is uploaded successfully but not yet processed.
     * - STATUS_DISABLED (Processing): The file is being processed. The file is uploaded successfully.
     * - STATUS_PENDING (Pending): The file is uploaded successfully but not yet processed.
     *
     * @return array
     */
    public static function get_status_for_manual_upload() {
        return [
            self::STATUS_DISABLED => get_string('status_disabled', 'tool_eabcetlbridge'),
            self::STATUS_PENDING => get_string('status_pending', 'tool_eabcetlbridge'),
        ];
    }

}
