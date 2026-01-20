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

namespace local_resumencursos\external;

use dml_exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use moodle_exception;
use local_resumencursos\utils\summary_utils;

defined('MOODLE_INTERNAL') || die();

class get_data_sumary extends external_api
{
    /**
     * @return external_function_parameters
     */
    public static function get_data_sumary_parameters()
    {
        /**
         * parametros que acepta el ws
         */
        return new external_function_parameters(
            [
                'courseid' => new external_value(
                    PARAM_INT
                ),
            ]
        );
    }

    /**
     * @param int $courseid
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_data_sumary($courseid = 0) 
    {
        global $DB, $CFG, $USER, $SESSION;
        require_once($CFG->dirroot . '/lib/enrollib.php');
        require_once($CFG->dirroot . '/mod/eabcattendance/renderer.php');
        $eabctiles_closegroup = "";
        $fecha_finalizacion = "";
        $fecha_caducidad = "";
        $return = array();
        $groups_members = false;
        $actividad = '';
        $courseids = array();
        $where = "";
        $wherefiltercursos = "";
        $wherefilterdate = "";
        $wherefilterdatesesions = "";
        $filterstatus = '';
        $wherefilterdatecourses = '';
        //filtros de cursos
        if($SESSION->filtersummary->curso != ''){
            $wherefiltercursos .= ' AND LOWER(course.fullname) LIKE "%'.strtolower($SESSION->filtersummary->curso).'%"';
        }
        /* if (!empty($SESSION->filtersummary->datetocourse) && ($SESSION->filtersummary->datetocourse != 0)) {
            $wherefiltercursos .= ' AND course.startdate >= "' . $SESSION->filtersummary->datetocourse . '"';
        }
        if (!empty($SESSION->filtersummary->datefromcourse) && ($SESSION->filtersummary->datefromcourse != 0)) {
            $wherefiltercursos .= ' AND course.enddate <= "' . $SESSION->filtersummary->datefromcourse . '"';
        } */
        if (!empty($SESSION->filtersummary->modalidadopresencial) || !empty($SESSION->filtersummary->modalidadsemipresencial) || !empty($SESSION->filtersummary->modalidaddistancia)) {
            $modaliidadstr = "";
            if (!empty($SESSION->filtersummary->modalidadopresencial)) {
                $modaliidadstr .= '"100000000",';
            } if (!empty($SESSION->filtersummary->modalidadsemipresencial)) {
                $modaliidadstr .= '"100000001",';
            } if (!empty($SESSION->filtersummary->modalidaddistancia)) {
                $modaliidadstr .= '"100000002",';
            }
            $modaliidadstr .= '"..."';
            $wherefiltercursos .= ' AND cb.modalidad IN (' . $modaliidadstr . ')';
        }

        //filtros de sesiones
         if (!empty($SESSION->filtersummary->estadoabierto) || !empty($SESSION->filtersummary->estadocerrado)) {
            $estadostr = "";
            if (!empty($SESSION->filtersummary->estadoabierto)) {
                $estadostr .= '"100000001",';
            } if (!empty($SESSION->filtersummary->estadocerrado)) {
                $estadostr .= '"100000003",';
            }
            $estadostr .= '"..."';
            $wherefilterdatesesions .= ' AND sb.estado IN (' . $estadostr . ')';
        } 
        if (!empty($SESSION->filtersummary->datetosesions) && ($SESSION->filtersummary->datetosesions != 0)) {
            $wherefilterdatesesions .= ' AND s.sessdate >= "' . $SESSION->filtersummary->datetosesions . '"';
        }
        if (!empty($SESSION->filtersummary->datefromsesions) && ($SESSION->filtersummary->datefromsesions != 0)) {
            $wherefilterdatesesions .= ' AND s.sessdate <= "' . $SESSION->filtersummary->datefromsesions . '"';
        }

        

        /*
        if($filterstatus != ''){
            $wherefilterstatus .= ' AND LOWER(ats.description) LIKE "%'.strtolower($filterstatus).'%"';
        } */
        $params = self::validate_parameters(self::get_data_sumary_parameters(),
            array(
                'courseid' => $courseid,
            )
        );
        
