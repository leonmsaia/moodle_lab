<?php

namespace local_pubsub\external;

use coding_exception;
use core\plugininfo\enrol;
use dml_exception;
use enrol_manual_enrol_users_form;
use enrol_users_addmember_form;
use external_function_parameters;
use external_single_structure;
use invalid_parameter_exception;
use moodle_exception;
use local_pubsub\utils;
use external_api;

require_once($CFG->libdir . '/externallib.php');

class inscripcion_elearning extends \external_api{
/**
     * @return external_function_parameters
     */
    public static function inscribirelearning_parameters(){
        return new external_function_parameters(
            array(
                "data" => new external_single_structure(
                    [
                        "ParticipanteIdRegistroParticipante"    => new \external_value(PARAM_RAW),
                        "ParticipanteProductId"                 => new \external_value(PARAM_RAW),
                        "ParticipanteFechaInscripcion"          => new \external_value(PARAM_RAW),
                        "ParticipanteNombre"                    => new \external_value(PARAM_RAW),
                        "ParticipanteApellido1"                 => new \external_value(PARAM_RAW),
                        "ParticipanteApellido2"                 => new \external_value(PARAM_RAW),
                        "ParticipanteTipoDocumento"             => new \external_value(PARAM_RAW),
                        "ParticipanteRUT"                       => new \external_value(PARAM_RAW),
                        "ParticipanteDV"                        => new \external_value(PARAM_RAW),
                        "ParticipantePasaporte"                 => new \external_value(PARAM_RAW),
                        "ParticipanteFechaNacimiento"           => new \external_value(PARAM_RAW),
                        "ParticipanteIdSexo"                    => new \external_value(PARAM_RAW),
                        "ParticipanteEmail"                     => new \external_value(PARAM_RAW),
                        "ParticipanteTelefonoMovil"             => new \external_value(PARAM_RAW),
                        "ParticipanteTelefonoFijo"              => new \external_value(PARAM_RAW),
                        "ParticipantePais"                      => new \external_value(PARAM_RAW),
                        "ParticipanteCargo"                     => new \external_value(PARAM_RAW),
                        "ParticipanteIdRol"                     => new \external_value(PARAM_RAW),
                        "ParticipanteCodigoComuna"              => new \external_value(PARAM_RAW),
                        "ParticipanteDireccion"                 => new \external_value(PARAM_RAW),
                        "ParticipanteRutAdherente"              => new \external_value(PARAM_RAW),
                        "ParticipanteDvAdherente"               => new \external_value(PARAM_RAW),
                        "ResponsableNombre"                     => new \external_value(PARAM_RAW),
                        "ResponsableApellido1"                  => new \external_value(PARAM_RAW),
                        "ResponsableApellido2"                  => new \external_value(PARAM_RAW),
                        "ResponsableTipoDocumento"              => new \external_value(PARAM_RAW),
                        "ResponsableRUT"                        => new \external_value(PARAM_RAW),
                        "ResponsableDV"                         => new \external_value(PARAM_RAW),
                        "ResponsablePasaporte"                  => new \external_value(PARAM_RAW),
                        "ResponsableFechaNacimiento"            => new \external_value(PARAM_RAW),
                        "ResponsableIdSexo"                     => new \external_value(PARAM_RAW),
                        "ResponsableEmail"                      => new \external_value(PARAM_RAW),
                        "ResponsableTelefonoMovil"              => new \external_value(PARAM_RAW),
                        "ResponsableTelefonoFijo"               => new \external_value(PARAM_RAW),
                        "ResponsableCargo"                      => new \external_value(PARAM_RAW),
                        "ResponsableCodigoComuna"               => new \external_value(PARAM_RAW),
                        "ResponsableCodigoRegion"               => new \external_value(PARAM_RAW),
                        "ResponsableDireccion"                  => new \external_value(PARAM_RAW),
                    ]
                )
            )
        );
    }

