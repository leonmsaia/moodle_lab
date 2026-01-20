<?php

//define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
/** @var moodle_database $DB */
global $CFG, $DB;

require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/grade/grade_item.php');
require_once($CFG->dirroot . '/lib/grade/grade_grade.php');
require_once($CFG->dirroot . '/lib/gradelib.php');
//busco solo los item que tengan menos de 100 para agilizar la consulta
$sql = "SELECT gi.* FROM {grade_items} as gi 
JOIN {eabcattendance} as e ON gi.iteminstance = e.id AND gi.courseid = e.course
WHERE gi.itemmodule = 'eabcattendance' AND gi.grademin <> 100";
$grade_items = $DB->get_records_sql($sql, null, 0, 0);

foreach($grade_items as $grade_item){
    $obj = new stdClass();
    $obj->id = $grade_item->id;
    $obj->grademin = 100;
    $DB->update_record('grade_items', $obj);
}



