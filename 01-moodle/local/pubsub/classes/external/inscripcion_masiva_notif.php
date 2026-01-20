<?php

namespace local_pubsub\external;

use coding_exception;
use dml_exception;
use external_function_parameters;
use external_single_structure;
use invalid_parameter_exception;
use moodle_exception;
use external_api;
use external_value;

require_once(dirname(__FILE__).'/../../../../config.php');
require_once($CFG->libdir . '/externallib.php');

class inscripcion_masiva_notif extends external_api
{
    /**
     * @return external_function_parameters
     */
    public static function inscripcion_masiva_notif_parameters()
    {

        return new external_function_parameters(
            array(
                "IdentificadorProceso" => new external_value(PARAM_RAW),
                "IdEvento" => new external_value(PARAM_RAW),
                "IdSesion" => new external_value(PARAM_RAW),
            )
        );
    }

    /**
     * @param $IdentificadorProceso
     * @param $IdEvento
     * @param $IdSesion
     * @return array
     * @throws coding_exception
     * @throws moodle_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function inscripcion_masiva_notif($IdentificadorProceso,$IdEvento,$IdSesion)
    {
        global $DB;

        $params = self::validate_parameters(
            self::inscripcion_masiva_notif_parameters(),
            array(
                "IdentificadorProceso" => $IdentificadorProceso,
                "IdEvento" => $IdEvento,
                "IdSesion" => $IdSesion,
            )
        );

        try {
            $conditions = [
                'guid_sesion' => $params['IdSesion'],
                'guid_evento' => $params['IdEvento'],
                'identificador_proceso' => $params['IdentificadorProceso'],
            ];
    
            $record = $DB->get_record('eabcattendance_carga_masiva', $conditions);
            
            if (!empty($record)){

                $courseId   = $record->id_curso_moodle;
                $groupId    = $record->id_grupo_moodle;
                $userIdToNot= $record->user_id_upload;

                $course = $DB->get_record('course', array('id' => $courseId));

                $conditionsNotification = [
                    'guid_sesion' => $params['IdSesion'],
                    'guid_evento' => $params['IdEvento'],
                    'recibido' => 0
                ];
        
                $recordNotify = $DB->get_records('eabcattendance_carga_masiva', $conditionsNotification);
                if(empty($recordNotify)){
                    $group = $DB->get_record('groups', array('id' => $groupId));
                    // Enviar notificación al usuario
                    $user = $DB->get_record('user', ['id' => $userIdToNot]);

                    sleep(15);
                    // se modifica el componente y el name para que sea una notificación instantánea
                    $eventdata = new \core\message\message();
                    $eventdata->courseid = SITEID;
                    $eventdata->component = 'moodle';
                    $eventdata->name = 'instantmessage';
                    $eventdata->userfrom = \core_user::get_noreply_user();
                    $eventdata->userto = $user;
                    $eventdata->subject = "INSCRIPCION MASIVA COMPLETADA";
                    $eventdata->fullmessage = "La inscripción en la sesión: ".$group->name ." en el curso ".$course->shortname." ha finalizado, por favor verifique en la sesión que la asistencia y notas esten correctas";
                    $eventdata->fullmessageformat = FORMAT_PLAIN;
                    $eventdata->fullmessagehtml = "La inscripción en la sesión: ".$group->name ." en el curso ".$course->shortname." ha finalizado, por favor verifique en la sesión que la asistencia y notas esten correctas";
                    $eventdata->smallmessage = '';
                    $eventdata->notification = 1;
                    
                    message_send($eventdata);
                }

                return [
                    'IdentificadorProceso' => $params['IdentificadorProceso'],
                    'IdEvento' => $params['IdEvento'],
                    'IdSesion' => $params['IdSesion'],
                ];
            }else{
                throw new moodle_exception("error_incripcion_masiva_notif", "local_pubsub", '', 'NO SE ENCONTRO EL REGISTRO EN LA TABLA eabcattendance_carga_masiva','');
            }
            
        }
        catch (moodle_exception $e) {
            $errormsg = 'Error writing to database: ' . $e->getMessage();
            debugging($errormsg, DEBUG_DEVELOPER);
        
            $combinedmsg = $errormsg . "\nDebug info: " . $e->debuginfo;
            throw new moodle_exception("error_incripcion_masiva_notif", "local_pubsub", '', $combinedmsg);
        }
         
    }

    /**
     * @return external_single_structure
     */
    public static function inscripcion_masiva_notif_returns()
    {
        return new external_single_structure(
            array(
                "IdentificadorProceso" => new external_value(PARAM_TEXT, "Identificador del proceso"),
                "IdEvento" => new external_value(PARAM_TEXT, "Id del evento"),
                "IdSesion" => new external_value(PARAM_TEXT, "Id de la sesión"),
            )
        );
    }
}