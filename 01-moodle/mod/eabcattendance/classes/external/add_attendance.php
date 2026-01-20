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

use dml_exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use moodle_exception;
use required_capability_exception;
use restricted_context_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die;


class add_attendance extends external_api {
    
    /**
     * Describes the parameters for add_attendance.
     *
     * @return external_function_parameters
     */
    public static function add_attendance_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'name' => new external_value(PARAM_TEXT, 'attendance name'),
                'intro' => new external_value(PARAM_RAW, 'attendance description', VALUE_DEFAULT, ''),
                'groupmode' => new external_value(PARAM_INT,
                    'group mode (0 - no groups, 1 - separate groups, 2 - visible groups)', VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Adds attendance instance to course.
     *
     * @param int $courseid
     * @param string $name
     * @param string $intro
     * @param int $groupmode
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     * @throws restricted_context_exception
     * @throws invalid_parameter_exception
     */
    public static function add_attendance(int $courseid, $name, $intro, int $groupmode) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/modlib.php');
        $params = self::validate_parameters(self::add_attendance_parameters(), array(
            'courseid' => $courseid,
            'name' => $name,
            'intro' => $intro,
            'groupmode' => $groupmode,
        ));
        // Get course.
        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);
        // Verify permissions.
        list($module, $context) = can_add_moduleinfo($course, 'eabcattendance', 0);
        self::validate_context($context);
        require_capability('mod/eabcattendance:addinstance', $context);
        // Verify group mode.
        if (!in_array($params['groupmode'], array(NOGROUPS, SEPARATEGROUPS, VISIBLEGROUPS))) {
            throw new invalid_parameter_exception('Group mode is invalid.');
        }
        // Populate modinfo object.
        $moduleinfo = new stdClass();
        $moduleinfo->modulename = 'eabcattendance';
        $moduleinfo->module = $module->id;
        $moduleinfo->name = $params['name'];
        $moduleinfo->intro = $params['intro'];
        $moduleinfo->introformat = FORMAT_HTML;
        $moduleinfo->section = 0;
        $moduleinfo->visible = 1;
        $moduleinfo->visibleoncoursepage = 1;
        $moduleinfo->cmidnumber = '';
        $moduleinfo->groupmode = $params['groupmode'];
        $moduleinfo->groupingid = 0;
        // Add the module to the course.
        $moduleinfo = add_moduleinfo($moduleinfo, $course);
        return array('eabcattendanceid' => $moduleinfo->instance);
    }

    /**
     * Describes add_attendance return values.
     *
     * @return external_single_structure
     */
    public static function add_attendance_returns() {
        return new external_single_structure(array(
            'eabcattendanceid' => new external_value(PARAM_INT, 'instance id of the created attendance'),
        ));
    }
    
}
