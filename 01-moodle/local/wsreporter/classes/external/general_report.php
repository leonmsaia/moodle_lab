<?php
namespace local_wsreporter\external;

defined('MOODLE_INTERNAL') || die();


use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_wsreporter\general_report_helper;

class general_report extends external_api {
    /**
     * @return external_function_parameters
     */
    public static function get_data_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * @return external_single_structure
     */
    public static function get_data_returns() {
        return new external_single_structure(
            [
                'enrolled_users_24h' => new external_value(PARAM_INT, 'Cantidad de alumnos inscritos (solo e-learning, últimas 24 horas)'),
                'completed_courses_24h' => new external_value(PARAM_INT, 'Alumnos con cursos finalizados (ultimas 24hs e-learning)'),
                'completed_users_sent_24h' => new external_value(PARAM_INT, 'Cantidad de usuarios que finalizaron cursos e-learning enviados al back (ultimas 24hs solo e-learning)'),
                'pending_enrollments_approved' => new external_value(PARAM_INT, 'Total de inscripciones con calificaciones aprobadas pendientes de envio a back'),
            ]
        );
    }

    /**
     * @return array
     */
    public static function get_data() {
        require_capability('local/wsreporter:view', context_system::instance());
        return general_report_helper::get_report_data();
    }
}
