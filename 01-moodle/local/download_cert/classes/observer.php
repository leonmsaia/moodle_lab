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
 * Event observer for meta enrolment plugin.
 *
 * @package    enrol_meta
 * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/enrol/meta/locallib.php');

/**
 * Event observer for enrol_meta.
 *
 * @package    enrol_meta
 * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_download_cert_observer extends enrol_meta_handler {

    /**
     * Observer
     * Permite enviar una notificación de inscripción a un curso
     * @param type $event
     */
    public static function unenrole_observer($event)
    {
        global $DB, $CFG;

        try {
            $transaction = $DB->start_delegated_transaction();

            $data = $event->get_data();
            $get_data_cert = $DB->get_record('download_cert_code', array('courseid' => $data['courseid'], 'userid' => $data['relateduserid']));
            error_log(print_r($data['courseid'], true));
            error_log(print_r($data['relateduserid'], true));
            error_log(print_r($get_data_cert, true));
            if (!empty($get_data_cert)) {

                //borro registro de la base de datos
                $DB->delete_records('download_cert_code', array('id' => $get_data_cert->id));

                $get_data_cert->unenrol = 'desmatriculado';
                $get_data_cert->status = 'unenrol';
                $get_data_cert->timeunenrol = time();

                //guardo log en la base de datos
                $event = \local_download_cert\event\enrol_unenrol::create(
                    array(
                        'context' => \context_course::instance($data['courseid']),
                        'other' => array('unenrol' =>  json_encode($get_data_cert))
                    )
                );
                $event->trigger();

                \local_download_cert\download_cert_utils::clear_attemps_course_user($data['relateduserid'], $data['courseid'] );
                
            }

            $transaction->allow_commit();
        } catch (\Exception $e) {
            //error_log(print_r($e, true));
            $transaction->rollback($e);
        }
    }
}
