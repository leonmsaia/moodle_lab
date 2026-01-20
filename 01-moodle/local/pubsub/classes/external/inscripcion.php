<?php

namespace local_pubsub\external;

use coding_exception;
use dml_exception;
use external_function_parameters;
use external_single_structure;
use invalid_parameter_exception;
use moodle_exception;
use external_api;

require_once($CFG->libdir . '/externallib.php');

class inscripcion extends external_api
{
    /**
     * @return external_function_parameters
     */
    public static function inscribir_parameters()
    {
        return new external_function_parameters(
            array(
                "data" => new external_single_structure(
                    [
                        "IdInterno" => new \external_value(PARAM_RAW),
                        "IdRolParticipante" => new \external_value(PARAM_RAW),
                        "IdSesion" => new \external_value(PARAM_RAW),
                        "IdSexo" => new \external_value(PARAM_RAW),
                        "NumeroAdherente" => new \external_value(PARAM_RAW),
                        "ParticipanteApellido1" => new \external_value(PARAM_RAW),
                        "ParticipanteApellido2" => new \external_value(PARAM_RAW),
                        "ParticipanteEmail" => new \external_value(PARAM_RAW),
                        "ParticipanteFono" => new \external_value(PARAM_RAW),
                        "ParticipanteIdentificador" => new \external_value(PARAM_RAW),
                        "ParticipanteNombre" => new \external_value(PARAM_RAW),
                        "ParticipantePais" => new \external_value(PARAM_RAW),
                        "ParticipanteTipoDocumento" => new \external_value(PARAM_RAW),
                        "ResponsableEmail" => new \external_value(PARAM_RAW),
                        "ResponsableIdentificador" => new \external_value(PARAM_RAW),
                        "ResponsableNombres" => new \external_value(PARAM_RAW)
                    ]
                )
            )
        );
    }

    /**
     * @param $data
     * @return array
     * @throws coding_exception
     * @throws moodle_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function inscribir($data)
    {
        global $DB;
        /** @var external_api $api */
        $api = new class extends external_api {};
        $params = $api::validate_parameters(self::inscribir_parameters(),
            array('data' => $data));
        $externaluser = $params["data"];
        $rut = strtolower(trim($externaluser['ParticipanteIdentificador']));
        $newuserid = null;
        $sql = /** @lang text */
            "SELECT s.*, a.course as course FROM {eabcattendance_sessions} AS s JOIN {eabcattendance} AS a ON a.id = s.eabcattendanceid where s.guid = ?";
        
        $session = $DB->get_record_sql($sql, [$externaluser["IdSesion"]]);
        
        $course = get_course($session->course);
        if (!empty($session->guid) && ($session->groupid != 0)) {
            if ($externaluser['ParticipanteIdentificador'] &&
                $externaluser['ParticipanteNombre'] &&
                $externaluser['ParticipanteApellido1'] &&
                $externaluser['ParticipanteEmail']) {
                $gm = groups_get_members($session->groupid);
                $user = $DB->get_record('user', array('username' => $rut));
                if (empty($user) || !in_array($user, $gm)) {
                    $enrol_passport = false;
                    if($externaluser['ParticipanteTipoDocumento'] == 100){
                        $enrol_passport = true;
                    }
                    $newuserid = \local_pubsub\metodos_comunes::register_participants($rut, $externaluser, $course, $session, $enrol_passport);
                } else {
                    //Rematricular usuario existente
                    $newuserid = $user->id;
                    try {
                        //si el usuario ya existe o ya esta en el grupo le reinicio las notas en el curso
                        \local_download_cert\download_cert_utils::clear_attemps_course_user($newuserid, $course->id);
                        \local_pubsub\metodos_comunes::clear_attendance_user($newuserid, $course->id);
                        \local_pubsub\metodos_comunes::clean_completion_critery($newuserid, $course->id);
                        \local_pubsub\metodos_comunes::clean_completion_cache_course($newuserid, $course->id);
                        \local_pubsub\metodos_comunes::clear_user_course_data($newuserid, $course->id);
                    } catch (moodle_exception $e) {
                        error_log("Error al limpiar datos del usuario $newuserid en el curso {$course->id}: " . $e->getMessage());
                    }
                    
                }
                \local_pubsub\back\inscripciones::insert_update_inscripciones_back($externaluser, $session->id, $newuserid, $rut);

                \local_pubsub\back\inscripciones_masivas::nota_asistencia($newuserid,$externaluser["IdSesion"],$externaluser['ParticipanteIdentificador'],$externaluser['IdInterno']);

            } else {
                $event = \local_pubsub\event\session_participants::create(
                    array(
                        'context' => \context_system::instance(),
                        'other' => array(
                            'error' => 'parametros obligatorios ParticipanteIdentificador, ParticipanteNombre, ParticipanteApellido1, ParticipanteEmail',
                        ),
                    )
                );
                $event->trigger();
                throw new moodle_exception('parametros obligatorios ParticipanteIdentificador, ParticipanteNombre, ParticipanteApellido1, ParticipanteEmail');
            }
        }

        return [
            "rut" => $rut,
            "userid" => $newuserid
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function inscribir_returns()
    {
        return new external_single_structure(
            array(
                'rut' => new \external_value(PARAM_RAW, 'rut inscripto'),
                'userid' => new \external_value(PARAM_RAW, 'userid o null si el usuario ya estaba creado'),
            )
        );
    }
}