    public static function inscribirelearning($data)
    {
        // @codingStandardsIgnoreLine
        /** @var \moodle_database $DB */

        $tiempo = "DEBUG: CE: ".microtime(true);
        
        global $DB;
        $params = self::validate_parameters(self::inscribirelearning_parameters(),
            array('data' => $data));

        $externaluser = $params["data"];

        if ($externaluser['ParticipanteTipoDocumento'] == 1){
            if (empty($externaluser['ParticipanteRUT'])){
                throw new moodle_exception("El campo ParticipanteRUT no puede ser vacio para TipoDocumento = 1 ");    
            }else{
                $username = strtolower(trim($externaluser['ParticipanteRUT']) . "-" . trim($externaluser['ParticipanteDV']));
            }            
        }else if ($externaluser['ParticipanteTipoDocumento'] == 100) {
            if (empty($externaluser['ParticipantePasaporte'])){
                throw new moodle_exception("El campo ParticipantePasaporte no puede ser vacio para TipoDocumento = 100 ");    
            }else{
                $username = strtolower(trim($externaluser['ParticipantePasaporte']));
            }            
        }else{
            throw new moodle_exception("El campo ParticipanteTipoDocumento no corresponde con un valor aceptado (1 - 100) ");
        }        
        
        $registro = (object) '';
        $estadoInscripcion = 0;
        $observacionInscripcion = '';
        $course_back = $DB->get_record('curso_back',array('productoid'=>$externaluser['ParticipanteProductId'])); 
        if ($course_back){
            $course = get_course($course_back->id_curso_moodle); 
            $courseid =  $course_back->id_curso_moodle;
        }else{
            $course = external_api::call_external_function("local_pubsub_upsert_course", ["ID" => $externaluser["ParticipanteProductId"]]);
            if($course["error"]) {
                throw new \moodle_exception($course);
            }
            $courseid = $course["data"]["moodlecourseid"];            
        }
                              
        if ($externaluser['ParticipanteNombre'] &&
            $externaluser['ParticipanteApellido1'] &&
            $externaluser['ParticipanteEmail']) {
            
            $tiempo .= ", CRIM: ".microtime(true);
            $registro = \local_pubsub\metodos_comunes::register_participant_elearning($username, $externaluser, $courseid, false, $externaluser['ParticipanteIdRegistroParticipante']);
            $tiempo .= ", FIM: ".microtime(true);
            
            if ($registro['estatus']=='nuevo'){
                $estadoInscripcion = 1;
                $observacionInscripcion = 'Registro exitoso';
            }else if ($registro['estatus']=='rematriculado'){
                $estadoInscripcion = 1;
                $observacionInscripcion = 'El Alumno fue Rematriculado';
            }else{
                $estadoInscripcion = 0;
                $observacionInscripcion = 'Alumno ya tiene el curso activo y con estado cursando';                    
            }

            $enrolid = $registro['enrolid']->id;
            $tiempo .= ", CRTIEB: ".microtime(true);
            //cambio en logica guardar solo registros exitosos
            if($registro['estatus'] == 'nuevo' || $registro['estatus'] == 'rematriculado') {
                \local_pubsub\back\inscripcion_elearning::insert_update_inscripciones_elearning_back($externaluser, $courseid, $registro['newuserid']);
            }
            $tiempo .= ", FRTIEB: ".microtime(true);
        } else {
            $observacionInscripcion = 'ERROR: Alguno de estos campos requeridos no estan presentes: ParticipanteNombre || ParticipanteApellido1 || ParticipanteEmail';
        }     

        $hoy = utils::date_utc();
                
        $data = [
            "IdRegistroParticipante" => $externaluser['ParticipanteIdRegistroParticipante'],
            "IdInscripcion" => "m45-".$enrolid, // SE ENVIARA CON UN PREFIJO MAS ADELANTE PARA QUE NO SE REPITA EL ID
            //"IdInscripcion" => $enrolid,
            "EstadoInscripcion" => $estadoInscripcion,
            "FechaInscripcion" => $hoy,
            "Observacion" => $observacionInscripcion
        ];

        // Se envia el resultado al Back
        $endpoint = get_config('local_pubsub', 'endpointupdatepartielearning');  
        if(empty($endpoint)) {
            throw new moodle_exception("Debe configurar el endpoint de Actualiza registro participantes e-learning ");
        }       
           
        $tiempo .= ", CEDB: ".microtime(true);
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, $endpoint);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
                'Authorization: ' . get_config('local_pubsub', 'tokenapi'),
                'Ocp-Apim-Subscription-Key: ' . get_config('local_pubsub', 'subscriptionkey'),
                'Content-Type:application/json'
         ));
         $response = curl_exec($cURLConnection);
         $httpcode = curl_getinfo($cURLConnection, CURLINFO_HTTP_CODE);
         curl_close($cURLConnection);
         $tiempo .= ", FEDB: ".microtime(true);

        if ($httpcode > 299) {
            throw new \Exception(json_encode([
                "response" => $response,
                "data" => $data,
                "endpoint" => $endpoint,
                "ErrorCode" => $httpcode
            ]));
        }

         $tiempo .= ", FE: ".microtime(true);
         $data['tiempo'] = $tiempo;

         if(is_array($registro) && isset($registro['companyid']) && $registro['companyid']) {
            try {
                $get_company_user = $DB->get_record('company_users', array('userid' => $registro['newuserid'], 'companyid' => $registro['companyid']));
                if (empty($get_company_user)) {
                    $dataobject = new \stdClass();
                    $dataobject->userid = $registro['newuserid'];
                    $dataobject->companyid = (int)$registro['companyid'];
                    $dataobject->departmentid = 0;
                    $dataobject->managertype = 0;
                    $DB->insert_record('company_users', $dataobject); 
                }
            } catch(moodle_exception $e) {
                $errormsg = 'Error writing to database: ' . $e->getMessage();
                debugging($errormsg, DEBUG_DEVELOPER);
            }
         }

         return $data;
    }

    /**
     * @return external_single_structure
     */
    public static function inscribirelearning_returns()
    {
        return new external_single_structure(
            array(
                'IdRegistroParticipante' => new \external_value(PARAM_RAW, ' '),
                'IdInscripcion' => new \external_value(PARAM_RAW, ''),
                'EstadoInscripcion' => new \external_value(PARAM_RAW, ''),
                'FechaInscripcion' => new \external_value(PARAM_RAW, ''),
                'Observacion' => new \external_value(PARAM_RAW, ''),
                'tiempo' => new \external_value(PARAM_RAW, ''),
            )
        );
    }
}