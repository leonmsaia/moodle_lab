<?php

namespace local_resumencursos\utils;

use stdClass;
use context_module;
use mod_eabcattendance_structure;
use moodle_exception;
use quiz_attempt;

class summary_utils {
    
    public static function get_grades($userid) {
        global $DB;
        //busco las calificaciones del usuario
        $calificaciones = $DB->get_records('grade_grades', array('userid' => $userid));
        $calificaciones_finales = array();
        foreach ($calificaciones as $c) {
            $tipo_actividad = $DB->get_record('grade_items', array('id' => $c->itemid));
            if (!($tipo_actividad->categoryid)) {
                $c->course = $tipo_actividad->courseid;
                $calificaciones_finales[] = $c;
            }
        }
        return $calificaciones_finales;
    }
    
    
    public static function final_date($course) {
        $fecha_finalizacion = $course->enddate;
        if ($fecha_finalizacion != 0) {
            $stop_date = date('Y-m-d', $course->enddate);
            $fecha_finalizacion =  date('d/m/Y', strtotime($stop_date. ' +36 month'));
        } else {
            $fecha_finalizacion = '';
        }
        return $fecha_finalizacion;
    }

    
    public static function qualification($calificaciones_finales, $id) {
        $calificacion = '';
                foreach ($calificaciones_finales as $c) {
                    if ($c->course == $id) {
                        $calificacion = (int)$c->finalgrade . '/' . (int)$c->rawgrademax;
                    }
                }
                return $calificacion;
    }
    
    public static function get_activity($eabctiles_closegroup) {
        $actividad = '';
        if ($eabctiles_closegroup != false) {
            if ((int) ($eabctiles_closegroup->timecreated) != 0) {

                $actividad = ' ' . getdate((int) ($eabctiles_closegroup->timecreated))['mday'];
                $actividad = $actividad . '/' . getdate((int) ($eabctiles_closegroup->timecreated))['mon'];
                $actividad = $actividad . '/' . getdate((int) ($eabctiles_closegroup->timecreated))['year'];
            }
        }
        return $actividad;
    }

