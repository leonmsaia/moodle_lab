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

namespace tool_eabcetlbridge\grades;

defined('MOODLE_INTERNAL') || die;

use tool_eabcetlbridge\utils35;

/**
 * Manages the process of finding users who need their grades synchronized
 * from an external Moodle 3.5 DB to the local Moodle 4.5 DB.
 *
 * @package   tool_eabcetlbridge
 * @category  grades
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pending_grades_manager {

    /** @var \moodle_database External Moodle 3.5 database connection. */
    protected $db35;

    /** @var \moodle_database Local Moodle 4.5 database connection. */
    protected $db;

    /**
     * Constructor.
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;

        $utils35 = new utils35();
        if (!$utils35->validate_connection()) {
            throw new \Exception('Could not establish connection to the external Moodle 3.5 database.');
        }
        $this->db35 = $utils35->db;
    }

    /**
     * Gets the base SQL for querying user enrollments and grades.
     *
     * @param string $select The SELECT clause.
     * @param array $conditions An array of SQL conditions.
     * @param array $joins An array of additional SQL joins.
     * @return string The complete SQL query.
     */
    private function get_base_sql(string $select, array $conditions, array $joins = []): string {
        $joinclauses = implode("\n", $joins);
        $whereclauses = implode("\n AND ", $conditions);

        return "SELECT $select
                  FROM {user} u
                  JOIN {user_enrolments} mue ON mue.userid = u.id
                  JOIN {enrol} e ON e.id = mue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                  JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
                  {$joinclauses}
             LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
                 WHERE {$whereclauses}";
    }

    /**
     * Builds the conditions and joins for the SQL query based on filters.
     *
     * @param array &$conditions Reference to the conditions array.
     * @param array &$joins Reference to the joins array.
     * @param array &$params Reference to the parameters array.
     * @param string|null $courseshortname Optional course shortname.
     * @param string|null $companyrut Optional company RUT.
     */
    private function apply_filters(array &$conditions, array &$joins, array &$params, ?string $courseshortname, ?string $companyrut) {
        if (!empty($courseshortname)) {
            $conditions[] = 'c.shortname = :courseshortname';
            $params['courseshortname'] = $courseshortname;
        }
        if (!empty($companyrut)) {
            $joins[] = 'JOIN {company_users} mcu ON mcu.userid = u.id';
            $joins[] = 'JOIN {company} com ON com.id = mcu.companyid';
            $conditions[] = 'com.rut = :companyrut';
            $params['companyrut'] = $companyrut;
        }
    }

    /**
     * Finds users who have passed in Moodle 3.5 but not in Moodle 4.5.
     *
     * @param int $page The page number for pagination (starts from 0).
     * @param int $perpage The number of records per page (0 for all).
     * @param int|null $fromdate Optional start timestamp to filter enrollments.
     * @param int|null $todate Optional end timestamp to filter enrollments.
     * @param string|null $courseshortname Optional course shortname to filter by.
     * @param string|null $companyrut Optional company RUT to filter by.
     * @return array An array of objects with username and coursename.
     */
    public function get_pending_users(int $page = 0, int $perpage = 50, ?int $fromdate = null, ?int $todate = null, ?string $courseshortname = null, ?string $companyrut = null): array {
        // Base conditions for all queries.
        $baseconditions = [
            'u.mnethostid = 1',
            'u.deleted = 0'
        ];
        $params = [];

        if ($fromdate) {
            $baseconditions[] = 'mue.timecreated >= :fromdate';
            $params['fromdate'] = $fromdate;
        }
        if ($todate) {
            $baseconditions[] = 'mue.timecreated <= :todate';
            $params['todate'] = $todate;
        }

        $joins = [];

        // Apply optional filters.
        $this->apply_filters($baseconditions, $joins, $params, $courseshortname, $companyrut);

        // Conditions for each database.
        $conditions45 = array_merge($baseconditions, ['(gg.finalgrade < gi.gradepass OR gg.finalgrade IS NULL)']);

        // Build SQL for each database.
        $select45 = "u.id AS userid, u.username, u.email AS useremail, c.id AS courseid,";
        $select45 .= "c.shortname AS coursename, mue.timecreated AS timecreated";
        $sql45 = $this->get_base_sql($select45, $conditions45, $joins);

        // Fetch users from both databases.
        $rs = $this->db->get_recordset_sql($sql45, $params);
        $users45 = [];
        foreach ($rs as $record) {
            $users45[$record->userid . '::' . $record->courseid] = $record;
        }
        $rs->close();

        if (empty($users45)) {
            return [];
        }

        // Group usernames by coursename to optimize the query to the 3.5 DB.
        $usercoursepairs = [];
        foreach ($users45 as $key => $user) {
            $usercoursepairs[$user->coursename][$user->username] = $user;
        }

        $approvedin35 = [];
        foreach ($usercoursepairs as $cshortname => $usernames) {
            list($usql, $uparams) = $this->db35->get_in_or_equal(array_keys($usernames), SQL_PARAMS_NAMED, 'uname');
            $sql35 = "SELECT u.username, gg.finalgrade
                        FROM {user} u
                        JOIN {user_enrolments} mue ON mue.userid = u.id
                        JOIN {enrol} e ON e.id = mue.enrolid
                        JOIN {course} c ON c.id = e.courseid
                        JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
                   LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
                       WHERE c.shortname = :courseshortname
                             AND u.username $usql
                             AND gg.finalgrade >= gi.gradepass
                             AND u.mnethostid = 1";
            $params35 = array_merge(['courseshortname' => $cshortname], $uparams);
            $results35 = $this->db35->get_records_sql($sql35, $params35);

            foreach ($results35 as $res) {
                $approvedin35[$res->username . '::' . $cshortname] = $res->finalgrade;
            }
        }

        $pendingusers = [];
        foreach ($users45 as $user) {
            $key = $user->username . '::' . $user->coursename;
            if (array_key_exists($key, $approvedin35)) {
                $user->finalgrade = $approvedin35[$key];
                $user->status = 'pending_sync';
                // The web service expects an array of objects, not an associative array.
                $pendingusers[] = (object) (array) $user;
            }
        }

        // Apply pagination to the final list.
        if ($perpage == 0) { // A perpage of 0 means return all results.
            return $pendingusers;
        }
        $offset = $page * $perpage;
        return array_slice($pendingusers, $offset, $perpage);
    }

    /**
     * Counts the total number of users pending grade synchronization.
     *
     * @param int|null $fromdate Optional start timestamp to filter enrollments.
     * @param int|null $todate Optional end timestamp to filter enrollments.
     * @param string|null $courseshortname Optional course shortname to filter by.
     * @param string|null $companyrut Optional company RUT to filter by.
     * @return int The total count.
     */
    public function count_pending_users(?int $fromdate = null, ?int $todate = null, ?string $courseshortname = null, ?string $companyrut = null): int {
        // This method is optimized to avoid loading huge datasets into memory.
        // It fetches users not approved in 4.5 and then checks their status in 3.5 in batches.

        $baseconditions = [
            'u.mnethostid = 1',
            'u.deleted = 0'
        ];
        $params = [];

        if ($fromdate) {
            $baseconditions[] = 'mue.timecreated >= :fromdate';
            $params['fromdate'] = $fromdate;
        }
        if ($todate) {
            $baseconditions[] = 'mue.timecreated <= :todate';
            $params['todate'] = $todate;
        }

        $joins = [];

        $this->apply_filters($baseconditions, $joins, $params, $courseshortname, $companyrut);

        $conditions45 = array_merge($baseconditions, ['(gg.finalgrade < gi.gradepass OR gg.finalgrade IS NULL)']);

        $select45 = 'u.username, c.shortname AS coursename';
        $sql45 = $this->get_base_sql($select45, $conditions45, $joins);
        $rs = $this->db->get_recordset_sql($sql45, $params);
        $users45 = [];
        foreach ($rs as $record) {
            $users45[] = $record;
        }
        $rs->close();

        if (empty($users45)) {
            return 0;
        }

        // Group usernames by coursename to optimize the query to the 3.5 DB.
        $usercoursepairs = [];
        foreach ($users45 as $user) {
            $usercoursepairs[$user->coursename][] = $user->username;
        }

        $pendingcount = 0;
        foreach ($usercoursepairs as $cshortname => $usernames) {
            // Check in batches to avoid huge IN clauses.
            $chunks = array_chunk($usernames, 500); // Process 500 users at a time.
            foreach ($chunks as $chunk) {
                list($usql, $uparams) = $this->db35->get_in_or_equal($chunk, SQL_PARAMS_NAMED, 'uname');
                $sql35 = "SELECT COUNT(u.id)
                            FROM {user} u
                            JOIN {user_enrolments} mue ON mue.userid = u.id
                            JOIN {enrol} e ON e.id = mue.enrolid
                            JOIN {course} c ON c.id = e.courseid
                            JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
                            JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
                           WHERE c.shortname = :courseshortname
                                 AND u.username $usql
                                 AND gg.finalgrade >= gi.gradepass
                                 AND u.mnethostid = 1";
                $params35 = array_merge(['courseshortname' => $cshortname], $uparams);
                $pendingcount += $this->db35->count_records_sql($sql35, $params35);
            }
        }

        return $pendingcount;
    }

    /**
     * Checks if a user is approved in a specific course on the local Moodle instance.
     *
     * @param string $username The user's username.
     * @param string $courseshortname The course's shortname.
     * @return bool True if the user is approved, false otherwise.
     */
    public function is_user_approved_locally(string $username, string $courseshortname): bool {
        $sql = "SELECT 1
                  FROM {user} u
                  JOIN {course} c ON c.shortname = :courseshortname
                  JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
             LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
                 WHERE u.username = :username
                   AND gg.finalgrade >= gi.gradepass";

        $params = [
            'username' => $username,
            'courseshortname' => $courseshortname
        ];

        return $this->db->record_exists_sql($sql, $params);
    }
}
