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
 * Web Services for Eabcattendance plugin.
 *
 * @package    mod_eabcattendance
 * @copyright  2015 Caio Bressan Doneda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/structure.php');
require_once(dirname(__FILE__).'/../../../lib/sessionlib.php');
require_once(dirname(__FILE__).'/../../../lib/datalib.php');

/**
 * Class eabcattendance_handler
 * @copyright  2015 Caio Bressan Doneda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eabcattendance_handler {

    public static function get_dates_courses_sessions($enrol_courses, $courseid, $start, $end) {
        
        global $DB, $USER;
        $records = [];
        $all_groups = [];  
        $all_short_name =[];
        
        foreach($enrol_courses as $enrol_course){            
            $context = context_course::instance($enrol_course->id);
            if (has_capability('local/eabccalendar:view', $context, $USER->id)) {
                if ($courseid == null){
                    $groups = groups_get_all_groups($enrol_course->id,$USER->id); 
                }else{
                    $porciones = explode(",", $courseid);
                    $groups = (in_array($enrol_course->id, $porciones)) ? groups_get_all_groups($enrol_course->id,$USER->id) : '';
                }
                foreach($groups as $group){                   
                    array_push($all_groups, $group->id);                    
                } 
            }
            $all_short_name[$enrol_course->id] =  $enrol_course->shortname;        
        }
        $str = implode (",", $all_groups);
        if(!$str){ $str = 0; }
        $items = [];
        $sql = "SELECT * FROM {eabcattendance_sessions} WHERE groupid in ($str) AND sessdate between $start AND $end AND guid IS NOT NULL";
        $params = array();

        $results = $DB->get_records_sql($sql, $params);

        foreach($results as $result){             
            $estatus = '';              
            $cerrado    = $DB->get_record("format_eabctiles_closegroup", array("groupid" => $result->groupid));
            $suspendido = $DB->get_record("format_eabctiles_suspendgrou", array("groupid" => $result->groupid));
            $sesion_back = $DB->get_record("sesion_back", array("id_sesion_moodle" => $result->id));

            if($cerrado){
                $estatus = 'Finalizado';
            }elseif($suspendido){
                $estatus = 'Cancelado';
            }else{
                $estatus = 'Activo';
            }
            $curso = $DB->get_record('eabcattendance', array('id'=>$result->eabcattendanceid));

            $horas = floor($result->duration / 3600);
            $minutos = floor(($result->duration - ($horas * 3600)) / 60);            
            $horas = ($horas !=0) ? $horas. 'H ' : '';
            $minutos = ($minutos !=0) ? $minutos. 'm' : '';
            
            $desde =  date('H:i', $result->sessdate); 
            $date   = date('Y-m-d H:i', $result->sessdate);                      
            $items['id']         = $result->id;
            $items['sesiondate'] = $date;
            $items['duracion']  = $horas . $minutos;
            $items['desde']     = $desde;
            $items['nombre']    = $all_short_name[$curso->course];
            $items['direccion'] = $sesion_back->direccion;
            $items['courseid']  = $curso->course;
            $items['estatus']   = $estatus;
            
            $focalizacion = $DB->get_record('focalizacion',array('sesionid'=>$sesion_back->id_sesion_moodle, 'instructorid'=> $USER->id));
            if ($focalizacion){
                $color = ($focalizacion->email==1) ? '#32720A' : '#0F77D1';
            }else{
                $color = '#0F77D1';
            }
            $items['color']   = $color;

            array_push($records,$items); 
        }
        
        return $records;
    }

    /**
     * For this user, this method searches in all the courses that this user has permission to take eabcattendance,
     * looking for today sessions and returns the courses with the sessions.
     * @param int $userid
     * @return array
     */
    public static function get_dates_sessions($userid) {        

        $usercourses = enrol_get_users_courses($userid);
        $eabcattendanceinstance = get_all_instances_in_courses('eabcattendance', $usercourses);

        $coursessessions = array();

        foreach ($eabcattendanceinstance as $eabcattendance) {
            $context = context_course::instance($eabcattendance->course);
            if (has_capability('mod/eabcattendance:takeeabcattendances', $context, $userid)) {
                $course = $usercourses[$eabcattendance->course];
                $course->eabcattendance_instance = array();

                $att = new stdClass();
                $att->id = $eabcattendance->id;
                $att->course = $eabcattendance->course;
                $att->name = $eabcattendance->name;
                $att->grade = $eabcattendance->grade;

                $cm = new stdClass();
                $cm->id = $eabcattendance->coursemodule;

                $att = new mod_eabcattendance_structure($att, $cm, $course, $context);
                $course->eabcattendance_instance[$att->id] = array();
                $course->eabcattendance_instance[$att->id]['name'] = $att->name;
                $todaysessions = $att->get_today_sessions();

                if (!empty($todaysessions)) {
                    $course->eabcattendance_instance[$att->id]['today_sessions'] = $todaysessions;
                    $coursessessions[$course->id] = $course;
                }
            }
        }

        return self::prepare_data($coursessessions);
    }

    /**
     * For this user, this method searches in all the courses that this user has permission to take eabcattendance,
     * looking for today sessions and returns the courses with the sessions.
     * @param int $userid
     * @return array
     */
    public static function get_courses_with_today_sessions($userid) {
        $usercourses = enrol_get_users_courses($userid);
        $eabcattendanceinstance = get_all_instances_in_courses('eabcattendance', $usercourses);

        $coursessessions = array();

        foreach ($eabcattendanceinstance as $eabcattendance) {
            $context = context_course::instance($eabcattendance->course);
            if (has_capability('mod/eabcattendance:takeeabcattendances', $context, $userid)) {
                $course = $usercourses[$eabcattendance->course];
                $course->eabcattendance_instance = array();

                $att = new stdClass();
                $att->id = $eabcattendance->id;
                $att->course = $eabcattendance->course;
                $att->name = $eabcattendance->name;
                $att->grade = $eabcattendance->grade;

                $cm = new stdClass();
                $cm->id = $eabcattendance->coursemodule;

                $att = new mod_eabcattendance_structure($att, $cm, $course, $context);
                $course->eabcattendance_instance[$att->id] = array();
                $course->eabcattendance_instance[$att->id]['name'] = $att->name;
                $todaysessions = $att->get_today_sessions();

                if (!empty($todaysessions)) {
                    $course->eabcattendance_instance[$att->id]['today_sessions'] = $todaysessions;
                    $coursessessions[$course->id] = $course;
                }
            }
        }

        return self::prepare_data($coursessessions);
    }

    /**
     * Prepare data.
     *
     * @param array $coursessessions
     * @return array
     */
    private static function prepare_data($coursessessions) {
        $courses = array();

        foreach ($coursessessions as $c) {
            $courses[$c->id] = new stdClass();
            $courses[$c->id]->shortname = $c->shortname;
            $courses[$c->id]->fullname = $c->fullname;
            $courses[$c->id]->eabcattendance_instances = $c->eabcattendance_instance;
        }

        return $courses;
    }

    /**
     * For this session, returns all the necessary data to take an eabcattendance.
     *
     * @param int $sessionid
     * @return mixed
     */
    public static function get_session($sessionid) {
        global $DB;

        $session = $DB->get_record('eabcattendance_sessions', array('id' => $sessionid));
        $session->courseid = $DB->get_field('eabcattendance', 'course', array('id' => $session->eabcattendanceid));
        $session->statuses = eabcattendance_get_statuses($session->eabcattendanceid, true, $session->statusset);
        $coursecontext = context_course::instance($session->courseid);
        $session->users = get_enrolled_users($coursecontext, 'mod/eabcattendance:canbelisted',
                                             $session->groupid, 'u.id, u.firstname, u.lastname');
        $session->eabcattendance_log = array();

        if ($eabcattendancelog = $DB->get_records('eabcattendance_log', array('sessionid' => $sessionid),
                                              '', 'studentid, statusid, remarks, id')) {
            $session->eabcattendance_log = $eabcattendancelog;
        }

        return $session;
    }

    /**
     * Update user status
     *
     * @param int $sessionid
     * @param int $studentid
     * @param int $takenbyid
     * @param int $statusid
     * @param int $statusset
     */
    public static function update_user_status($sessionid, $studentid, $takenbyid, $statusid, $statusset) {
        global $DB;

        $record = new stdClass();
        $record->statusset = $statusset;
        $record->sessionid = $sessionid;
        $record->timetaken = time();
        $record->takenby = $takenbyid;
        $record->statusid = $statusid;
        $record->studentid = $studentid;

        if ($eabcattendancelog = $DB->get_record('eabcattendance_log', array('sessionid' => $sessionid, 'studentid' => $studentid))) {
            $record->id = $eabcattendancelog->id;
            $DB->update_record('eabcattendance_log', $record);
        } else {
            $DB->insert_record('eabcattendance_log', $record);
        }

        if ($eabcattendancesession = $DB->get_record('eabcattendance_sessions', array('id' => $sessionid))) {
            $eabcattendancesession->lasttaken = time();
            $eabcattendancesession->lasttakenby = $takenbyid;
            $eabcattendancesession->timemodified = time();

            $DB->update_record('eabcattendance_sessions', $eabcattendancesession);
        }
    }
}