    public static function get_status_session($userid, $sessionid) {
        global $DB;
        
        $status = $DB->get_record_sql('select l.id, s.description from {eabcattendance_log} AS l
                JOIN {eabcattendance_statuses} as s on l.statusid = s.id 
                where l.sessionid = ' . $sessionid . ' and l.studentid = ' . $userid);
        return $status;
    }
    
    public static function get_data_return($course, $calificacion, $fecha_finalizacion, $actividad, $sessiondate = '', $statusdata = '', $time = '', $fecha_caducidad = '', $session = null){ 
        global $CFG, $DB;
        $address = '';
        $nombreadherente = '';
        $curso_back = $DB->get_record('curso_back', array('id_curso_moodle' => $course->id));
        $modalidad = (!empty($curso_back)) ? self::get_modalidad($curso_back) : "";
        if(!empty($session)){
            $fecha_finalizacion = self::get_ending($curso_back, $session->id, $course);
            $sesion_back = $DB->get_record('sesion_back', array('id_sesion_moodle' => $session->sessionid));
            if(!empty($sesion_back)){
                $address = self::get_address($curso_back, $sesion_back);
                $nombreadherente = $sesion_back->nombreadherente;
            }
        } else {
            $fecha_finalizacion = self::get_ending($curso_back, '', $course);
        }
        
        return array(
            'nombrecurso' => array( 'url' => $CFG->wwwroot . '/course/view.php?id=' . $course->id, 'name' => $course->shortname, 'fullname' => $course->fullname),
            'courseid' => $course->id,
            'modalidad' => $modalidad,
            'calificacion' => $calificacion,
            'finalizacion' => $fecha_finalizacion,
            'duracion' => (!empty($curso_back)) ? $curso_back->horas : "",
            'direccion' => $address,
            'actividad' => $actividad,
            'caducidad' => $fecha_caducidad,
            'valoracion' => self::average_quizes($course),
            'disponibilidad' => '',
            'sessdate' => $sessiondate,
            'status' => $statusdata,
            'time' => $time,
            'nombreadherente' => $nombreadherente
        );
    }
    
    public static function get_roles($userid, $ourseid) {
        global $DB;
        $sql = "SELECT ra.id, ra.userid, ra.contextid, ra.roleid, c.instanceid
                                FROM {role_assignments} ra
                                JOIN {context} c ON ra.contextid = c.id AND c.contextlevel = 50
                                JOIN {role} r ON ra.roleid = r.id
                               WHERE ra.userid = ? AND c.instanceid = ?
                            ORDER BY contextlevel DESC, contextid ASC, r.sortorder ASC";
        $roleassignments = $DB->get_records_sql($sql, array($userid, $ourseid));
        return $roleassignments;
    }

    /*
     * modalidad:
        "100000000" = "Presencial"
        "100000001" = "Semi-presencial"
        "100000002" = "Distancia"
        "201320000" = "Elearning"
        "201320001" = "streaming"
        "201320002" = "mobile"
     */
    public static function get_modalidad($data){
        if(empty($data)){
            return "Elearning";
        }
        
        $modalidad = '';
        switch ($data->modalidad) {
			case get_config('local_pubsub', 'tipomodalidadpresencial'):
				//si la modalidad es presencial
                $modalidad = get_string('presencial', 'local_resumencursos');
                break;
			case get_config('local_pubsub', 'tipomodalidadsemipresencial'):
				//si la modalidad es semi presencial
				$modalidad = get_string('semipresencial', 'local_resumencursos');
				break;
			case get_config('local_pubsub', 'tipomodalidaddistancia'):
				//si la modalidad es distancia
				$modalidad_distancia_elearning  = get_config('local_pubsub', 'modalidaddistanciaelearning');
				$modalidad_distancia_streaming  = get_config('local_pubsub', 'modalidaddistanciastreaming');
				$modalidad_distancia_mobile     = get_config('local_pubsub', 'modalidaddistanciamobile');

				if ($data->modalidaddistancia == $modalidad_distancia_elearning){
					// Modalidad a Distancia Tipo Elearning
					$modalidad = "Elearning";
				}elseif($data->modalidaddistancia == $modalidad_distancia_streaming){
					// Modalidad a Distancia Tipo Streming
					$modalidad = "Streaming";
				}elseif($data->modalidaddistancia == $modalidad_distancia_mobile){
                    // Modalidad a Distancia Tipo Mobile
                    $modalidad = "Mobile";
				}else{
					$modalidad = "Elearning";
				}  
				break;
			default:
                $modalidad = "Elearning";
        }
        return $modalidad;
    }
    
    /*
     * si es a distancia colcoar la palabra  “Virtual”
     *  "100000002" = "Distancia"
     */
    public static function get_address($modalidadCurso, $direccion){
        $dir = "";
        if(empty($modalidadCurso)){
            return "";
        }
        if(empty($direccion)){
            return "";
        }
        
        if($modalidadCurso->modalidad == "100000002"){
            $dir = get_string('virtual', 'local_resumencursos');
        } else {
            $dir = $direccion->direccion;
        }
        return $dir;
    }
    
    /*
    si es presencial mostra la fecha de finalizacion del curso de lo contrario mostrar fecha de cierre
     * "100000000" = "Presencial"
    */
    public static function get_ending($curso_back, $sessionid, $course){
        global $DB;
        if(empty($curso_back)){
            return "";
        }
        if($curso_back->modalidad == "100000000"){
            return date('d/m/yy', $course->enddate);
        } else {
            $close = $DB->get_record('format_eabctiles_closegroup', array('groupid' => $sessionid));
            if(!empty($close)){
                return date('d/m/yy', $close->timemodified);
            } else {
                return '';
            }
        }
    }
    
    public static function average_quizes($course){
        global $USER, $DB, $CFG;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
        require_once($CFG->libdir . '/datalib.php');
        $quizes = $DB->get_records('quiz', array('course' => $course->id));
        $numattempts = 1;
        $numquizes = 0;
        $geenralattemps = 0;
        foreach($quizes as $quiz) {
            $gradesum = 0;
            if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
                return 0;
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
                $numattempts++;
            }
            $numquizes++;
            $geenralattemps = $geenralattemps + ($gradesum / $numattempts);
        }
        //evitar division entre cero
        $numquizes = ($numquizes == 0) ? 1 : $numquizes;
        return ($geenralattemps/$numquizes);
    }

