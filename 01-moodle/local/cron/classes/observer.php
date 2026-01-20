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
 * local cron observer
 *
 * @package    local_cron
 * @copyright  2019 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Osvaldo Arriola <osvaldo@e-abclearning.com>
 */

namespace local_cron;

use core\event\user_graded;

defined('MOODLE_INTERNAL') || die();

include_once($CFG->dirroot . '/local/cron/lib.php');

/**
 * Observer definition
 *
 * @package    local_cron
 * @copyright  2019 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Osvaldo Arriola <osvaldo@e-abclearning.com>
 */
class observer
{
	/**
	 * Observer
	 * Permite enviar una notificación de inscripción a un curso
	 * @param type $event
	 */
	public static function unenrole_observer($event)
	{
		try {
			\local_cron\utils::clear_user_course_data($event->relateduserid, $event->courseid);
		} catch (\Exception $e) {
			error_log(print_r($e, true));
		}
	}
	/**
	 * @param user_graded $event
	 * @throws coding_exception
	 * @throws dml_exception
	 */
	public static function user_graded(user_graded $event)
    {
        // todo: se desactivo porque generaba un error, ver http://agile.e-abclearning.com/issues/1817 para detalles
        return;
		/** @var \moodle_database $DB */
		global $DB;
		$grade = $event->get_grade();
		$itemtype = $grade->grade_item->itemtype;
		if ($itemtype == 'course') {
			$config = get_config('local_cron');
			$courseid = $grade->grade_item->courseid;
			$userid = $grade->userid;
			$gradepass = (float)$grade->grade_item->gradepass;
			$finalgrade = (float)$grade->finalgrade;
			$days = (int)$config->days;

			if (empty($days)) {
				$days = 30;
			}
			$today = time();

			$user = $DB->get_record('user', ['id' => $userid]);
			$course = get_course($courseid);

			$ws_log = $DB->get_record('mutual_log_local_cron', array('userid' => $userid, 'courseid' => $courseid));

			$enrolled_date = get_enrol_date($courseid, $userid);

			$days_passed = \local_cron\utils::interval($enrolled_date->dateroltime, $today);

			$aprobado = $finalgrade >= $gradepass && $days_passed <= $days + 1;

			if (!$ws_log && $user->idnumber && $aprobado) {
				$get_xml_body = get_xml_body($user, $course, 1, 0, $today);
				$encrypt_xml_body = encrypt_base64($get_xml_body);
				// enviar request y capturar respuesta como un objeto con codigo y mensaje
				$get_soap_request = get_soap_request($encrypt_xml_body);
				if ($get_soap_request->soapenvBody->respElearningCapacitacionFinalizarExpResp->return->respuesta->codigo == "0") {
					save_log(
						"Aprobado",
						$course->id,
						$user->id,
						$today,
						(floatval($finalgrade)),
						floatval($gradepass)
					);
				}
			}
		}
	}
}
