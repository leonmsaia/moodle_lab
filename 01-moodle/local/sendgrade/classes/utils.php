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
namespace local_sendgrade;

use core\event\user_graded;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers supported by this format.
 * @package    local_sendgrade
 * @copyright  2018 David Watson {@link http://evolutioncode.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {
   

    /**
     * @param user_graded $event
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function user_graded(user_graded $event)
    {
        global $DB;
        $grade = $event->get_grade();
        $itemtype = $grade->grade_item->itemtype;
        

    }


    public static function get_grade_user_course($courseid, $userid){
        global $CFG;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->libdir.'/grade/grade_grade.php');
        $gradeitemparamscourse = [
            'itemtype' => 'course',
            'courseid' => $courseid,
        ];
        $grade_course = \grade_item::fetch($gradeitemparamscourse);
        $grades_user = \grade_grade::fetch_users_grades($grade_course, array($userid), false);
        $finalgradeuser = $grades_user[key($grades_user)]->finalgrade;
        return $finalgradeuser;
    }

    public static function get_grade_item($courseid, $item){
        global $DB;
        $item_id_course = $DB->get_record('grade_items', ['itemtype' => $item, 'courseid' => $courseid ]);
        if(empty($item_id_course)){
            return 0;
        }
        return $item_id_course;
    }

    public static function send_grade($userid, $courseid, $grade, $item_id_course){
        global $DB;
        //valido que este enviada la nota
        $get_record = $DB->get_record('format_eabctiles_send_course', ['userid' => $userid, 'courseid' => $courseid]);
        $dataobject = new \stdClass();
        if(empty($get_record)){
            $dataobject->userid = $userid;
            $dataobject->courseid = $courseid;
            $dataobject->grade = $grade;
            $dataobject->timestamp = time();
            $DB->insert_record('format_eabctiles_send_course', $dataobject);
        } else {
            $dataobject->id = $get_record->id;
            $dataobject->grade = $grade;
            $DB->update_record('format_eabctiles_send_course', $dataobject);
        }
        
        //envio al ws si esta a
        $testing = get_config('format_eabctiles','sendgrade_course');
        if(!empty($testing)){
            \format_eabctiles\utils\eabctiles_utils::send_grade_save($courseid, $userid, (float)$grade, (float)$item_id_course->gradepass);
        }
        //guardo en el log
        $DB->insert_record('local_sendgrade_log', $dataobject);
        
    }


    public static function send_grade_log($userid, $courseid, $grade, $quizid, $status_confirm = null, $cmid){
        global $DB;

        $attempts = quiz_get_user_attempts((array) $quizid, $userid, 'finished', true);
        $lastfinishedattempt = end($attempts);
        
        $get_send = $DB->get_records('format_eabctiles_send_course', ['userid' => $userid, 'courseid' => $courseid]);
        if(!empty($get_send)) {
            $get_send = end($get_send)->id;
        } else {
            $get_send = null;
        }

        $cm = get_coursemodule_from_id(null, $cmid, $courseid);
        $grade_item = $DB->get_record('grade_items', ['itemtype' => 'mod', 'courseid' => $courseid, 'itemmodule' => 'quiz', 'iteminstance' => $cm->instance ]);
        
        $dataobject = new \stdClass();
        $dataobject->userid = $userid;
        $dataobject->courseid = $courseid;
        $dataobject->grade = $grade;
        $dataobject->quiz_attempts_id = $lastfinishedattempt->id;
        $dataobject->send_grade_id = $get_send;
        $dataobject->timestamp = time();
        $dataobject->status_confirm = $status_confirm;
        $dataobject->grade_item = $grade_item->id;
        $dataobject->grade_pass = $grade_item->gradepass;

        $DB->insert_record('format_eabctiles_sendco_log', $dataobject);
    }
}