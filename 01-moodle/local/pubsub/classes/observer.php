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
 * @package    local_pubsub_observer
 */
use core\event\user_graded;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers 
 * @package    local_sendgrade
 * @copyright  2018 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_pubsub_observer {
    /**
	 * Observer
	 * Permite enviar una notificación de inscripción a un curso
	 * @param type $event
	 */
	public static function unenrole_observer($event)
	{
		try {
			\local_pubsub\metodos_comunes::clear_user_course_data($event->relateduserid, $event->courseid);
		} catch (\Exception $e) {
			error_log(print_r($e, true));
		}
	}
    
}