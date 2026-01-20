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

namespace tool_eabcetlbridge\completion;

defined('MOODLE_INTERNAL') || die();

use moodle_database;
use stdClass;
use Exception;

/**
 * Activity mapper for matching activities between Moodle 3.5 and 4.5
 *
 * This class provides methods to map activities based on module type and name.
 *
 * @package    local_restoration
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_mapper {

    /**
     * List of standard Moodle modules that have a 'name' field
     *
     * @return array Array of module names
     */
    public static function get_supported_modules() {
        return [
            'assign',
            'assignment',  // Legacy
            'quiz',
            'forum',
            'resource',
            'page',
            'book',
            'lesson',
            'scorm',
            'choice',
            'feedback',
            'workshop',
            'wiki',
            'glossary',
            'label',
            'url',
            'folder',
            'chat',
            'data',
            'lti',
            'survey',
            'imscp',
            'attendance',
            'eabcattendance',
            'customcert',
            'checklist',
            'game',
            'hotpot',
            'journal',
            'lightboxgallery',
            'questionnaire',
            'zoomeabc',
            'chateabc'
        ];
    }

    /**
     * Get the name of an activity instance
     *
     * @param moodle_database $db Database connection
     * @param string $module_type Type of module (assign, quiz, etc.)
     * @param int $instance_id Instance ID from course_modules
     * @return string|null Activity name or null if not found
     */
    public static function get_activity_name($db, $module_type, $instance_id) {
        $supported = self::get_supported_modules();

        if (!in_array($module_type, $supported)) {
            return null;
        }

        try {
            $table = $module_type;
            $record = $db->get_record($table, ['id' => $instance_id], 'name');
            return $record ? $record->name : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Find a course module in Moodle 4.5 by module type and activity name
     *
     * @param int $courseid Course ID in Moodle 4.5
     * @param string $module_type Module type (assign, quiz, etc.)
     * @param string $activity_name Name of the activity
     * @return stdClass|null Course module object or null if not found
     */
    public static function find_activity_by_name($courseid, $module_type, $activity_name) {
        global $DB;

        $supported = self::get_supported_modules();
        if (!in_array($module_type, $supported)) {
            return null;
        }

        try {
            $sql = "SELECT cm.*
                    FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {" . $module_type . "} act ON act.id = cm.instance
                    WHERE cm.course = :courseid
                      AND m.name = :modulename
                      AND act.name = :activityname
                    LIMIT 1";

            $params = [
                'courseid' => $courseid,
                'modulename' => $module_type,
                'activityname' => $activity_name
            ];

            return $DB->get_record_sql($sql, $params);

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get all completed activities for a user in a course from Moodle 3.5
     *
     * @param moodle_database $db35 Connection to Moodle 3.5
     * @param int $userid User ID in Moodle 3.5
     * @param int $courseid Course ID in Moodle 3.5
     * @return array Array of completion records with activity details
     */
    public static function get_completions_from_35($db35, $userid, $courseid) {
        $completions = [];

        try {
            // Get all completed course modules for the user
            $sql = "SELECT cm.id as cmid,
                           cm.instance,
                           m.name as module_type,
                           cmc.completionstate,
                           cmc.timemodified,
                           cmc.viewed
                    FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module
                    JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id
                    WHERE cm.course = :courseid
                      AND cmc.userid = :userid
                      AND cmc.completionstate > 0
                    ORDER BY cmc.timemodified ASC";

            $params = ['courseid' => $courseid, 'userid' => $userid];
            $records = $db35->get_records_sql($sql, $params);

            foreach ($records as $record) {
                // Get the activity name
                $activity_name = self::get_activity_name($db35, $record->module_type, $record->instance);

                if ($activity_name !== null) {
                    $completions[] = [
                        'cmid35' => $record->cmid,
                        'module_type' => $record->module_type,
                        'activity_name' => $activity_name,
                        'completionstate' => $record->completionstate,
                        'timemodified' => $record->timemodified,
                        'viewed' => $record->viewed
                    ];
                }
            }

        } catch (Exception $e) {
            // Return empty array on error
            debugging('Error getting completions from 3.5: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return $completions;
    }

    /**
     * Check if a module completion already exists in Moodle 4.5
     *
     * @param int $coursemoduleid Course module ID
     * @param int $userid User ID
     * @return stdClass|null Existing completion record or null
     */
    public static function get_existing_completion($coursemoduleid, $userid) {
        global $DB;

        try {
            return $DB->get_record('course_modules_completion', [
                'coursemoduleid' => $coursemoduleid,
                'userid' => $userid
            ]);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Retrieve a course completion record from the database.
     *
     * @param moodle_database $db The database connection object.
     * @param int $userid The user ID.
     * @param int $courseid The course ID.
     * @return stdClass|null The completion record or null if not found.
     */
    public static function get_course_completion($db, $userid, $courseid) {
        try {
            $sql = "SELECT *
                      FROM {course_completions}
                     WHERE course = :courseid
                           AND userid = :userid";

            $record = $db->get_record_sql($sql, [
                'courseid' => $courseid,
                'userid' => $userid
            ]);
            if ($record) {
                return $record;
            }
        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Get the user's enrollment date in a course.
     *
     * @param int $userid The user ID.
     * @param int $courseid The course ID.
     * @return int|null The enrollment timestamp or null if not enrolled.
     */
    public static function get_user_enrollment_date($userid, $courseid) {
        global $DB;

        $sql = "SELECT MIN(ue.timecreated)
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :userid AND e.courseid = :courseid";

        $params = ['userid' => $userid, 'courseid' => $courseid];

        return $DB->get_field_sql($sql, $params);
    }
}
