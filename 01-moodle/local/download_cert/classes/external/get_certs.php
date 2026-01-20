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
 * External functions
 *
 * @package   local_report_completion
 * @copyright 2018 Osvaldo Arriola  <osvaldo@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_download_cert\external;

use dml_exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use moodle_exception;
use Exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class get_certs extends external_api
{
    /**
     * @return external_function_parameters
     */
    public static function get_certs_parameters()
    {
        /**
         * parametros que acepta el ws
         */
        return new external_function_parameters(
            []
        );
    }

    /**
     * @param int $courseid
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_certs()
    {
        global $DB, $CFG, $USER;
        $data = array();
        $ilerningbool = false;
        $presencialbool = false;
        $encuestabool = false;
        $elearning = array();
        $presenciales = array();
        try {
            /** @var external_api $api */
        $api = new class extends external_api {};
        $params = $api::validate_parameters(
                self::get_certs_parameters(),
                array()
            );
            require_once $CFG->dirroot . '/completion/classes/privacy/provider.php';
            require_once $CFG->dirroot . '/lib/completionlib.php';

            require_once($CFG->dirroot . '/lib/enrollib.php');
            //$courses = enrol_get_all_users_courses($USER->id, true,  NULL, $sort = 'visible DESC');
            //consultos los cursos creados en el que el participante esta matriculado pero solo los creados desde back
            $sqlenrol = 'SELECT
                    course.*
                    FROM {course} AS course 
                    JOIN {enrol} AS en ON en.courseid = course.id
                    JOIN {user_enrolments} AS ue ON ue.enrolid = en.id
                    JOIN {curso_back} AS cb ON cb.id_curso_moodle = course.id 
                    WHERE course.visible=1 AND ue.userid = ' . $USER->id . "
                    ORDER BY visible DESC";

            $courses = $DB->get_records_sql($sqlenrol);
            if (!empty($courses)) {

                foreach ($courses as $course) {
                    /*
                    se considera completado si aprobo el curso nota de usuario mayor a 75 en base a la nota del curso
                    y si su asistencia es 100%(configurable)
                    */
                    $completioncert = \local_download_cert\download_cert_utils::completion_cert($course->id);
                    $completionattendance = \local_download_cert\download_cert_utils::completion_attendance($course->id);
                    //si completo el curso con nota mayor a la nota de aprobacion
                    if ($completioncert == true) {

                        //consulto si es curso ilernisn
                        $get_ilerning_enrol = $DB->get_record('inscripcion_elearning_back', array('id_user_moodle' => $USER->id, 'id_curso_moodle' => $course->id));

                        //creo arreglo para cursos ilerning
                        //si tiene registro de cursos ilerning registrado lleno el arreglo
                        if (!empty($get_ilerning_enrol)) {
                            $modinfo = get_fast_modinfo($course);
                            $configilerning = get_config('local_download_cert', 'completion_mod_ilerning');
                            $configilerning = (!empty($configilerning)) ? $configilerning : 'feedback';
                            $glossarymods = $modinfo->get_instances_of($configilerning);
                            foreach ($glossarymods as $glossarymod) {
                                if ($glossarymod->deletioninprogress == 0 && $glossarymod->visible == 1) {
                                    $activitycompletion = \core_completion\privacy\provider::get_activity_completion_info($USER, $course, $glossarymod);
                                    if ($activitycompletion->completionstate == 1) {
                                        $encuestabool = true;
                                        $elearning[] = array('id' => $course->id, 'fullname' => $course->fullname);
                                    }
                                }
                                
                            }
                        } else {
                            //si no es ilerning es presencial
                            //creo arreglo para cursos presenciales
                            $completion_type = \local_download_cert\download_cert_utils::completion_course_type($course->id, 'presencial');

                            if ($completion_type == true && $completionattendance == true) {
                                $presencialbool = true;
                                $presenciales[] = array('id' => $course->id, 'fullname' => $course->fullname);
                            }
                        }
                    }
                }
            }
            $data = array(
                'elearning' => array(
                    'elearningbool' => $encuestabool,
                    'elearning' => $elearning
                ),

                'presencial' => array(
                    'presencialbool' => $presencialbool,
                    'presenciales' => $presenciales
                )
            );
        } catch (Exception $e) {
            throw new moodle_exception('errormsg', 'local_download_cert', '', $e->getMessage());
        }


        return $data;
    }

    /**
     * @return external_multiple_structure
     */
    public static function get_certs_returns()
    {
        return new external_single_structure(
            [
                'elearning' =>  new external_single_structure(
                    array(
                        'elearningbool' => new external_value(PARAM_RAW, 'elearningbool', VALUE_OPTIONAL, ''),
                        'elearning' =>  new external_multiple_structure(
                            new external_single_structure(
                                [
                                    'id' => new external_value(PARAM_RAW, 'id'),
                                    'fullname' => new external_value(PARAM_RAW, 'name course'),
                                ],
                                VALUE_OPTIONAL,
                                array()
                            )
                        )
                    )
                ),
                'presencial' => new external_single_structure(
                    array(
                        'presencialbool' => new external_value(PARAM_RAW, 'name', VALUE_OPTIONAL, ''),
                        'presenciales' =>  new external_multiple_structure(
                            new external_single_structure(
                                [
                                    'id' => new external_value(PARAM_RAW, 'id'),
                                    'fullname' => new external_value(PARAM_RAW, 'name course'),
                                ],
                                VALUE_OPTIONAL,
                                array()
                            )
                        )
                    )
                ),

            ]
        );
    }
}
