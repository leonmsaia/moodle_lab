<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
use local_encuesta_movilidad\utils;
/**
 * @var moodle_page $PAGE
 * @var core_renderer $OUTPUT;
 */
global $PAGE, $OUTPUT, $CFG, $COURSE;
$url=new moodle_url('/local/encuesta_movilidad/view.php');
$goToSurvey = optional_param('survey', '', PARAM_RAW);
$courseId = optional_param('courseid', '', PARAM_RAW);
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_title('Encuesta de movilidad');
$PAGE->set_heading('Encuesta');
echo $OUTPUT->header();

$nombre = get_config('local_encuesta_movilidad', 'name_activity');
$texto  = get_config('local_encuesta_movilidad', 'text_activity');
$boton  = get_config('local_encuesta_movilidad', 'text_button_activity');
$link   = get_config('local_encuesta_movilidad', 'link_activity');
$aditionalParams = utils::get_aditionals_params();
$urlParams = "?usuariorut=" . $aditionalParams['usuariorut'] . "&empresarut=". $aditionalParams['empresarut'] . "&currenttime=" .  $aditionalParams['currentTime'];
$paramCourse = (!empty($courseId) ? '&courseid='.$courseId : '');
$linkEncuesta = $link . $urlParams . $paramCourse;

echo "<h2>". $nombre ."</h2><br>";
echo "<p>". $texto. "</p><br>";
if (!empty($goToSurvey)){
      //evento acceder a encuesta
      if (!empty($courseId)){
            $event = \local_encuesta_movilidad\event\mobility_survey::create(
                  array(
                  'context' => \context_course::instance($courseId),
                  'courseid' => $courseId,
                  'other' => $aditionalParams
                  )
            );
            $event->trigger();
      }
      redirect($linkEncuesta);
}
echo "<p><a class='btn btn-primary' href='".$url.'?survey=1'. $paramCourse ."'>". $boton. "</a></p>" ;

echo $OUTPUT->footer();