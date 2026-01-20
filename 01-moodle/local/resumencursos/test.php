<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include_once('../../config.php');

$courseid          = optional_param('courseid', 0, PARAM_INT);

require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
require_once($CFG->libdir . '/datalib.php');

//require_once($CFG->libdir . '/grade/grade_item.php');
//require_once($CFG->libdir . '/grade/grade_grade.php');
//require_once($CFG->libdir . '/grade/constants.php');
//
//$gradeitemparamscourse = [
//    'itemtype' => 'mod',
//    'itemmodule' => 'quiz',
//    'courseid' => $courseid,
//];
//$grade_course = \grade_item::fetch($gradeitemparamscourse);
//$grades_user = \grade_grade::fetch_users_grades($grade_course, array($USER->id), false);
//$finalgradeuser = $grades_user[key($grades_user)]->finalgrade;

$quizes = $DB->get_records('quiz', array('course' => $courseid));
$course = $DB->get_record('course', array('id' => $courseid));
$geenralattemps = 0;
foreach($quizes as $quiz){
    $gradesum = 0;
    if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
    // Get this user's attempts.
    $attempts = quiz_get_user_attempts($quiz->id, $USER->id, 'finished', true);
    $attemps = array();
    foreach ($attempts as $attempt) {
        $quizattem = new quiz_attempt($attempt, $quiz, $cm, $course, false);
        $attemps[] = array(
            'name' => $quizattem->get_quiz_name(),
            'get_sum_marks' => $quizattem->get_sum_marks(),
            'attempt' => $quizattem->get_attempt(),
            'attempt_number' => $quizattem->get_attempt_number(),
        );
        $gradesum = $gradesum + $quizattem->get_sum_marks();
    }
    
    $numattempts = count($attempts);
    $geenralattemps = $geenralattemps + ($gradesum/$numattempts);
    
    
}
echo "<pre>" . ($geenralattemps/count($quizes)) . "</pre><br>";


//echo "<pre>" . print_r($attemps, true) . "</pre>";