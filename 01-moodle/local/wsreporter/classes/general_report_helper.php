<?php
namespace local_wsreporter;

defined('MOODLE_INTERNAL') || die();

class general_report_helper {

    /**
     * Obtiene los datos del reporte, incluyendo usuarios inscritos, cursos completados,
     * usuarios enviados y matrículas pendientes aprobadas en las últimas 24 horas.
     *
     * @return array Datos del reporte.
     */
    public static function get_report_data() {

        $data = [];

        $data['enrolled_users_24h'] = self::get_enrolled_users_24h();
        $data['completed_courses_24h'] = self::get_completed_courses_24h();
        $data['completed_users_sent_24h'] = self::get_completed_users_sent_24h();
        $data['pending_enrollments_approved'] = self::get_pending_enrollments_approved();

        return $data;
    }

    /**
     * Cuenta los usuarios inscritos en las últimas 24 horas.
     *
     * @return int Número de usuarios inscritos.
     */
    private static function get_enrolled_users_24h() {
        global $DB;
        $sql = "SELECT IFNULL(COUNT(ieb.id), 0)
                  FROM {inscripcion_elearning_back} ieb
                 WHERE UNIX_TIMESTAMP(ieb.createdat) 
                 BETWEEN (UNIX_TIMESTAMP() - (24*60*60)) AND UNIX_TIMESTAMP()";

        return (int) $DB->get_field_sql($sql);
    }

    /**
     * Cuenta los cursos completados en las últimas 24 horas.
     *
     * @return int Número de cursos completados.
     */
    private static function get_completed_courses_24h() {
        global $DB;
        $sql = "SELECT IFNULL(COUNT(cp.timecompleted), 0)
                  FROM {course_completions} AS cp
                 WHERE cp.timecompleted IS NOT NULL AND cp.timecompleted 
                 BETWEEN (UNIX_TIMESTAMP() - (24*60*60)) AND UNIX_TIMESTAMP()";
        return (int) $DB->get_field_sql($sql);
    }

    /**
     * Cuenta los usuarios completados y enviados en las últimas 24 horas.
     *
     * @return int Número de usuarios enviados.
     */
    private static function get_completed_users_sent_24h() {
        global $DB;
        $sql = "SELECT IFNULL(COUNT(ceb.id), 0)
                  FROM {cierre_elearning_back_log} ceb
                 WHERE ceb.createdat 
                 BETWEEN (UNIX_TIMESTAMP() - (24*60*60)) AND UNIX_TIMESTAMP()";
        return (int) $DB->get_field_sql($sql);
    }

    /**
     * Cuenta las matrículas pendientes que han sido aprobadas (con nota mayor o igual a 75).
     *
     * @return int Número de matrículas aprobadas pendientes de reporte.
     */
    private static function get_pending_enrollments_approved() {
        global $DB;
        $sql = "SELECT COUNT(*)
                  FROM {inscripcion_elearning_back} ieb
                  JOIN {course_completions} cc ON cc.course = ieb.id_curso_moodle AND cc.userid = ieb.id_user_moodle
                  JOIN {enrol} e ON e.courseid = cc.course
                  JOIN {user_enrolments} mue ON mue.enrolid = e.id AND mue.userid = cc.userid
                  JOIN {grade_items} gi ON gi.courseid = cc.course AND gi.itemtype = 'course'
             LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = cc.userid
                 WHERE ieb.timereported = 0
                   AND cc.timecompleted IS NOT NULL
                   AND gg.finalgrade >= 75";
        return (int) $DB->get_field_sql($sql);
    }
}
