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
 * This file contains an event for when an eabcattendance is taken.
 *
 * @package    mod_eabcattendance
 * @copyright  2014 onwards Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_eabcattendance\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event for when an eabcattendance is taken.
 *
 * @property-read array $other {
 *      Extra information about event properties.
 *
 *      string mode Mode of the report viewed.
 * }
 * @package    mod_eabcattendance
 * @since      Moodle 2.7
 * @copyright  2013 onwards Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eabcattendance_taken_by_student_details extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'course';
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $message = "";
        $message .= \html_writer::empty_tag('br');
        if(!empty($this->other["statusset"])){
            $message .= 'statusset: ' . $this->other["statusset"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["remarks"])){
            $message .= 'remarks: ' . $this->other["remarks"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["sessionid"])){
            $message .= 'sessionid: ' . $this->other["sessionid"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["timetaken"])){
            $message .= 'timetaken: ' . $this->other["timetaken"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["takenby"])){
            $message .= 'takenby: ' . $this->other["takenby"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["statusid"])){
            $message .= 'statusid: ' . $this->other["statusid"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["grouptype"])){
            $message .= 'grouptype: ' . $this->other["grouptype"];
            $message .= \html_writer::empty_tag('br');
        }

        return $message;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventtakenbystudent', 'mod_eabcattendance');
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/take.php', array('id' => $this->contextinstanceid,
                                                                 'sessionid' => $this->other['sessionid'],
                                                                 'grouptype' => $this->other['grouptype']));
    }

    /**
     * Replace add_to_log() statement.
     *
     * @return array of parameters to be passed to legacy add_to_log() function.
     */
    
}
