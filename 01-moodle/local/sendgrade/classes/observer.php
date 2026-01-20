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
 * Event observers supported by this format.
 * @package    format_eabctiles
 * @copyright  2018 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use core\event\user_graded;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers supported by this format.
 * @package    local_sendgrade
 * @copyright  2018 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_sendgrade_observer {
   

    /**
     * @param user_graded $event
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function user_graded(user_graded $event)
    {
        global $DB;
        $grade = $event->get_grade();
    }

    /*
        Evento se ejevuta cuando el usuario ejecuta el submit de envio de nota del quiz
        evento se ejecuta antes de guardar la nota
    */
    public static function attempt_submitted($event){
        global $DB, $SESSION, $USER;

        $data = $event->get_data();
        $get_sends = $DB->get_record('format_eabctiles_send_course', ['userid' => $data['relateduserid'], 'courseid' => $event->courseid]);
        //si es curso elearning y no se a enviado
        /* if((\local_mutual\front\utils::is_course_elearning($event->courseid) == true) && (empty($get_sends))){
            $item_id = $DB->get_record('grade_items', ['iteminstance' => $data['other']['quizid'], 'courseid' => $data['courseid'], 'itemmodule' => 'quiz' ]);
            $grade_user = $DB->get_record('grade_grades', ['itemid' => $item_id->id, 'userid' => $data['relateduserid'] ]);
            $attempt = $event->get_record_snapshot('quiz_attempts', $event->objectid);
            $quiz    = $event->get_record_snapshot('quiz', $attempt->quiz);
            //$cm      = get_coursemodule_from_id('quiz', $event->get_context()->instanceid, $event->courseid);
            if(intval($item_id->grademax) == 10 ){
                //si la nota esta configurada en base a 10 la multiplico por 10 para saber el calculo en base a 100
                $gradeuser = $attempt->sumgrades * 10;
            } else {
                $gradeuser = $attempt->sumgrades;
            }
            //si tiene nota de curs obusco el mayor entre la nota del curso y la nota del usuario
            $grade_user_course = \local_sendgrade\utils::get_grade_user_course($data['courseid'], $USER->id);
            $grade = ($gradeuser >= $grade_user_course)  ? $gradeuser : $grade_user_course;
            //si saco la nota maxima del curso envio automaticamente la nota
            $grade_item_course = \local_sendgrade\utils::get_grade_item($data['courseid'], 'course');

            if($grade == $grade_item_course->grademax){
                \local_sendgrade\utils::send_grade($data['relateduserid'], $data['courseid'], $grade, $grade_item_course->gradepass);
            } else {
                //valido si es el ultimo intento o si no le quedan mas intentos 
                //en caso de no quedar mas intentos envio la nota mayor
                if($quiz->attempts == $attempt->attempt){
                    \local_sendgrade\utils::send_grade($data['relateduserid'], $data['courseid'], $grade, $grade_item_course->gradepass);
                } else {
                    //en caso de que le quedan intentos restantes
                    //si la nota del usuario es mayor o igual a la nota para aprobar el curso le muestro la alerta
                    //en caso contrariono hago nada 
                    if(($gradeuser  >= $grade_item_course->gradepass)){
                        //validar solo que la nota sea difernte a 100 entre las enviadas 
                        if( $SESSION->send_grade !== 'cancel'){
                            redirect(new moodle_url('/local/sendgrade/confirm_send.php', ['action' => "send", 'attempt' => $attempt->id, 'attempsquiz' => $quiz->attempts, 'attempsquizuser' => $attempt->attempt, 'cmid' => $quiz->cmid, 'courseid' => $event->courseid, 'quizid' => $data['other']['quizid'], 'sumgrade' => base64_encode($gradeuser) ]));
                        } else {
                            $SESSION->send_grade = 'procesar';
                        }
                    } else {

                    }
                }
            }
        } */
    }

    /**
    * Observer
    * Permite limpiar la tabla bandera de envios al desmatricular un usuarios
    * @param type $event
    */

    public static function unenrole_observer($event) {
        global $DB;
         try {
            $DB->delete_records('format_eabctiles_send_course', array("userid" => $event->relateduserid, "courseid" => $event->courseid));
         } catch (\Exception $e) {
           error_log(print_r($e, true));
         }
         
    }
}