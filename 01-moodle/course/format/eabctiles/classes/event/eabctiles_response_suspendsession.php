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

namespace format_eabctiles\event;
defined('MOODLE_INTERNAL') || die();


class eabctiles_response_suspendsession extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'course';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return "Response ws suspender por sesión";
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $message = "Response ws suspender sesión";
        $message .= \html_writer::empty_tag('br');
        if(!empty($this->other["groupid"])){
            $message .= 'groupid: ' . $this->other["groupid"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["motivo"])){
            $message .= 'Motivo: ' . $this->other["motivo"];
            $message .= \html_writer::empty_tag('br');
        }
        if(!empty($this->other["response"])){
            $message .= 'Response: ' . $this->other["response"];
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