        try {
            if ($courseid != -1) {
                //despliego un solo curso
                $courseids[] = $courseid;
            } else {
                //despliego la lista de cursos
//                $usrenrolids = enrol_get_all_users_courses($USER->id, true,  NULL, $sort = 'visible DESC');
                $sqlenrol = 'SELECT
                course.*
                FROM {course} AS course 
                JOIN {enrol} AS en ON en.courseid = course.id
                JOIN {user_enrolments} AS ue ON ue.enrolid = en.id
                LEFT JOIN {curso_back} as cb ON cb.id_curso_moodle = course.id 
                WHERE course.visible=1 AND ue.userid = ' . $USER->id.$wherefiltercursos."
                ORDER BY visible DESC";
                $usrenrolids = $DB->get_records_sql($sqlenrol);
                foreach ($usrenrolids as $enrol) {
                    $courseids[] = $enrol->id;
                }
                
            }
            
            $calificaciones_finales = summary_utils::get_grades($USER->id);

            foreach ($courseids as $id) {
                
                $course = $DB->get_record('course', array('id' => $id));
                
                $fecha_finalizacion = "";
                $fecha_caducidad = summary_utils::final_date($course);
                $calificacion = summary_utils::qualification($calificaciones_finales, $id);
                $sqlgroups = "select s.id as sessionid, g.id,s.sessdate, s.duration 
                from {eabcattendance_sessions} AS s 
                JOIN {groups} as g on g.id = s.groupid
                LEFT JOIN {eabcattendance_log} AS l ON (l.sessionid = s.id AND l.studentid = ".$USER->id.")
                LEFT JOIN {eabcattendance_statuses} as ats ON l.statusid = ats.id 
                LEFT JOIN {sesion_back} as sb ON sb.id_sesion_moodle = s.id 
                where g.courseid = " . $id.$wherefilterdatesesions;
                $grupos = $DB->get_records_sql($sqlgroups);

                                        error_log(print_r($sqlgroups, true));
                foreach ($grupos as $g) {
                    $sessiondate = !empty($g->sessdate) ? date('d/m/yy', $g->sessdate) : '';
                    
                    $groups_members = $DB->get_record('groups_members', array('groupid' => $g->id, 'userid' => $USER->id));
                    
                    if (!empty($groups_members)) {
                        
                        $roles = summary_utils::get_roles($USER->id, $id);
                        if ((count($roles) == 1) && ($roles[key($roles)]->roleid == 5)) {
                            $eabctiles_closegroup = $DB->get_record('format_eabctiles_closegroup', array('groupid' => $groups_members->groupid));
                            $actividad = summary_utils::get_activity($eabctiles_closegroup);
                            $status = summary_utils::get_status_session($USER->id, $g->sessionid, $filterstatus);
                            $statusdata = !empty($status->description) ? $status->description : '';
                            $time = eabcattendance_construct_session_time($g->sessdate, $g->duration);
                            
                            $return[] = summary_utils::get_data_return($course, $calificacion, $fecha_finalizacion, $actividad, $sessiondate, $statusdata, $time, $fecha_caducidad, $g);
                        }
                        $groups_members = true;
                    } else {
                        $groups_members = false;
                    }
                    
                }
                if($groups_members == false){
                    $roles = summary_utils::get_roles($USER->id, $id);
                    if ((count($roles) == 1) && ($roles[key($roles)]->roleid == 5)) {
                        $return[] = summary_utils::get_data_return($course, $calificacion, $fecha_finalizacion, $actividad, "", "", "", $fecha_caducidad);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new moodle_exception('errormsg', 'local_resumencursos', '', $e->getMessage());
        }

        return $return;
    }

    /**
     * @return external_multiple_structure
     */
    public static function get_data_sumary_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    //'nombrecurso' => new external_value(PARAM_RAW),
                    'nombrecurso' => new external_single_structure(
                            array(
                                'url' => new external_value(PARAM_RAW, 'url'),
                                'name' => new external_value(PARAM_RAW, 'name course'),
                                'fullname' => new external_value(PARAM_RAW, 'name course'),
                            )
                        ),
                    'courseid' => new external_value(PARAM_RAW),
                    'modalidad' => new external_value(PARAM_RAW),
                    'calificacion' => new external_value(PARAM_RAW),
                    'finalizacion' => new external_value(PARAM_RAW),
                    'duracion' => new external_value(PARAM_RAW),
                    'direccion' => new external_value(PARAM_RAW),
                    'actividad' => new external_value(PARAM_RAW),
                    'caducidad' => new external_value(PARAM_RAW),
                    'valoracion' => new external_value(PARAM_RAW),
                    'disponibilidad' => new external_value(PARAM_RAW),
                    'sessdate' => new external_value(PARAM_RAW),
                    'status' => new external_value(PARAM_RAW),
                    'time' => new external_value(PARAM_RAW),
                    'nombreadherente' => new external_value(PARAM_RAW),
                ]
            )
        );
    }
}
