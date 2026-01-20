<?php

namespace local_cron;

use mysqli_native_moodle_database;
use stdClass;

global $CFG;
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/grade/grade_grade.php');

class utils
{
    const STATUS_APROBADO = 'Aprobado';
    const STATUS_REPROBADO_INASISTENCIA = "Reprobado por inasistencia";
    const STATUS_REPROBADO = "Reprobado";
    /**
     * Reseteo de datos de usuario en curso
     * @param $userid 
     * @param $courseid
     */
    public static function clear_user_course_data($userid, $courseid)
    {
        /** @var \moodle_database $DB */
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');
        $key = $userid . '_' . $courseid;
        $completioncache = \cache::make('core', 'completion');
        $completioncache->delete($key);

        $cache = \cache::make('core', 'coursecompletion');
        $cache->delete($key);

        //limpiar criterios de completado de actividad por curso
        $completioncrit = $DB->get_records('course_completion_crit_compl', array('userid' => $userid, 'course' => $courseid));
        if ($completioncrit) {
            $DB->delete_records("course_completion_crit_compl", array('userid' => $userid, 'course' => $courseid));
        }

        $DB->delete_records('course_completions', array('userid' => $userid, 'course' => $courseid));
        $DB->set_field('inscripcion_elearning_back', 'timereported', 0, array('id_curso_moodle' => $courseid, 'id_user_moodle' => $userid));

        $modinfo = \get_fast_modinfo($courseid);
        $cms = $modinfo->get_cms();
        foreach ($cms as $cm) {
            //limpiar intentos de completado de actividad scomr
            if ($cm->modname == "scorm") {
                $atemps_scroms = scorm_get_all_attempts($cm->instance, $userid);
                $scorm = $DB->get_record('scorm', array('id' => $cm->instance));
                foreach ($atemps_scroms as $atemps_scrom) {
                    scorm_delete_attempt($userid, $scorm, $atemps_scrom);
                }
            }
            //limpiar intentos de completado de actividad quiz
            if ($cm->modname == "quiz") {
                quiz_delete_user_attempts_user($cm->instance, $userid);
            }
        }
        //limpiar registros de local cron
        $DB->delete_records("mutual_log_local_cron", array('userid' => $userid, 'courseid' => $courseid));
    }

    /**
     * Devuelve las matriculaciones hechas por cron y que aun no tiene un registro de estado
     * @return array
     */
    public static function get_cron_enrolments()
    {
        /** @var \moodle_database $DB */
        global $DB;
        $enrollmentssql = "
                            SELECT ue.*, e.courseid FROM {user_enrolments} as ue 
                            JOIN {enrol} as e ON ue.enrolid = e.id 
                            join {user} u on u.id = ue.userid
                            left join {mutual_log_local_cron} llc on llc.userid = u.id and llc.courseid = e.courseid
                            where u.idnumber is not null and u.idnumber != '' and llc.id is null";
        return $DB->get_records_sql($enrollmentssql);
    }

    /**
     * Evalua si es una inscripcion creada por ws de cron y no fue registrado aun en la tabla mutual_log_local_cron
     * @param int|stdClass $userorid
     * @param int $courseid
     * @return bool
     */
    public static function is_cron_inscription($userorid, $courseid)
    {
        /** @var \moodle_database $DB */
        global $DB;

        if ($userorid instanceof stdClass) {
            $user = $userorid;
        } else {
            $user = $DB->get_record('user', array('id' => $userorid));
        }

        return !$DB->record_exists('mutual_log_local_cron', array('userid' => $user->id, 'courseid' => $courseid))
            && $user->idnumber;
    }


    /**
     * @param int $fecha_inicial
     * @param int $fecha_final
     * @return float|int
     */
    public static function interval($fecha_inicial, $fecha_final)
    {
        if ($fecha_inicial > $fecha_final) {
            return 0;
        }
        $dias = ($fecha_final - $fecha_inicial) / 86400; //86400 0 1 día
        $days = floor($dias);
        return $days;
    }

    /**
     * Devuelve un objeto con el estado del usuario dentro del curso
     * @param int|object $userorid
     * @param int|object $courseorid
     * @param int $enroltimecreated
     * @param int $timelimit
     */
    public static function get_user_course_status($userorid, $courseorid, $enroltimecreated, $timelimit, $timecreated = null)
    {
        /** @var \mysqli_native_moodle_database $DB */
        global $DB, $CFG;

        $today = time();

        if ($userorid instanceof stdClass) {
            $user = $userorid;
        } else {
            $user = $DB->get_record('user', array('id' => $userorid));
        }

        if ($courseorid instanceof stdClass) {
            $course = $courseorid;
        } else {
            $course = $DB->get_record('course', array('id' => $courseorid));
        }
        $days_passed_enrol = \local_cron\utils::interval($enroltimecreated, $today);
        $course_completion = $DB->get_record(
            'course_completions',
            array(
                'userid' => $user->id,
                'course' => $course->id
            )
        );
        var_dump($course_completion);
        if (!empty($course_completion->timecompleted)) {
            $days_passed_completion = \local_cron\utils::interval($timecreated, $course_completion->timecompleted);
        }

        $grade = self::get_grade_user_course($user, $course);

        $ret = [
            "courseid" => $course->id,
            "userid" => $user->id,
            "date" => $today,
            "grade" => $grade->grade,
            "gradepass" => $grade->gradepass
        ];

        $gradevalue = $CFG->gradevalue;
        $gradepassed = false;

        if (!empty($grade->grade)) {
            if (floatval($grade->grade) >= floatval($grade->gradepass)) {
                $gradepassed = true;
            }
        }


        // si la fecha desde su matriculacion esta dentro de los dias permitidos, o si su finalizacion de curso esta entre los dias permitidos
        if (
            $days_passed_enrol <= $timelimit || (!empty($gradevalue) && $gradepassed)
            || (!empty($course_completion->timecompleted) && $days_passed_completion <= $timelimit)
        ) {

            var_dump($ret);
            //si termino el curso
            if (!empty($course_completion->timecompleted && $timecreated < $course_completion->timecompleted)) {
                //valido si el usuario tiene finalgradeuser para evaluar aparobado o desaprobado
                if ($gradepassed) {
                    $status = self::STATUS_APROBADO;
                } else {
                    $status = self::STATUS_REPROBADO;
                }
            } else {
                //usuario en curso
                return [];
            }
        } else {
            // se valida si tiene calificacion aunque haya pasado los 30 dias
            if (!empty($grade->grade) && !$gradepassed) {
                $status = self::STATUS_REPROBADO;
            } else {
                $status = self::STATUS_REPROBADO_INASISTENCIA;
            }
        }
        $ret["status"] = $status;
        return (object)$ret;
    }