    public static function get_select_table_sql($userid){
        $is_not_admin = '';
        if(is_siteadmin() == false){
            $is_not_admin = 'ue.timestart as timestartenrol,
            ue.timecreated as timecreatedenrol,';
        }
        

        return 'c.id as courseid,   
        cb.horas,
        cb.tipomodalidad as modalidad,
        cb.modalidaddistancia,
        #formatear
        IF(cc.timecompleted IS NOT NULL, 
            cc.timecompleted, 
            ""
        ) as final_date,
        ' . $is_not_admin . '
        (
            #traigo nota
            SELECT
            ROUND(gg.finalgrade,2) AS grade
            FROM {course} AS course
            JOIN {grade_grades} AS gg ON gg.userid = ' . $userid . '
            JOIN {grade_items} AS gi ON (gi.id = gg.itemid AND gi.courseid = course.id)
            WHERE  c.id = course.id AND gi.itemtype = "course"
            LIMIT 1
        ) as grade,
        "disponibilidad" as disponibilidad
                 ';
    }

    public static function get_from_table_sql($userid){
        $join_not_admin = "JOIN {enrol} as e ON e.courseid = c.id
        JOIN {user_enrolments} as ue ON ue.enrolid = e.id AND ue.userid = " . $userid;
        $is_not_admin = (is_siteadmin() == false) ?  $join_not_admin : '';
        return "{course} as c  
        " . $is_not_admin . "
        LEFT JOIN {curso_back} AS cb ON cb.id_curso_moodle = c.id AND c.visible = 1
        LEFT JOIN {course_completions} as cc ON (cc.course = c.id AND cc.userid= " . $userid . ")
        LEFT JOIN {grade_items} gi ON (gi.courseid = c.id AND gi.itemtype = 'course')
        LEFT JOIN {grade_grades} gg ON (gg.itemid = gi.id AND gg.userid = " . $userid . ")
        ";
    }
    public static function get_where($fromform, $is_capability, $is_capability_str){
        global $DB;
        $where = "";
        $strcursos = '';
        if(!empty($fromform->curso)){
            $cursos = $DB->get_records_sql('SELECT * FROM {course} as c WHERE c.fullname LIKE "%'.$fromform->curso.'%"');
            
            if(!empty($cursos)){
                foreach($cursos as $curso){
                    $strcursos .= $curso->id . ',';
                }
                $strcursos .= '"...."';
                $where .= ' AND c.id IN (' . $strcursos . ')';
            }
        }
        if (!empty($fromform->hours)) {
            $where .= ' AND cb.horas LIKE "%' . $fromform->hours . '%"';
        }
        if (!empty($fromform->modalidadopresencial) || !empty($fromform->modalidadsemipresencial)) {
            $modaliidadstr = "";
            if (!empty($fromform->modalidadopresencial)) {
                $modaliidadstr .= '"100000000",';
            } if (!empty($fromform->modalidadsemipresencial)) {
                $modaliidadstr .= '"100000001",';
            } 
            $modaliidadstr .= '"..."';
            $where .= ' AND cb.tipomodalidad IN (' . $modaliidadstr . ')';
        }
        if(!empty($fromform->modalidadelearning) || !empty($fromform->modalidadstreaming) || !empty($fromform->modalidadmobile)){
            $modaliidad_distancia_str = "";
            if (!empty($fromform->modalidadelearning)) {
                $modaliidad_distancia_str .= '"201320000",';
            } if (!empty($fromform->modalidadstreaming)) {
                $modaliidad_distancia_str .= '"201320001",';
            } if (!empty($fromform->modalidadmobile)) {
                $modaliidad_distancia_str .= '"201320002",';
            } 
            $modaliidad_distancia_str .= '"..."';
            $where .= ' AND cb.modalidaddistancia IN (' . $modaliidad_distancia_str . ')';
        }
        //echo print_r($fromform->dateto, true);exit;
        if (!empty($fromform->dateto) && ($fromform->dateto != 0) && ($fromform->dateto['enabled'] == 1) &&
        !empty($fromform->datefrom) && ($fromform->datefrom != 0) && ($fromform->datefrom['enabled'] == 1)) {
            $today = $fromform->dateto['day'] . '-' .$fromform->dateto['month'] . '-' . $fromform->dateto['year'];
            $todayfrom = $fromform->datefrom['day'] . '-' .$fromform->datefrom['month'] . '-' . $fromform->datefrom['year'];
            
            $timecomplete_today = strtotime($today);
            $timecomplete_todayfrom = strtotime($todayfrom);

            $allowdownloadwithgrade = static::allow_download_with_grade();
            $fecha_referencia_sql = "cc.timecompleted";
            if ($allowdownloadwithgrade) {
                $fecha_referencia_sql = "COALESCE(
                    NULLIF(cc.timecompleted, 0),
                    NULLIF(gg.timemodified, 0),
                    NULLIF(gg.timecreated, 0),
                    0
                )";
            }
            $where .= ' AND ( UNIX_TIMESTAMP(DATE_ADD(FROM_UNIXTIME(' . $fecha_referencia_sql . '), INTERVAL +36 MONTH))  BETWEEN "'.$timecomplete_today.'"  AND "'.$timecomplete_todayfrom.'")';
        }
        if($is_capability == true){
            $where .= ' AND c.id IN (' . $is_capability_str . '"....")';
        }
        return $where;
    }

    public static function completion_course($course, $user){
        global $DB;
        /*
            se considera completado si aprobo el curso nota de usuario mayor a 75 en base a la nota del curso
            y si su asistencia es 100%(configurable)
        */
        $is_ilerning = false;
        $completionbool = false;
        $activitycompletion = new stdClass();
        $completion_type = new stdClass();

        $completioncert = \local_download_cert\download_cert_utils::completion_cert($course->id);
        $completionattendance = \local_download_cert\download_cert_utils::completion_attendance($course->id);
        //si completo el curso con nota mayor a la nota de aprobacion
        if ($completioncert == true) {

            //consulto si es curso ilernisn
            $get_ilerning_enrol = $DB->get_record('inscripcion_elearning_back', array('id_user_moodle' => $user->id, 'id_curso_moodle' => $course->id));

            //creo arreglo para cursos ilerning
            //si tiene registro de cursos ilerning registrado lleno el arreglo
            if (!empty($get_ilerning_enrol)) {
                $modinfo = get_fast_modinfo($course);
                $configilerning = get_config('local_download_cert', 'completion_mod_ilerning');
                $configilerning = (!empty($configilerning)) ? $configilerning : 'feedback';
                $glossarymods = $modinfo->get_instances_of($configilerning);
                foreach ($glossarymods as $glossarymod) {
                    if ($glossarymod->deletioninprogress == 0 && $glossarymod->visible == 1) {
                        $activitycompletion = \core_completion\privacy\provider::get_activity_completion_info($user, $course, $glossarymod);
                        if ($activitycompletion->completionstate == 1) {
                            $completionbool = true;
                        } else {
                            $completionbool = false;
                            break;
                        }
                    }
                }
                $is_ilerning = true;
            } else {
                //si no es ilerning es presencial
                //creo arreglo para cursos presenciales
                $completion_type = \local_download_cert\download_cert_utils::completion_course_type($course->id, 'presencial');

                if (($completion_type == true || $completion_type == false) && $completionattendance == true) {
                    $completionbool = true;
                }
            }
        }
        return array(
            'completionbool' => $completionbool,
            'activitycompletion' => $activitycompletion,
            'completion_type' => $completion_type,
            'is_ilerning' => $is_ilerning
        );
    }

    /*
    Criterios de completado segun tipo de curso
    elearning: completar el quiz y la encuesta del curso
    presencial: completar el quiz y tener la asistencia del curso
    streaming: completar el quiz y tener la asistencia del curso
    */
    public static function completion_types($course, $user){
        global $DB;
        if(\local_mutual\front\utils::is_course_elearning($course) == true){
            
            $completion_feedback = \local_pubsub\metodos_comunes::complete_feedback($user, $course, 'feedback');
            $completion_questionaire = \local_pubsub\metodos_comunes::complete_feedback($user, $course, 'questionnaire');
            if(empty($data->final_date) && ($completion_feedback == true || $completion_questionaire == true)){
                return false;
            } else {
                return true;
            }
        } else {
            $last_sesion = \local_pubsub\metodos_comunes::get_last_date_session_user($USER->id, $course->id);
            
            if ($completion_type == true  && !empty($last_sesion)) {
                return true;
            } else {
                return '';
            }
        }
        
    }

    public static function curso_aprobado($courseid, $userid = null)
    {
        global $DB, $USER, $CFG;
        require_once($CFG->libdir . '/grade/grade_item.php');
        require_once($CFG->libdir . '/grade/grade_grade.php');
        require_once($CFG->libdir . '/grade/constants.php');

        $userid = ($userid) ? $userid : $USER->id;

        $completion = false;
        //nota de aprobado de curso
        $gradeitemparamscourse = [
            'itemtype' => 'course',
            'courseid' => $courseid,
        ];
        $grade_course = \grade_item::fetch($gradeitemparamscourse);


        if (!empty($grade_course)) {
            $grades_user = \grade_grade::fetch_users_grades($grade_course, array($userid), false);

            if (!empty($grades_user)) {
                $finalgradeuser = $grades_user[key($grades_user)]->finalgrade;
                if (!empty($finalgradeuser)) {

                    if (floatval($finalgradeuser) >= floatval($grade_course->gradepass)) {
                        //aprobado
                        return true;
                    } else {
                        //nota menor reprobado
                        return false;
                    }
                }
            } else {
                //no tiene configurada la nota
                return false;
            }
        } else {
            return false;
        }
    }


    public static function get_last_session_user($userid, $courseid){
        global $DB;
        $sql = "SELECT MAX(ats.sessdate) as sessdate, ats.id
        FROM {course} AS c
        JOIN {groups} AS g ON g.courseid = c.id
        JOIN {groups_members} AS m ON g.id = m.groupid
        JOIN {user} AS u ON m.userid = u.id
        JOIN {eabcattendance_sessions} as ats ON ats.groupid = g.id
        JOIN {eabcattendance_log} AS l ON (l.sessionid = ats.id AND l.studentid = ".$userid.")
        JOIN {eabcattendance_statuses} as ates ON l.statusid = ates.id 
        JOIN {format_eabctiles_closegroup} as cg ON (cg.groupid = g.id AND cg.status = 1)
        WHERE c.id = " . $courseid . " AND u.id = " . $userid . " ";
        $date = $DB->get_record_sql($sql);
        if(empty($date)){
            return null;
        } else {
            return $date;
        }
    }

    public static function is_enrol_elearning($userid, $courseid){
        global $DB;
        $bool = false;
        $get_elerning_enrol = $DB->get_records('inscripcion_elearning_back', array('id_user_moodle' => $userid, 'id_curso_moodle' => $courseid));
        if(!empty($get_elerning_enrol)){
            return $get_ilerning_enrol;
        } else {
            return null;
        }
    }

    

    public static function complete_mod($user, $course, $mod_type){
        global $DB;
        $completionbool = false;
        $modinfo = get_fast_modinfo($course->id);
        $mods = $modinfo->get_instances_of($mod_type);

        if(!empty($mods)){
            foreach ($mods as $mod) {
                if ($mod->deletioninprogress == 0 && $mod->visible == 1) {
                    $activitycompletion = \core_completion\privacy\provider::get_activity_completion_info($user, $course, $mod);
                    if ($activitycompletion->completionstate == COMPLETION_TRACKING_MANUAL || $activitycompletion->completionstate == COMPLETION_COMPLETE_PASS) {
                        $completionbool = true;
                    } else {
                        $completionbool = false;
                        break;
                    }
                }
            }
        }
        return $completionbool;;
    }

    public static function curso_aprobado_mod($courseid, $mod)
    {
        global $DB, $USER, $CFG;
        require_once($CFG->libdir . '/grade/grade_item.php');
        require_once($CFG->libdir . '/grade/grade_grade.php');
        require_once($CFG->libdir . '/grade/constants.php');
        $abrobado = false;
        //nota de aprobado de curso
        $gradeitemparamsmod = [
            'itemtype' => 'mod',
            'courseid' => $courseid,
            'itemmodule' => $mod,
        ];
        $grade_mods = \grade_item::fetch_all($gradeitemparamsmod);

        foreach ($grade_mods as $grade_mod) {
            if (!empty($grade_mod)) {

                $grades_user = \grade_grade::fetch_users_grades($grade_mod, array($USER->id), false);
                if (!empty($grades_user)) {
                    $finalgradeuser = $grades_user[key($grades_user)]->finalgrade;
                    if (!empty($finalgradeuser)) {
                        if (floatval($finalgradeuser) >= floatval($grade_mod->gradepass)) {
                            //aprobado
                            $abrobado = true;
                        } else {
                            //nota menor reprobado
                            $abrobado = false;
                        }
                    } else {
                        //no tiene nota
                        $abrobado = false;
                    }
                } else {
                    //no tiene configurada la nota
                    $abrobado = false;
                }
            } else {
                $abrobado = false;
            }
            /* echo "<br>=============mod===================<br>";
            echo print_r($mod, true);
            echo "<br>============finalgradeuser====================<br>";
            echo print_r($finalgradeuser, true);
            echo "<br>=============grade_mod->gradepass===================<br>";
            echo print_r($grade_mod->gradepass, true); */
        }
        return $abrobado;
    }

    public static function complete_mod_grade($user, $course, $mod_type){
        global $DB;
        $completionbool = false;
        $modinfo = get_fast_modinfo($course->id);
        $mods = $modinfo->get_instances_of($mod_type);

        if(!empty($mods)){
            foreach ($mods as $mod) {
                if ($mod->deletioninprogress == 0 ) {
                    
                }
            }
        }
        return $completionbool;;
    }

    public static function criterios_completado_certificado($course, $user){
        //Si el curso es presencial o streaming el criterio sera el quiz o tarea
        //si el curso es elearning el criterio sera solo quiz
        $completion_quiz = \local_resumencursos\utils\summary_utils::complete_mod($user, $course, 'quiz');

        
        /* if ((\local_mutual\front\utils::is_course_streaming($course->id) == true) || (\local_mutual\front\utils::is_course_presencial($course->id) == true)) {
            $completion_assign = \local_resumencursos\utils\summary_utils::complete_mod($user, $course, 'assign');
            //si completo quiz o la tarea retorno true si no retorno false
            if($completion_quiz == true || $completion_assign == true){
                return true;
            } else {
                return false;
            }
        } else {
            return $completion_quiz;
        } */
        if ((\local_mutual\front\utils::is_course_streaming($course->id) == true) || (\local_mutual\front\utils::is_course_presencial($course->id) == true)) {
            $completion_assign = self::curso_aprobado_mod($course->id, "assign");
            $completion_attendance = \local_download_cert\download_cert_utils::completion_attendance($course->id);
            
            //si completo quiz o la tarea retorno true si no retorno false
            if(($completion_quiz == true || $completion_assign == true ) && $completion_attendance == true){
                return true;
            } else {
                return false;
            }
        } else {
            return $completion_quiz;
        }

        
    }

    public static function completado_aprobado($course, $user){
        $curso_aprobado = \local_resumencursos\utils\summary_utils::curso_aprobado($course->id);
        $curso_crit = \local_resumencursos\utils\summary_utils::criterios_completado_certificado($course, $user);

        // Permiso para descargar reporte con calificación, si se activa.
        $allowdownloadwithgrade = get_config(
            'local_resumencursos', 'allow_download_with_grade'
        );
        if ($allowdownloadwithgrade == true && $curso_aprobado == true) {
            $coursecontext = \core\context\course::instance($course->id, IGNORE_MISSING);
            if ($coursecontext && is_enrolled($coursecontext, $user)) {
                return true;
            }
        }

        if ($curso_aprobado == true && $curso_crit == true) {
            return true;
        } else if ($curso_aprobado == true) {
            // Verificar solo completado de curso en 35.
            $completion35 = static::get_completado_aprobado_35($course, $user);
            if ($completion35 && !empty($completion35->timecompleted)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Return the course completion record or true if the user has completed the course.
     * 
     * If the user has completed the course, the function will return the completion record.
     * If the user has not completed the course but the config "allow_download_with_grade" is true,
     * the function will return true.
     *
     * If the user has not completed the course and the config "allow_download_with_grade" is false,
     * the function will return false.
     * 
     * @param stdClass $course The course object.
     * @param stdClass $user The user object.
     * @return stdClass|bool The completion record or true/false.
     */
    public static function get_course_completion($course, $user) {
        global $DB;

        $allowdownloadwithgrade = static::allow_download_with_grade();

        $completion = $DB->get_record(
            'course_completions', array('userid' => $user->id, 'course' => $course->id)
        );

        if (!empty($completion->timecompleted)) {
            return $completion;
        } else if ($allowdownloadwithgrade) {
            return true;
        }

        $completion35 = static::get_completado_aprobado_35($course, $user);
        if ($completion35 && !empty($completion35->timecompleted)) {
            return $completion35;
        }

        return $completion;
    }

    /**
     * Verifica si el usuario ha completado el curso en la BD externa (Moodle 3.5).
     * Si la opción "use_external_completion" está activada, se verifica si el usuario ha completado el curso en la BD externa.
     * Si no hay un registro de completado en la BD externa, se sigue con la lógica local.
     * 
     * @param stdClass $course The course object.
     * @param stdClass $user The user object.
     * @return stdClass|bool The completion record or false if not found.
     */
    public static function get_completado_aprobado_35($course, $user) {
        $useexternalcompletion = get_config('local_resumencursos', 'use_external_completion');

        if ($useexternalcompletion) {
            try {
                $db35 = \local_resumencursos\utils\local_external_db_connection::get_moodle35_connection();
                if ($db35) {
                    $sql = "SELECT cc.id, cc.timecompleted
                              FROM {course_completions} cc
                              JOIN {course} c ON c.id = cc.course
                              JOIN {user} u ON u.id = cc.userid
                             WHERE u.username = :username
                                   AND c.shortname = :shortname
                                   AND u.deleted = 0
                                   AND u.mnethostid = 1";

                    $params = ['username' => $user->username, 'shortname' => $course->shortname];
                    $externalcompletion = $db35->get_record_sql($sql, $params);

                    if (
                            !empty($externalcompletion) && 
                            !empty($externalcompletion->timecompleted)
                        ) {
                        return $externalcompletion; // El curso está completado en la BD externa.
                    }
                }
            } catch (\Exception $e) {
                // Si hay un error con la BD externa (ej. no configurada), se ignora y se sigue con la lógica local.
                // Opcionalmente, puedes registrar este error.
                // debugging($e->getMessage(), DEBUG_DEVELOPER);
                return false;
            }
        }

        return false;
    }

    /**
     * Check if the configuration "allow_download_with_grade" is enabled.
     *
     * @return bool True if the configuration is enabled, false otherwise.
     */
    public static function allow_download_with_grade() {
        $allowdownloadwithgrade = get_config(
            'local_resumencursos', 'allow_download_with_grade'
        );
        if ($allowdownloadwithgrade) {
            return true;
        }
        return false;
    }
}
