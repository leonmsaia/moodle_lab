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
 * The local_pubsub instance list viewed event.
 *
 * @package    local_cron
 * @copyright  José Salgado jose@e-abclearning.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_cron\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The mod_eabcattendance instance list viewed event class.
 *
 * @package    mod_eabcattendance
 * @copyright  2018 Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_cron_clear_log extends \core\event\base {
    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('clear_local_cron', 'local_cron');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $message = get_string('clear_local_cron', 'local_cron');
        $message .= \html_writer::empty_tag('br');
        if(!empty($this->other["status"])){
            $message .= 'status: ' . $this->other["status"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["userid"])){
            $message .= 'userid: ' . $this->other["userid"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["courseid"])){
            $message .= 'courseid: ' . $this->other["courseid"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["timemodified"])){
            $message .= 'timemodified: ' . $this->other["timemodified"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["gradeuser"])){
            $message .= 'gradeuser: ' . $this->other["gradeuser"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["gradeapprovecourse"])){
            $message .= 'gradeapprovecourse: ' . $this->other["gradeapprovecourse"];
            $message .= \html_writer::empty_tag('br');
        }
        return $message;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/');
    }
}
