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

/**
 * Condition main class.
 *
 * @package availability_eabcgroup
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_eabcgroup;

defined('MOODLE_INTERNAL') || die();

/**
 * Condition main class.
 *
 * @package availability_eabcgroup
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var array Array from eabcgroup id => name */
    protected static $eabcgroupnames = array();

    /** @var int ID of eabcgroup that this condition requires, or 0 = any eabcgroup */
    protected $eabcgroupid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Get eabcgroup id.
        if (!property_exists($structure, 'id')) {
            $this->eabcgroupid = 0;
        } else if (is_int($structure->id)) {
            $this->eabcgroupid = $structure->id;
        } else {
            throw new \coding_exception('Invalid ->id for eabcgroup condition');
        }
    }

    public function save() {
        $result = (object)array('type' => 'eabcgroup');
        if ($this->eabcgroupid) {
            $result->id = $this->eabcgroupid;
        }
        return $result;
    }

    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $DB;
        $course = $info->get_course();
        $context = \context_course::instance($course->id);
        $allow = true;
        if (!has_capability('moodle/site:accessallgroups', $context, $userid)) {
            // Get all eabcgroups the user belongs to.
            $eabcgroups = $info->get_modinfo()->get_groups();
            foreach($eabcgroups as $eabcgroup){
                $cloasegroups = $DB->get_record('format_eabctiles_closegroup', array('groupid' => $eabcgroup));
                if(!empty($cloasegroups)){
                    if ($cloasegroups->status == 0) {
                        $allow = true;
                        break;
                    } else {
                        $allow = false;
                    }
                } else {
                    $allow = true;
                    break;
                }
            }
        }
        return $allow;
    }

    public function get_description($full, $not, \core_availability\info $info) {
        global $DB;

        if ($this->eabcgroupid) {
            // Need to get the name for the eabcgroup. Unfortunately this requires
            // a database query. To save queries, get all eabcgroups for course at
            // once in a static cache.
            $course = $info->get_course();
            if (!array_key_exists($this->eabcgroupid, self::$eabcgroupnames)) {
                $courseeabcgroups = $DB->get_records(
                        'groups', array('courseid' => $course->id), '', 'id, name');
                foreach ($courseeabcgroups as $rec) {
                    self::$eabcgroupnames[$rec->id] = $rec->name;
                }
            }

            // If it still doesn't exist, it must have been misplaced.
            if (!array_key_exists($this->eabcgroupid, self::$eabcgroupnames)) {
                $name = get_string('missing', 'availability_eabcgroup');
            } else {
                $context = \context_course::instance($course->id);
                $name = format_string(self::$eabcgroupnames[$this->eabcgroupid], true,
                        array('context' => $context));
            }
        } else {
            return get_string($not ? 'requires_notanyeabcgroup' : 'requires_anyeabcgroup',
                    'availability_eabcgroup');
        }

        return get_string($not ? 'requires_noteabcgroup' : 'requires_eabcgroup',
                'availability_eabcgroup', $name);
    }

    protected function get_debug_string() {
        return $this->eabcgroupid ? '#' . $this->eabcgroupid : 'any';
    }

    /**
     * Include this condition only if we are including eabcgroups in restore, or
     * if it's a generic 'same activity' one.
     *
     * @param int $restoreid The restore Id.
     * @param int $courseid The ID of the course.
     * @param base_logger $logger The logger being used.
     * @param string $name Name of item being restored.
     * @param base_task $task The task being performed.
     *
     * @return Integer eabcgroupid
     */
    public function include_after_restore($restoreid, $courseid, \base_logger $logger,
            $name, \base_task $task) {
        return !$this->eabcgroupid || $task->get_setting_value('eabcgroups');
    }

    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) {
        global $DB;
        if (!$this->eabcgroupid) {
            return false;
        }
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'eabcgroup', $this->eabcgroupid);
        if (!$rec || !$rec->newitemid) {
            // If we are on the same course (e.g. duplicate) then we can just
            // use the existing one.
            if ($DB->record_exists('groups',
                    array('id' => $this->eabcgroupid, 'courseid' => $courseid))) {
                return false;
            }
            // Otherwise it's a warning.
            $this->eabcgroupid = -1;
            $logger->process('Restored item (' . $name .
                    ') has availability condition on eabcgroup that was not restored',
                    \backup::LOG_WARNING);
        } else {
            $this->eabcgroupid = (int)$rec->newitemid;
        }
        return true;
    }

    public function update_dependency_id($table, $oldid, $newid) {
        if ($table === 'groups' && (int)$this->eabcgroupid === (int)$oldid) {
            $this->eabcgroupid = $newid;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Wipes the static cache used to store eabcgrouping names.
     */
    public static function wipe_static_cache() {
        self::$eabcgroupnames = array();
    }

    public function is_applied_to_user_lists() {
        // eabcgroup conditions are assumed to be 'permanent', so they affect the
        // display of user lists for activities.
        return true;
    }

    public function filter_user_list(array $users, $not, \core_availability\info $info,
            \core_availability\capability_checker $checker) {
        global $CFG, $DB;

        // If the array is empty already, just return it.
        if (!$users) {
            return $users;
        }

        require_once($CFG->libdir . '/grouplib.php');
        $course = $info->get_course();

        // List users for this course who match the condition.
        if ($this->eabcgroupid) {
            $eabcgroupusers = groups_get_members($this->eabcgroupid, 'u.id', 'u.id ASC');
        } else {
            $eabcgroupusers = $DB->get_records_sql("
                    SELECT DISTINCT gm.userid
                      FROM {groups} g
                      JOIN {groups_members} gm ON gm.groupid = g.id
                     WHERE g.courseid = ?", array($course->id));
        }

        // List users who have access all eabcgroups.
        $aagusers = $checker->get_users_by_capability('moodle/site:accessallgroups');

        // Filter the user list.
        $result = array();
        foreach ($users as $id => $user) {
            // Always include users with access all eabcgroups.
            if (array_key_exists($id, $aagusers)) {
                $result[$id] = $user;
                continue;
            }
            // Other users are included or not based on eabcgroup membership.
            $allow = array_key_exists($id, $eabcgroupusers);
            if ($not) {
                $allow = !$allow;
            }
            if ($allow) {
                $result[$id] = $user;
            }
        }
        return $result;
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $eabcgroupid Required eabcgroup id (0 = any eabcgroup)
     * @return stdClass Object representing condition
     */
    public static function get_json($eabcgroupid = 0) {
        $result = (object)array('type' => 'eabcgroup');
        // Id is only included if set.
        if ($eabcgroupid) {
            $result->id = (int)$eabcgroupid;
        }
        return $result;
    }

    public function get_user_list_sql($not, \core_availability\info $info, $onlyactive) {
        global $DB;

        // Get enrolled users with access all eabcgroups. These always are allowed.
        list($aagsql, $aagparams) = get_enrolled_sql(
                $info->get_context(), 'moodle/site:accessallgroups', 0, $onlyactive);

        // Get all enrolled users.
        list ($enrolsql, $enrolparams) =
                get_enrolled_sql($info->get_context(), '', 0, $onlyactive);

        // Condition for specified or any eabcgroup.
        $matchparams = array();
        if ($this->eabcgroupid) {
            $matchsql = "SELECT 1
                           FROM {groups_members} gm
                          WHERE gm.userid = userids.id
                                AND gm.eabcgroupid = " .
                    self::unique_sql_parameter($matchparams, $this->eabcgroupid);
        } else {
            $matchsql = "SELECT 1
                           FROM {groups_members} gm
                           JOIN {groups} g ON g.id = gm.eabcgroupid
                          WHERE gm.userid = userids.id
                                AND g.courseid = " .
                    self::unique_sql_parameter($matchparams, $info->get_course()->id);
        }

        // Overall query combines all this.
        $condition = $not ? 'NOT' : '';
        $sql = "SELECT userids.id
                  FROM ($enrolsql) userids
                 WHERE (userids.id IN ($aagsql)) OR $condition EXISTS ($matchsql)";
        return array($sql, array_merge($enrolparams, $aagparams, $matchparams));
    }
}
