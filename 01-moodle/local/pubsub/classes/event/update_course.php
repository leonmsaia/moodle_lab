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
 * @package    local_pubsub
 * @copyright  José Salgado jose@e-abclearning.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_pubsub\event;
use moodle_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_eabcattendance instance list viewed event class.
 *
 * @package    mod_eabcattendance
 * @copyright  2018 Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_course extends \core\event\base {
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
        return "Update course";
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $message = "Update course";
        $message .= \html_writer::empty_tag('br');
        if(!empty($this->other["courseid"])){
            $message .= 'courseid ' . $this->other["courseid"];
            $message .= \html_writer::empty_tag('br');
        }

        return $message;
    }

    /**
     * Returns relevant URL.
     *
     * @return moodle_url
     * @throws moodle_exception
     */
    public function get_url() {
        return new moodle_url('/');
    }
}
