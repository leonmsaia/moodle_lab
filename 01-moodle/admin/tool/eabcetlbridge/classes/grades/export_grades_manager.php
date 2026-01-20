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

global $CFG;

require_once($CFG->dirroot . '/grade/lib.php');

/*require_once($CFG->libdir  . '/gradelib.php');
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/grade/export/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/report/user/lib.php');
require_once($CFG->dirroot . '/grade/report/user/externallib.php');
require_once($CFG->dirroot . '/admin/tool/eabcetlexporter/classes/request.php');
*/
use grade_plugin_return;
use grade_report_user;
use csv_export_writer;
use tool_eabcetlbridge\request;
use tool_eabcetlbridge\utils35;

/**
 * Export grades by user
 *
 * @package   tool_eabcetlbridge
 * @category  grades
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_grades_manager {

    /** @var int */
    protected $username;

    /** @var string|null */
    protected $courseshortname;

    /** @var \moodle_database */
    protected $db = null;

    /**
     * Constructor
     *
     * @param string $username
     * @param string|null $courseshortname
     */
    public function __construct(string $username, ?string $courseshortname = null) {
        $this->username = $username;
        $this->courseshortname = $courseshortname;
        $utils35 = new utils35();
        if (!$utils35->validate_connection()) {
            throw new \Exception('No se pudo establecer la conexión con la base de datos.');
        }
        $this->db = $utils35->db;
    }


    /**
     * Export grades for a user
     *
     * @param int $userid the user for which to export grades
     * @param string|null $courseshortname
     * @return array an object containing the exported grades
     */
    public static function execute(int $userid, ?string $courseshortname = null) {
        $manager = new self($userid, $courseshortname);
        $grades = $manager->get_grades();
        return $grades;
    }

    /**
     * Process the export of grades for a user
     *
     * @return array containing the exported grades
     */
    public function get_grades() {
        $user = $this->db->get_record('user', ['username' => $this->username], '*');

        if (empty($user)) {
            return [];
        }

        if ($this->courseshortname) {
            $usercourses = $this->get_user_course_from_external_db(
                $user->id, $this->courseshortname
            );
        } else {
            $usercourses = $this->get_user_courses_from_external_db($user->id);
        }

        $allgradesdata = [];

        foreach ($usercourses as $course) {
            $gradeitems = $this->get_user_grades_for_course($course->id, $user->id);

            if (empty($gradeitems)) {
                // Si no hay calificaciones en este curso, podemos omitirlo.
                continue;
            }

            $coursegradedata = [
                //'courseid' => $course->id,
                'courseshortname' => $course->shortname,
                'coursefullname' => $course->fullname,
                'gradeitems' => array_values($gradeitems), // Re-indexar el array.
            ];

            $allgradesdata[] = $coursegradedata;
        }

        // Devolver la estructura completa.
        return [
            'userid' => $user->id,
            'username' => $user->username,
            'userfullname' => fullname((object) $user),
            'courses' => $allgradesdata
        ];
    }

    /**
     * Retrieves the courses a user is enrolled in from the external database.
     *
     * This function replicates the basic functionality of enrol_get_users_courses
     * but queries the external database connection available in $this->db.
     * It only fetches active enrolments.
     *
     * @param int $userid The user's ID.
     * @return array An array of course objects (id, shortname, fullname).
     */
    private function get_user_courses_from_external_db(int $userid): array {
        $sql = "SELECT DISTINCT c.id, c.shortname, c.fullname
                  FROM {course} c
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = :userid
                       AND ue.status = 0"; // ENROL_USER_ACTIVE = 0.

        return $this->db->get_records_sql($sql, ['userid' => $userid]);
    }

    /**
     * Retrieves a specific course a user is enrolled in from the external database.
     *
     * @param int $userid The user's ID.
     * @param string $courseshortname The course shortname.
     * @return array An array containing the course object if found and enrolled.
     */
    private function get_user_course_from_external_db(int $userid, string $courseshortname): array {
        $sql = "SELECT DISTINCT c.id, c.shortname, c.fullname
                  FROM {course} c
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = :userid
                       AND c.shortname = :courseshortname
                       AND ue.status = 0"; // ENROL_USER_ACTIVE = 0.

        $params = ['userid' => $userid, 'courseshortname' => $courseshortname];
        return $this->db->get_records_sql($sql, $params);
    }

    /**
     * Retrieves all grade items and the user's grades for a specific course from the external DB.
     *
     * @param int $courseid The course ID.
     * @param int $userid The user ID.
     * @return array An array of grade items with user's grade data.
     */
    private function get_user_grades_for_course(int $courseid, int $userid): array {
        $sql = "SELECT gg.id, gi.itemname, gi.itemtype, gi.idnumber, gg.finalgrade AS graderaw
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = :userid
                 WHERE gi.courseid = :courseid AND gg.finalgrade > 0
              ORDER BY gi.sortorder DESC";

        $params = ['userid' => $userid, 'courseid' => $courseid];

        return $this->db->get_records_sql($sql, $params);
    }

    /**
     * Retrieves all enrolled users for a given course shortname from the external database.
     *
     * @param string $courseshortname The shortname of the course.
     * @return array An array of user objects (id, username).
     * @throws \Exception If the course is not found.
     */
    public static function get_users_by_course(string $courseshortname): array {
        $utils35 = new utils35();
        if (!$utils35->validate_connection()) {
            throw new \Exception('Could not establish connection to the external database.');
        }
        $db = $utils35->db;

        $course = $db->get_record('course', ['shortname' => $courseshortname], 'id');
        if (!$course) {
            throw new \Exception("Course with shortname '{$courseshortname}' not found in external DB.");
        }

        $sql = "SELECT u.id, u.username
                  FROM {user} u
                  JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid = :courseid AND ue.status = 0"; // ENROL_USER_ACTIVE = 0.

        return $db->get_records_sql($sql, ['courseid' => $course->id]);
    }

    /**
     * Retrieves all users in a specific group for a given course shortname from the external database.
     *
     * @param string $courseshortname The shortname of the course.
     * @param string $groupname The name of the group.
     * @return array An array of user objects (id, username).
     * @throws \Exception If the course or group is not found.
     */
    public static function get_users_by_group(string $courseshortname, string $groupname): array {
        $utils35 = new utils35();
        if (!$utils35->validate_connection()) {
            throw new \Exception('Could not establish connection to the external database.');
        }
        $db = $utils35->db;

        $course = $db->get_record('course', ['shortname' => $courseshortname], 'id');
        if (!$course) {
            throw new \Exception("Course with shortname '{$courseshortname}' not found in external DB.");
        }

        $sql = "SELECT u.id, u.username
                  FROM {user} u
                  JOIN {groups_members} gm ON gm.userid = u.id
                  JOIN {groups} g ON g.id = gm.groupid
                 WHERE g.courseid = :courseid AND g.name = :groupname";

        return $db->get_records_sql($sql, ['courseid' => $course->id, 'groupname' => $groupname]);
    }

    /**
     * Retrieves all enrolled users for a given course shortname from the LOCAL database.
     *
     * @param string $courseshortname The shortname of the course.
     * @return array An array of user objects (id, username).
     * @throws \moodle_exception If the course is not found.
     */
    public static function get_local_users_by_course(string $courseshortname): array {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $courseshortname], 'id');
        if (!$course) {
            throw new \moodle_exception('invalidcourse', 'error', '', $courseshortname);
        }

        $context = \context_course::instance($course->id);
        $users = get_enrolled_users($context, '', 0, 'u.id, u.username');

        return array_values($users);
    }

    /**
     * Retrieves all users in a specific group for a given course shortname from the LOCAL database.
     *
     * @param string $courseshortname The shortname of the course.
     * @param string $groupname The name of the group.
     * @return array An array of user objects (id, username).
     * @throws \moodle_exception If the course or group is not found.
     */
    public static function get_local_users_by_group(string $courseshortname, string $groupname): array {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $courseshortname], 'id');
        if (!$course) {
            throw new \moodle_exception('invalidcourse', 'error', '', $courseshortname);
        }

        $group = $DB->get_record('groups', ['courseid' => $course->id, 'name' => $groupname], 'id');
        if (!$group) {
            throw new \moodle_exception('invalidgroup', 'error', '', $groupname);
        }

        $sql = "SELECT u.id, u.username
                  FROM {user} u
                  JOIN {groups_members} gm ON gm.userid = u.id
                 WHERE gm.groupid = :groupid";

        $users = $DB->get_records_sql($sql, ['groupid' => $group->id]);

        return array_values($users);
    }
}