    /**
     * Obtiene la calificacion y la calificacion para aprobar de un usuario en un curso dado
     * @param int|object $user
     * @param int|object $course
     * @return object
     */
    public static function get_grade_user_course($user, $course)
    {
        /** @var \mysqli_native_moodle_database $DB */
        global $DB;
        if (!($user instanceof stdClass)) {
            $user = $DB->get_record('user', array('id' => $user));
        }

        if (!($course instanceof stdClass)) {
            $course = $DB->get_record('course', array('id' => $course));
        }

        $gradeitemparamscourse = [
            'itemtype' => 'course',
            'courseid' => $course->id,
        ];
        $grade_course = \grade_item::fetch($gradeitemparamscourse);
        if ($grade_course) {
            $grades_user = \grade_grade::fetch_users_grades($grade_course, array($user->id));
            $grade_user = reset($grades_user);
            $finalgradeuser = floatval($grade_user->finalgrade);
            $gradepass = floatval($grade_course->gradepass);
        } else {
            $finalgradeuser = 0;
            $grades_user = 0;
            $gradepass = 0;
        }

        return (object)[
            "grade" => $finalgradeuser,
            "gradepass" => $gradepass
        ];
    }

    /**
     * Borra los usuarios que estan en la tabla mutual_log_local_cron si la fecha del estado es anterior a la matriculacion nueva
     * Borra inconsistencias de datos que pueden ser causados por una mala configuracion de los cursos
     * Borra tambien usuarios que estan en esta tabla, pero que ya no estan matriculados
     */
    public static function clean_cron_table()
    {
        /** @var mysqli_native_moodle_database $DB */
        global $DB;
        // sql que obtiene los usuarios que se rematricularon pero aun tienen el estado anterior guardado
        $sql = 'select llc.id
              from {user_enrolments} as ue
                 join {enrol} me on ue.enrolid = me.id
                 join {user} u on u.id = ue.userid
                 join {mutual_log_local_cron} llc on llc.userid = ue.userid and me.courseid = llc.courseid
                where ue.timecreated > llc.timemodified';
        $rs = $DB->get_recordset_sql($sql);
        while ($rs->valid()) {
            $current = $rs->current();
            $DB->delete_records('mutual_log_local_cron', array('id' => $current->id));
            $rs->next();
        }
        $rs->close();

        // sql que obtiene las inconsistencias en moodle
        $sql = "select mmllc.id
        from {mutual_log_local_cron} as mmllc
                join {grade_items} mgi on mmllc.courseid = mgi.courseid and mgi.itemtype = 'course'
                join {grade_grades} mgg on mgi.id = mgg.itemid and mgg.userid = mmllc.userid
                left join {enrol} e on e.courseid = mmllc.courseid and e.enrol = 'manual'
                left join {user_enrolments} mue on e.id = mue.enrolid and mue.userid = mmllc.userid
                left join {course_completions} cc on cc.userid = mmllc.userid and cc.course = mmllc.courseid
        where (mgg.finalgrade >= mgi.gradepass and mgg.finalgrade > 0 and (mmllc.status = 'Reprobado' or mmllc.status = 'Reprobado por inasistencia'))
            or (mgg.finalgrade < mgi.gradepass and mmllc.status = 'Aprobado')
            or (mgg.finalgrade < mgi.gradepass and mgg.finalgrade > 0 and  mmllc.status = 'Reprobado por inasistencia') order by mmllc.id desc
        ";
        $rs = $DB->get_recordset_sql($sql);
        while ($rs->valid()) {
            $current = $rs->current();
            $DB->delete_records('mutual_log_local_cron', array('id' => $current->id));
            $rs->next();
        }
        $rs->close();

        // sql que obtiene los usuarios de mutual_log_local_cron que ya no estan matriculados
        $sql = "select mllc.id
                from {mutual_log_local_cron} as mllc
                left join {enrol} me on mllc.courseid = me.courseid and me.enrol = 'manual'
                left join {user_enrolments} mue on me.id = mue.enrolid and mue.userid = mllc.userid
                where mue.id is null";
        $rs = $DB->get_recordset_sql($sql);
        while ($rs->valid()) {
            $current = $rs->current();
            $DB->delete_records('mutual_log_local_cron', array('id' => $current->id));
            $rs->next();
        }
        $rs->close();
    }
}
