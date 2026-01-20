<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
global $DB;

$sql_base = "
  FROM mdl_inscripcion_elearning_back ieb
  JOIN mdl_course_completions cc 
    ON cc.course = ieb.id_curso_moodle 
   AND cc.userid = ieb.id_user_moodle
  JOIN mdl_grade_items gi 
    ON gi.courseid = cc.course 
   AND gi.itemtype = 'course'
  LEFT JOIN mdl_grade_grades gg 
    ON gg.itemid = gi.id 
   AND gg.userid = cc.userid
  WHERE ieb.timereported = 0 
    AND cc.timecompleted IS NOT NULL 
    AND gg.finalgrade > 75
    AND ieb.createdat >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 60 DAY), '%Y-%m-%d %H:%i:%s')
";

$total = $DB->get_field_sql("SELECT COUNT(*) $sql_base");
$minid = $DB->get_field_sql("SELECT MIN(ieb.id) $sql_base");
$maxid = $DB->get_field_sql("SELECT MAX(ieb.id) $sql_base");

echo $total . "|" . $minid . "|" . $maxid . "\n";