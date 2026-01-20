<?php

namespace local_rating_item\utils;

use stdClass;
use context_module;
use mod_eabcattendance_structure;
use moodle_exception;


class rating_item_utils {
    static function save_rating($get_data){
        global $CFG, $USER;
        require_once $CFG->dirroot . '/lib/grade/grade_grade.php';
        require_once $CFG->dirroot . '/lib/grade/grade_item.php';
        require_once $CFG->dirroot . '/lib/grade/constants.php';
        require_once $CFG->dirroot . '/lib/gradelib.php';
        $item1 = get_config('local_rating_item', 'item1_course'. $get_data->courseid);
        
        $grade_grade_object = \grade_grade::fetch(array('userid' =>$get_data->userid, 'itemid' => $item1));
        if(empty($grade_grade_object)){
            $grade_grade = new \grade_grade();
            $grade_grade->itemid = intval($item1);
            $grade_grade->userid = $get_data->userid;
            $grade_grade->rawgrade = grade_floatval($get_data->grade);
            $grade_grade->finalgrade = grade_floatval($get_data->grade);
            $grade_grade->feedback = $get_data->feedback["text"];
            $grade_grade->usermodified = intval($USER->id);
            $grade_grade->timecreated = intval(time());
            $grade_grade->timemodified = intval(time());
            $grade_grade->aggregationstatus = 'used';
            $grade_grade->insert();
        } else {
            if(empty($grade_grade_object->finalgrade)){
                $grade_grade = new \grade_grade();
                $grade_grade->id = $grade_grade_object->id;
                $grade_grade->itemid = intval($item1);
                $grade_grade->userid = $get_data->userid;
                $grade_grade->rawgrade = grade_floatval($get_data->grade);
                $grade_grade->finalgrade = grade_floatval($get_data->grade);
                $grade_grade->feedback = $get_data->feedback["text"];
                $grade_grade->usermodified = intval($USER->id);
                $grade_grade->timecreated = intval(time());
                $grade_grade->timemodified = intval(time());
                $grade_grade->aggregationstatus = 'used';
                $grade_grade->update();
            }
        }
    }
    
    static function get_users_to_rating($courseid){
        global $CFG;
        require_once $CFG->dirroot . '/lib/grade/grade_grade.php';
        require_once $CFG->dirroot . '/lib/grade/grade_item.php';
        require_once $CFG->dirroot . '/lib/grade/constants.php';
        
        $enroles = get_enrolled_users(\context_course::instance($courseid));
        $array_grades = array();
        foreach ($enroles as $enrole){
            $item1 = get_config('local_rating_item', 'item1_course'. $courseid);
            $item2 = get_config('local_rating_item', 'item2_course'. $courseid);
            $grade_item1 = \grade_grade::fetch(array('userid' =>$enrole->id, 'itemid' => $item1));
            $grade_item2 = \grade_grade::fetch(array('userid' =>$enrole->id, 'itemid' => $item2));
            if(empty($grade_item1->finalgrade) && !empty($grade_item2->finalgrade)){
                $array_grades[$enrole->id] = $enrole->firstname . ' ' . $enrole->lastname;
            }
        }
        return $array_grades;
    }
    
    static function get_gradeitems_course($courseid){
        global $DB;
        $gradeitemarray = array();
        $gradeitems = $DB->get_records_sql('select * from {grade_items} as gi where gi.courseid = ? and gi.itemtype not in ("course")', array($courseid ));
        foreach($gradeitems as $gradeitem){
            $gradeitemarray[$gradeitem->id] = $gradeitem->itemname;
        }
        return $gradeitemarray;
    }
}
