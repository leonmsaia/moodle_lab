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
 * Web service courses definition
 * @package   mod_eabceabcattendance
 * @author    José Salgado <jose@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_eabcattendance\external;

use context_module;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use mod_eabcattendance_structure;
use stdClass;

defined('MOODLE_INTERNAL') || die;


class add_session extends external_api {
    /**
     * Describes the parameters for add_session.
     *
     * @return external_function_parameters
     */
    public static function add_session_parameters() {
        return new external_function_parameters(
            array(
                'attendanceid' => new external_value(PARAM_INT, 'attendance instance id'),
                'description' => new external_value(PARAM_RAW, 'description', VALUE_DEFAULT, ''),
                'sessiontime' => new external_value(PARAM_INT, 'session start timestamp'),
                'duration' => new external_value(PARAM_INT, 'session duration (seconds)', VALUE_DEFAULT, 0),
                'groupid' => new external_value(PARAM_INT, 'group id', VALUE_DEFAULT, 0),
                'addcalendarevent' => new external_value(PARAM_BOOL, 'add calendar event', VALUE_DEFAULT, true),
            )
        );
    }
    /**
     * Adds session to attendance instance.
     *
     * @param int $attendanceid
     * @param string $description
     * @param int $sessiontime
     * @param int $duration
     * @param int $groupid
     * @param bool $addcalendarevent
     * @return array
     */
    public static function add_session(int $attendanceid, $description, int $sessiontime, int $duration, int $groupid,
                                       bool $addcalendarevent) {
        global $USER, $DB;
        
        $params = self::validate_parameters(self::add_session_parameters(), array(
            'attendanceid' => $attendanceid,
            'description' => $description,
            'sessiontime' => $sessiontime,
            'duration' => $duration,
            'groupid' => $groupid,
            'addcalendarevent' => $addcalendarevent,
        ));
        $cm = get_coursemodule_from_instance('eabcattendance', $params['attendanceid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $attendance = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);
        // Check permissions.
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/eabcattendance:manageeabcattendances', $context);
        // Validate group.
        $groupid = $params['groupid'];
        $groupmode = (int)groups_get_activity_groupmode($cm);
        if ($groupmode === NOGROUPS && $groupid > 0) {
            throw new invalid_parameter_exception('Group id is specified, but group mode is disabled for activity');
        } else if ($groupmode === SEPARATEGROUPS && $groupid === 0) {
            throw new invalid_parameter_exception('Group id is not specified (or 0) in separate groups mode.');
        }
        if ($groupmode === SEPARATEGROUPS || ($groupmode === VISIBLEGROUPS && $groupid > 0)) {
            // Determine valid groups.
            $userid = has_capability('moodle/site:accessallgroups', $context) ? 0 : $USER->id;
            $validgroupids = array_map(function($group) {
                return $group->id;
            }, groups_get_all_groups($course->id, $userid, $cm->groupingid));
            if (!in_array($groupid, $validgroupids)) {
                throw new invalid_parameter_exception('Invalid group id');
            }
        }
        // Get attendance.
        $attendance = new mod_eabcattendance_structure($attendance, $cm, $course, $context);
        // Create session.
        $sess = new stdClass();
        $sess->sessdate = $params['sessiontime'];
        $sess->duration = $params['duration'];
        $sess->descriptionitemid = 0;
        $sess->description = $params['description'];
        $sess->descriptionformat = FORMAT_HTML;
        $sess->direction = $params['direction'];
        $sess->directionformat = FORMAT_HTML;
        $sess->calendarevent = (int) $params['addcalendarevent'];
        $sess->timemodified = time();
        $sess->studentscanmark = 0;
        $sess->autoassignstatus = 0;
        $sess->subnet = '';
        $sess->studentpassword = '';
        $sess->automark = 0;
        $sess->automarkcompleted = 0;
        $sess->absenteereport = get_config('attendance', 'absenteereport_default');
        $sess->includeqrcode = 0;
        $sess->subnet = $attendance->subnet;
        $sess->statusset = 0;
        $sess->groupid = $groupid;
        $sessionid = $attendance->add_session($sess);
        return array('sessionid' => $sessionid);
    }
    /**
     * Describes add_session return values.
     *
     * @return external_multiple_structure
     */
    public static function add_session_returns() {
        return new external_single_structure(array(
            'sessionid' => new external_value(PARAM_INT, 'id of the created session'),
        ));
    }
    
}
