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
 * utils class
 *
 * @package    local_eabcprogramas
 * @copyright 2020 Eimar Urbina <eimar@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eabcprogramas\task;

use coding_exception;
use dml_exception;
use grade_grade;
use grade_item;
use local_eabcprogramas\persistent\programasusuarios;
use local_eabcprogramas\utils;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/querylib.php');

class finalizacion_programa extends \core\task\scheduled_task
{

    /**
     * @return string
     * @throws coding_exception
     */
    public function get_name()
    {
        return get_string("finalizaciondeprograma", "local_eabcprogramas");
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
     */
    public function execute()
    {
        global $DB;
        $last_execution = $this->get_last_run_time();

        $programs = $DB->get_records('local_eabcprogramas', ['status' => utils::activo()]);
        foreach ($programs as $key => $program) {
            if (!$program->cursos) {
                continue;
            }
            /*
            * matriculaciones desde esta fecha.
            */
            $year = $program->version;

            if (!is_numeric($year)) {
                continue;
            }
            if (preg_match("/\d{4}/", $year) === 0) {
                continue;
            }
            if ($year <= 2016 || $year > date('Y', time())) {
                continue;
            }

            echo '<b>Año de enrolamiento: ' . $year . '</b><br>';
            $fromtimestr = '1 January ' . $year;
            $fromtime = strtotime($fromtimestr);

            $totimestr = '31 December ' . $year;
            $totime = strtotime($totimestr);

            echo "<hr><h4><i>" . $program->description . ", cursos: " . $program->cursos . "</i></h4>";

            $gradepass = 0;

            $config = get_config('local_eabcprogramas');
            if ($config) {
                if ($config->param_aprobacion) {
                    $gradepass = $config->param_aprobacion;
                }
            }

            $cursos = explode(',', $program->cursos);

            $aprobados = array();
            $cursos_lista = [];

            foreach ($cursos as $key => $cursoid) {
                $course = $DB->get_record('course', ['id' => $cursoid]);
                if ($course->id == 1) {
                    continue;
                }
                $courseid = $course->id;
                echo "<hr><h5>Curso: " . $course->fullname . "</h5>";
                /*
                * Completion Info
                */
                $completioninfo = new \completion_info($course);
                if (!$completioninfo->is_enabled()) {
                    continue;
                }

                /*
                * query para búsqueda de userids
                * Valido rol y fecha de matriculación
                */
                $query = "SELECT ra.userid as userid, c.id  as courseid
                            FROM {role_assignments} ra 
                            join {user} u on u.id = ra.userid
                            join {context} co on co.id = ra.contextid
                            join {course} c on c.id = co.instanceid
                            WHERE ra.roleid = 5
                            and co.contextlevel = 50
                            and c.id = " . $courseid . "
                            and ra.timemodified > " . $fromtime . "
                            and ra.timemodified < " . $totime;

                /*
                * Grade pass
                */

                if ($gradepass == 0) {
                    $gradecourseitem = $DB->get_record('grade_items', array('courseid' => $course->id, 'itemtype' => 'course'));
                    if ($gradecourseitem && $gradecourseitem->gradepass) {
                        $gradepass = $gradecourseitem->gradepass;
                    }
                }
                if ($gradepass == 0 || $gradepass == null) {
                    $gradepass = 75;
                }
                echo 'Nota para aprobar: ' . $gradepass . '<br>';

                /*
                * Obtengo paginados.
                */
                // for ($page = 0; $page <= $pages; $page++) {
                //usuarios matriculados en el curso por página.
                $userids = [];
                $enrolls = $DB->get_records_sql($query);
                if (is_array($enrolls)) {
                    $userids = array_keys($enrolls);

                    //obtengo info del calificador por página
                    $gradeusers = grade_get_course_grades($courseid, $userids);

                    $gradeusers = $gradeusers->grades;
                    foreach ($enrolls as $enroll) {
                        $completed = false;
                        $approved = false;
                        $programa = $DB->get_record('local_eabcprogramas_usuarios', ['userid' => $enroll->userid, 'programid' => $program->id]);
                        if ($programa) {
                            continue;
                        }
                        //grade
                        $finalgrade = 'sin calificación';
                        $nota = 0;
                        $asistencia = 0;
                        if (
                            isset($gradeusers[$enroll->userid]) &&
                            !is_null($gradeusers[$enroll->userid]->grade && $gradeusers[$enroll->userid]->grade > 0)
                        ) {
                            $finalgrade = round($gradeusers[$enroll->userid]->grade, 2);
                            if ($gradepass > 0) {
                                if ($finalgrade >= $gradepass) {
                                    $nota = $finalgrade;
                                    $finalgrade = "<spam style='color:green'>Usuario: " . $enroll->userid . ", nota: " . $nota . " Aprobado</spam>";
                                    $approved = true;
                                } else {
                                    $finalgrade = "<spam style='color:red'>Usuario: " . $enroll->userid . ", nota: " .  $nota . " </spam>";
                                    $approved = false;
                                    continue;
                                }
                            }
                            echo $finalgrade . '<br>';
                        }else{
                            continue;
                        }

                        //info completion por usuario con clase completion_info
                        $status = 'sin información de finalización';
                        if ($completioninfo->is_enabled()) {
                            $coursecompleted = $completioninfo->is_course_complete($enroll->userid);
                            if ($coursecompleted) {
                                //info fecha completion por usuario con query a la BBDD
                                $completion = $DB->get_record('course_completions', ['userid' => $enroll->userid, 'course' => $courseid]);
                                if ($completion) {
                                    $status = "<spam style='color:green'>Usuario: " . $enroll->userid . " completado el " . date('d-m-Y', $completion->timecompleted) . "</spam>";
                                    // if (date('Y', $completion->timecompleted) == date('Y', time())) {
                                    if (date('Y', $completion->timecompleted) == $year) {
                                        $completed = true;
                                    }
                                }
                            } else {
                                $status = "<spam style='color:red'>Usuario " . $enroll->userid . " Sin completar</spam>";
                                $completed = false;
                                continue;
                            }
                            echo $status . '<br>';
                        }else{
                            continue;
                        }
                        // $encuesta_completada = utils::activity_completed($courseid, $enroll->userid, "questionnaire");
                        // $evaluacionfinal_completada = utils::activity_completed($courseid, $enroll->userid, "quiz");

                        // if ($encuesta_completada && $evaluacionfinal_completada) {
                        //     $asistencia = 100;
                        // }
                        if ($completed && $approved) {
                            $asistencia = 100;
                            $aprobados[$enroll->userid][$courseid] = $completion->timecompleted;

                            if (isset($cursos_lista[$enroll->userid])) {
                                $cursos_lista[$enroll->userid] = $cursos_lista[$enroll->userid] . $course->fullname . ',' . $nota . ',' . $gradepass . ',' . $asistencia . ','. $completion->timecompleted . ':';
                            } else
                                $cursos_lista[$enroll->userid] = $course->fullname . ',' . $nota . ',' . $gradepass . ',' . $asistencia . ','. $completion->timecompleted . ':';
                        }
                    }
                }
                // }
            }
            if ($aprobados) {
                foreach ($aprobados as $uid => $list_cursos) {
                    $cursos_por_usuario = array_keys($list_cursos);
                    $cant_cursos_pr = count($cursos);
                    $count = 0;
                    foreach ($cursos_por_usuario as $cid => $value) {
                        if (in_array($value, $cursos)) {
                            $count++;
                        } else {
                            break;
                        }
                    }
                    if ($cant_cursos_pr == $count) {
                        $endfirstcourse = min($list_cursos);
                        if (isset($cursos_lista[$uid])) {
                            utils::agregar_programa_usuario($uid, $program, $endfirstcourse, $cursos_lista[$uid]);
                        }
                    }
                }
            }
        }
    }
}
