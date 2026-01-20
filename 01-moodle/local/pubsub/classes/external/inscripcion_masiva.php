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

class inscripcion_masiva extends external_api
{
    /**
     * @return external_function_parameters
     */
    public static function inscribir_masiva_parameters()
    {

        return new external_function_parameters(
            array(
                "IdentificadorProceso" => new external_value(PARAM_RAW),
                "IdEvento" => new external_value(PARAM_RAW),
                "IdSesion" => new external_value(PARAM_RAW),
                "IdentificadorParticipante" => new external_value(PARAM_RAW),
                "IdRegistroDynamics" => new external_value(PARAM_RAW),
                "Operacion" => new external_value(PARAM_RAW),
                "Resultado" => new external_value(PARAM_RAW),
                "Mensaje" => new external_value(PARAM_RAW),
            )
        );
    }

    /**
     * @param $IdentificadorProceso
     * @param $IdEvento
     * @param $IdSesion
     * @param $IdentificadorParticipante
     * @param $IdRegistroDynamics
     * @param $Operacion
     * @param $Resultado
     * @param $Mensaje
     * @return array
     * @throws coding_exception
     * @throws moodle_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function inscribir_masiva($IdentificadorProceso,$IdEvento,$IdSesion,$IdentificadorParticipante,$IdRegistroDynamics,$Operacion,$Resultado,$Mensaje)
    {
        global $DB;

        $params = self::validate_parameters(
            self::inscribir_masiva_parameters(),
            array(
                "IdentificadorProceso" => $IdentificadorProceso,
                "IdEvento" => $IdEvento,
                "IdSesion" => $IdSesion,
                "IdentificadorParticipante" => $IdentificadorParticipante,
                "IdRegistroDynamics" => $IdRegistroDynamics,
                "Operacion" => $Operacion,
                "Resultado" => $Resultado,
                "Mensaje" => $Mensaje,
            )
        );
        
        $conditions = [
            'numero_documento' => $params['IdentificadorParticipante'],
            'guid_sesion' => $params['IdSesion'],
            'guid_evento' => $params['IdEvento'],
            'identificador_proceso' => $params['IdentificadorProceso'],
        ];

        $record = $DB->get_record('eabcattendance_carga_masiva', $conditions);

        if($record){
            $userId     = $record->user_id;

            try {
                if ($params['Resultado'] != 0){
                    $recordUpdateError = new \stdClass;
                    $recordUpdateError->id = $record->id;
                    $recordUpdateError->recibido = 1;
                    $recordUpdateError->fecha_recibido = date("Y-m-d H:i:s");
                    $recordUpdateError->resultado = $params['Resultado'];
                    $recordUpdateError->mensaje = $params['Mensaje'];
        
                    $DB->update_record('eabcattendance_carga_masiva', $recordUpdateError);
                    throw new moodle_exception("error_incripcion_masiva", "local_pubsub", '', 'Error recibido del Back dynamics inscripcion participante carga masiva', $params['Mensaje']);
                }

                if($Operacion == 'U' && $Resultado == 0){
                    $newuserid = $userId;
                    $userUpdate = new \stdClass;
                    $userUpdate->id = $newuserid;
                    $userUpdate->firstname = $record->nombres;
                    $userUpdate->lastname = $record->apellido_paterno;
                    $userUpdate->email = $record->correo;
                    
                    $DB->update_record('user', $userUpdate);
                    \local_pubsub\back\inscripciones_masivas::nota_asistencia($userId,$IdSesion,$IdentificadorParticipante,$IdRegistroDynamics);
                }
                
                set_user_preference('migrado45', 2, $userId);
                $recordUpdate = new \stdClass;
                $recordUpdate->id = $record->id;
                $recordUpdate->recibido = 1;
                $recordUpdate->fecha_recibido = date("Y-m-d H:i:s");
                $recordUpdate->id_inscripcion_dynamics = $params['IdRegistroDynamics'];
                $recordUpdate->user_id = $userId;
                $recordUpdate->resultado = $params['Resultado'];
                $recordUpdate->mensaje = $params['Mensaje'];
    
                $DB->update_record('eabcattendance_carga_masiva', $recordUpdate);
    
                return [
                    'IdentificadorProceso' => $params['IdentificadorProceso'],
                    'IdEvento' => $params['IdEvento'],
                    'IdSesion' => $params['IdSesion'],
                    'IdRegistroDynamics' => $params['IdRegistroDynamics'],
                ];
                
            }
            catch (moodle_exception $e) {
                $errormsg = 'Error writing to database: ' . $e->getMessage();
                debugging($errormsg, DEBUG_DEVELOPER);
            
                $combinedmsg = $errormsg . "\nDebug info: " . $e->debuginfo;
                throw new moodle_exception("error_incripcion_masiva", "local_pubsub", '', $combinedmsg);
            }
        }else{
            throw new moodle_exception("error_incripcion_masiva", "local_pubsub", '', 'NO SE ENCONTRO EL REGISTRO EN LA TABLA eabcattendance_carga_masiva','');
        }
    }

    /**
     * @return external_single_structure
     */
    public static function inscribir_masiva_returns()
    {
        return new external_single_structure(
            array(
                "IdentificadorProceso" => new external_value(PARAM_TEXT, "Identificador del proceso"),
                "IdEvento" => new external_value(PARAM_TEXT, "Id del evento"),
                "IdSesion" => new external_value(PARAM_TEXT, "Id de la sesión"),
                "IdRegistroDynamics" => new external_value(PARAM_TEXT, "Id de registro de Dynamics"),
            )
        );
    }
}