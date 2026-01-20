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
 * Plugin capabilities are defined here.
 *
 * @package     local_eabcourses
 * @category    courses
 * @copyright   2020 Ysrrael Sánchez <ysrrael@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function get_courses_elearning()
{
    global $USER, $PAGE, $CFG;
    $systemcontext = context_system::instance();
    $PAGE->set_context($systemcontext);
    //auth plugin del que traerá los datos de usuarios
    $report = '';
    $datos = new stdclass();
    $courseslist = array();
    $array = array();
    $limit = get_config('local_cron', 'days');
    if(empty($limit)) {
        $limit = 30; 
    }

    $array[] = 'enddate';
    $datos->courses_current = get_string('courses_current', 'local_eabcourses');
    $datos->courses_not_current = get_string('courses_not_current', 'local_eabcourses');
    $courseslist = enrol_get_all_users_courses($USER->id, true,  $array, $sort = 'enddate DESC');
    $time = new DateTime("now", core_date::get_user_timezone_object());

    foreach ($courseslist as $course) {
        $row = array();
        $row['fullname'] = $course->fullname;
        $row['link'] = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
        
        //SI EL CURSO ES ELEARNING
        if(\local_mutual\front\utils::is_course_elearning($course->id) == true){
            $enrolldate = \local_eabcourses\utils::get_user_course_enroldate($USER, $course);
            
            $nowtimestamp = $time->getTimestamp();
            $limittime = $enrolldate + $limit * 60 * 60 * 24;
            $timesend =  ($limittime >= $nowtimestamp) ? date('Y-m-d', $limittime) : '';
            $dEnd  = new DateTime($timesend);
            $dDiff = $time->diff($dEnd);

            //convierto la fecha para buscar el dia, mes y año
            $finaldateStr = userdate($limittime, '%Y-%m-%d');
            //despues de encontrear la fecha la fecha le agrego 23:59:59 
            //para que sea el final del dia y lo convierto a timestamp
            $limittime = strtotime($finaldateStr . 'T23:59:59z');
            
            $row['remainingdays'] = ($limittime >= $nowtimestamp) ? $dDiff->days . ' Días' : 'Vencido';            
            $row['date'] = date('d-m-Y', $limittime);
            $row['modalidad'] = 'Elearning';

            //Cursos vencidos y disponibles
            if ($limittime >= $nowtimestamp) {
                $datos->rows_courses_current[] = $row;
            } else {
                $datos->rows_courses_nocurrent[] = $row;
            }
        }else{
            // SI no es ELearning es PRESENCIAL O STREAMINNG
            $enrolldate = \local_eabcourses\utils::get_remainingdays_presencial_streamning($USER->id, $course->id);
            $row['remainingdays'] = $enrolldate;            
            $row['date'] = '';
            $row['modalidad'] = (\local_mutual\front\utils::is_course_presencial($course->id)) ? 'Presencial' : 'Streaming';
            if ($row['remainingdays'] == 'Disponible') {
                $datos->rows_courses_current[] = $row;
            } else {
                $datos->rows_courses_nocurrent[] = $row;
            }            
        }
    }

    $datos->rows_courses_current    = isset($datos->rows_courses_current) ? orderCursos($datos->rows_courses_current) : '';
    $datos->rows_courses_nocurrent  = isset($datos->rows_courses_nocurrent) ? orderCursos($datos->rows_courses_nocurrent) : '';

    $renderer = $PAGE->get_renderer('local_eabcourses');
    if (!empty($courseslist)) {
        $report .= $renderer->render_courses($datos);
    } else {
        $message = array('message' => get_string('nodata', 'local_eabcourses'));
        $report .= $renderer->render_message($message);
    }

    return $report;
}

function orderCursos($array){
    //Ordenamiento de cursos por Elearning - Streaming - Presencial
    usort($array, function($a, $b) {
        return $a['modalidad'] <=> $b['modalidad'];
    });

    usort($array, function($a, $b) {
        if ($a['modalidad']!='Elearning'){
            return $b['modalidad'] <=> $a['modalidad'];
        }
    });

    return $array;
}