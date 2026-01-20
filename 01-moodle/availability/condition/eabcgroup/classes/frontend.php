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
 * Front-end class.
 *
 * @package availability_eabcgroup
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_eabcgroup;

defined('MOODLE_INTERNAL') || die();

/**
 * Front-end class.
 *
 * @package availability_eabcgroup
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /** @var array Array of eabcgroup info for course */
    protected $alleabcgroups;
    /** @var int Course id that $alleabcgroups is for */
    protected $alleabcgroupscourseid;

    protected function get_javascript_strings() {
        return array('anyeabcgroup');
    }

    protected function get_javascript_init_params($course, \cm_info $cm = null,
            \section_info $section = null) {
        // Get all eabcgroups for course.
        $eabcgroups = $this->get_all_eabcgroups($course->id);

        // Change to JS array format and return.
        $jsarray = array();
        $context = \context_course::instance($course->id);
//        foreach ($eabcgroups as $rec) {
//            $jsarray[] = (object)array('id' => $rec->id, 'name' =>
//                    format_string($rec->name, true, array('context' => $context)));
//        }
        return array($jsarray);
    }

    /**
     * Gets all eabcgroups for the given course.
     *
     * @param int $courseid Course id
     * @return array Array of all the eabcgroup objects
     */
    protected function get_all_eabcgroups($courseid) {
        global $CFG;
        require_once($CFG->libdir . '/grouplib.php');

        if ($courseid != $this->alleabcgroupscourseid) {
            $this->alleabcgroups = groups_get_all_groups($courseid, 0, 0, 'g.id, g.name');
            $this->alleabcgroupscourseid = $courseid;
        }
        return $this->alleabcgroups;
    }

    protected function allow_add($course, \cm_info $cm = null,
            \section_info $section = null) {
        global $CFG;

        // Only show this option if there are some eabcgroups.
        return count($this->get_all_eabcgroups($course->id)) > 0;
    }
}
