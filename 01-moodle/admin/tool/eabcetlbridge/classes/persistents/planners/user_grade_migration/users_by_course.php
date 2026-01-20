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
use tool_eabcetlbridge\persistents\batch_files;
use tool_eabcetlbridge\persistents\mappers\user_grade_migration\users_by_file;

/**
 * User Planner persistent.
 *
 * @package   tool_eabcetlbridge
 * @category  planners
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users_by_course extends planner {

    /**
     * Table name for the persistent.
     * @var string
     */
    const TABLE = 'eabcetlbridge_planner';

    /** @var string The source type of the records (e.g., 'user', 'course') */
    const TYPE = 'user';

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
            'itemidentifier' => ['type' => PARAM_INT], // Userid.
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

        $notguest = $DB->sql_equal('u.username', ':guest', true, true, true);
        $type = self::TYPE;
        $objective = self::OBJECTIVE;

        $sql = "SELECT DISTINCT u.id AS itemidentifier,
                       c.id AS courseid
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                 WHERE $notguest
                       AND u.deleted = 0
                       AND NOT EXISTS (
                           SELECT 1
                             FROM {eabcetlbridge_planner} plan
                            WHERE plan.itemidentifier = u.id
                                  AND plan.courseid = c.id
                                  AND plan.type = :type
                                  AND plan.objective = :objective)
              ORDER BY c.id ASC, u.timecreated ASC";

        $params = [
            'guest' => 'guest',
            'type' => $type,
            'objective' => $objective
        ];

        return [$sql, $params];

    }

    /**
     * Retrieves records from the database with a specific status.
     *
     * Retrieves records from the database that have the specified status.
     * The function takes two optional parameters: $limitfrom and $limitnum.
     * $limitfrom is the number of records to skip before starting to retrieve records.
     * $limitnum is the number of records to retrieve. If not specified, the default value is 1000.
     *
     * @param int $limitfrom The number of records to skip before starting to retrieve records.
     * @param int $limitnum The number of records to retrieve.
     * @return array An array of objects, each containing the retrieved records.
     */
    public static function get_records_for_status_update($limitfrom = 0, $limitnum = 1000) {
        global $DB;

        if ($limitnum <= 0 || $limitnum == 1000) {
            $configlimitnum = get_config('tool_eabcetlbridge', 'planner_status_update_limitnum');
            if ($configlimitnum > 0) {
                $limitnum = $configlimitnum;
            } else {
                $limitnum = 1000;
            }
        }

        $courseplannerfields = courses::get_sql_fields('c', 'c');
        $userplannerfields = self::get_sql_fields('u', 'u');
        $batchfilefields = batch_files::get_sql_fields('bf', 'bf');

        $params = [
            'utype' => self::TYPE,
            'uobjective' => self::OBJECTIVE,
            'ustatus' => self::STATUS_PENDING,
            'ctype' => courses::TYPE,
            'cobjective' => courses::OBJECTIVE,
            'cstatus' => courses::STATUS_COMPLETED,
            'batchfilestatus' => batch_files::STATUS_COMPLETED
        ];

        $sql = "SELECT $userplannerfields, $courseplannerfields, $batchfilefields, user.timecreated
                  FROM {eabcetlbridge_planner} u
                  JOIN {eabcetlbridge_planner} c ON u.courseid = c.courseid
                  JOIN {eabcetlbridge_batch_file} bf ON c.batchfileid = bf.id
                  JOIN {user} user ON user.id = u.itemidentifier
                 WHERE u.type = :utype
                       AND u.objective = :uobjective
                       AND u.status = :ustatus
                       AND c.type = :ctype
                       AND c.objective = :cobjective
                       AND c.status = :cstatus
                       AND bf.status = :batchfilestatus";

        $users = $courses = $batchfiles = $usertimecreated = [];
        $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
        foreach ($rs as $key => $row) {
            $record = self::extract_record($row, 'u');
            $users[$key] = new self(0, $record);

            $record = self::extract_record($row, 'c');
            $courses[$key] = new courses(0, $record);

            $record = self::extract_record($row, 'bf');
            $batchfiles[$key] = new batch_files(0, $record);

            $usertimecreated[$key] = $row->timecreated;
        }
        $rs->close();

        return [$users, $courses, $batchfiles, $usertimecreated];

    }


    /**
     * Retrieves records from the database with a specific status and related users by file.
     * The function takes three optional parameters: $limitfrom, $limitnum. $limitfrom is the
     * number of records to skip before starting to retrieve records.
     * $limitnum is the number of records to retrieve. If not specified, the default value is 1000.
     *
     * This function will execute a SQL query that retrieves records from the database that
     * have the specified status and related users by file.
     * The function will return an array containing the retrieved records.
     *
     * @param int $limitfrom The number of records to skip before starting to retrieve records.
     * @param int $limitnum The number of records to retrieve.
     * @return array An array of objects, each containing the retrieved records.
     */
    public static function get_records_for_status_update_with_users_by_file($limitfrom = 0, $limitnum = 1000) {
        global $DB;

        if ($limitnum <= 0 || $limitnum == 1000) {
            $configlimitnum = get_config('tool_eabcetlbridge', 'planner_status_update_limitnum');
            if ($configlimitnum > 0) {
                $limitnum = $configlimitnum;
            } else {
                $limitnum = 1000;
            }
        }

        $courseplannerfields = courses::get_sql_fields('c', 'c');
        $userplannerfields = self::get_sql_fields('u', 'u');
        $batchfilefields = batch_files::get_sql_fields('bf', 'bf');

        $params = [
            'utype' => self::TYPE,
            'uobjective' => self::OBJECTIVE,
            'ustatus' => self::STATUS_PENDING,
            'ctype' => courses::TYPE,
            'cobjective' => courses::OBJECTIVE,
            'cstatus' => courses::STATUS_COMPLETED,
            'batchfilestatus' => batch_files::STATUS_COMPLETED,
            'idmapsourcetype' => users_by_file::SOURCETYPE
        ];

        $sql = "SELECT $userplannerfields, $courseplannerfields, $batchfilefields, user.timecreated
                  FROM {eabcetlbridge_planner} u
                  JOIN {eabcetlbridge_planner} c ON u.courseid = c.courseid
                  JOIN {user} user ON user.id = u.itemidentifier
                  JOIN {eabcetlbridge_id_map} idmap ON idmap.source_key = user.username
                                                   AND c.courseid = idmap.courseid
                  JOIN {eabcetlbridge_batch_file} bf ON idmap.target_id = bf.id
                 WHERE u.type = :utype
                       AND u.objective = :uobjective
                       AND u.status = :ustatus
                       AND c.type = :ctype
                       AND c.objective = :cobjective
                       AND c.status = :cstatus
                       AND bf.status = :batchfilestatus
                       AND idmap.source_type = :idmapsourcetype";

        $users = $courses = $batchfiles = $usertimecreated = [];
        $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $limitnum);
        foreach ($rs as $key => $row) {
            $record = self::extract_record($row, 'u');
            $users[$key] = new self(0, $record);

            $record = self::extract_record($row, 'c');
            $courses[$key] = new courses(0, $record);

            $record = self::extract_record($row, 'bf');
            $batchfiles[$key] = new batch_files(0, $record);

            $usertimecreated[$key] = $row->timecreated;
        }
        $rs->close();

        return [$users, $courses, $batchfiles, $usertimecreated];

    }

    /**
     * Mark processed users as completed.
     * 
     * @param int $batchfileid The id of the batch file that has been processed.
     * @param int $courseid The id of the course that has been processed.
     * @param array $processedusers An array of user ids that have been processed.
     */
    public static function mark_processed_users(
            int $batchfileid,
            int $courseid,
            array $processedusers = []) {

        /** @global \moodle_database $DB */
        global $DB;

        [$userisinsql, $userisinparams] = $DB->get_in_or_equal($processedusers, SQL_PARAMS_NAMED);
        $notcompleted = $DB->sql_equal('status', ':differentstatus', false, false, true);

        $userisinparams['courseid'] = $courseid;
        $userisinparams['batchfileid'] = $batchfileid;
        $userisinparams['timemodified'] = time();
        $userisinparams['type'] = self::TYPE;
        $userisinparams['objective'] = self::OBJECTIVE;
        $userisinparams['newstatus'] = self::STATUS_COMPLETED;
        $userisinparams['differentstatus'] = self::STATUS_COMPLETED;

        $sql = "UPDATE {eabcetlbridge_planner}
                       SET status = :newstatus,
                           batchfileid = :batchfileid,
                           timemodified = :timemodified
                     WHERE itemidentifier $userisinsql
                           AND courseid = :courseid
                           AND type = :type
                           AND objective = :objective
                           AND $notcompleted";

        $DB->execute($sql, $userisinparams);

    }


}
