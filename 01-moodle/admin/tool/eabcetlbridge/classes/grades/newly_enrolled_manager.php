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
 * Manages the process of finding newly enrolled users.
 *
 * @package   tool_eabcetlbridge
 * @category  grades
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class newly_enrolled_manager {

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
        $this->db35 = null;
    }

    /**
     * Establishes the connection to the Moodle 3.5 database if not already connected.
     *
     * @return void
     * @throws \Exception If the connection cannot be established.
     */
    private function ensure_db35_connection(): void {
        if ($this->db35) {
            return;
        }

        try {
            $utils35 = new utils35();
            if (!$utils35->validate_connection()) {
                throw new \Exception('Could not establish connection to the external Moodle 3.5 database.');
            }
            $this->db35 = $utils35->db;
        } catch (\Exception $e) {
            throw new \Exception('Failed to connect to external DB: ' . $e->getMessage());
        }
    }

    /**
     * Finds newly enrolled users within a given timeframe.
     *
     * @param int $page The page number for pagination (starts from 0).
     * @param int $perpage The number of records per page.
     * @param int $fromdate Required start timestamp to filter enrollments.
     * @param int|null $todate Optional end timestamp to filter enrollments.
     * @param bool $checkgrade35 If true, checks for a passing grade in Moodle 3.5.
     * @return array An array of objects with user and course information.
     *               The array contains 'users' and 'total' count.
     */
    public function get_newly_enrolled_users(
            int $page,
            int $perpage,
            int $fromdate,
            ?int $todate,
            bool $checkgrade35
        ): array {

        global $CFG;

        $conditions = [
            'u.mnethostid = :mnethostid',
            'u.deleted = 0',
            'mue.timecreated >= :fromdate'
        ];
        $params = [
            'mnethostid' => $CFG->mnet_localhost_id,
            'fromdate' => $fromdate
        ];

        if ($todate) {
            $conditions[] = 'mue.timecreated <= :todate';
            $params['todate'] = $todate;
        }

        $whereclauses = implode("\n AND ", $conditions);

        // First, get the total count of records.
        $countsql = "SELECT COUNT(mue.id)
                       FROM {user} u
                       JOIN {user_enrolments} mue ON mue.userid = u.id
                       JOIN {enrol} e ON e.id = mue.enrolid
                       JOIN {course} c ON c.id = e.courseid
                      WHERE {$whereclauses}";
        $total = $this->db->count_records_sql($countsql, $params);

        $sql = "SELECT u.id AS userid,
                       u.username,
                       c.id AS courseid,
                       c.shortname AS courseshortname,
                       mue.timecreated AS enrolmenttime
                  FROM {user} u
                  JOIN {user_enrolments} mue ON mue.userid = u.id
                  JOIN {enrol} e ON e.id = mue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                 WHERE {$whereclauses}
              ORDER BY mue.timecreated ASC";

        $offset = $page * $perpage;
        $enrolledusers = $this->db->get_records_sql($sql, $params, $offset, $perpage);

        if (empty($enrolledusers) || !$checkgrade35) {
            return [
                'users' => array_values($enrolledusers),
                'total' => $total,
            ];
        }

        // 2. Preparación para consulta externa masiva (Bulk processing).
        $usernames = [];
        $courseshortnames = [];

        // Mapeamos para búsqueda rápida luego: "username|shortname" => objeto.
        $mapusercourse = [];

        foreach ($enrolledusers as $user) {
            $usernames[$user->username] = $user->username;
            $courseshortnames[$user->courseshortname] = $user->courseshortname;
            // We initialize to false by default.
            $user->hasgrades35 = false;
            // Composite key for mapping.
            $key = $user->username . '|' . $user->courseshortname;
            $mapusercourse[$key] = $user;
        }

        // 3. Consulta externa optimizada (Una sola Query).
        $this->ensure_db35_connection();

        // Obtenemos calificaciones aprobatorias para este conjunto de usuarios y cursos.
        $passinggrades = $this->fetch_external_passing_grades($usernames, $courseshortnames);

        // 4. Asignación de resultados en memoria.
        foreach ($passinggrades as $record) {
            $key = $record->username . '|' . $record->courseshortname;
            if (isset($mapusercourse[$key])) {
                $mapusercourse[$key]->hasgrades35 = true;
            }
        }

        return [
            'users' => array_values($enrolledusers),
            'total' => $total,
        ];
    }

    /**
     * Retrieves the passing grades for a given set of users and courses.
     *
     * @param array $usernames The usernames to fetch grades for.
     * @param array $courseshortnames The course shortnames to fetch grades for.
     * @return array An array containing the passing grades for each user and course.
     */
    private function fetch_external_passing_grades(array $usernames, array $courseshortnames): array {
        if (empty($usernames) || empty($courseshortnames)) {
            return [];
        }

        list($usql, $uparams) = $this->db35->get_in_or_equal($usernames, SQL_PARAMS_NAMED, 'user');
        list($csql, $cparams) = $this->db35->get_in_or_equal($courseshortnames, SQL_PARAMS_NAMED, 'course');

        $concat = $this->db35->sql_concat('u.username', "'|'", 'c.shortname');

        $sql = "SELECT DISTINCT $concat AS uniqueid, u.username, c.shortname AS courseshortname
                  FROM {user} u
                  JOIN {user_enrolments} mue ON mue.userid = u.id
                  JOIN {enrol} e ON e.id = mue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                  JOIN {grade_items} gi ON gi.courseid = c.id
                  JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
                 WHERE u.username $usql
                       AND c.shortname $csql
                       AND gg.finalgrade > 0
                       AND u.deleted = 0";

        $params = array_merge($uparams, $cparams);

        return $this->db35->get_records_sql($sql, $params);
    }
}
