<?php

namespace local_pubsub\external;

use dml_exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use moodle_exception;
use local_pubsub\metodos_comunes;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');

class facilitador extends external_api
{
    /**
     * @return external_function_parameters
     */
    public static function create_facilitador_parameters()
    {
        return new external_function_parameters(
            array(
                'ID' => new external_value(PARAM_RAW, 'Id del facilitador'),
            )
        );
    }

    /**
     * @param $id
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function create_facilitador($id)
    {
        global $DB, $CFG;

        include_once($CFG->dirroot.'/user/lib.php');

        $params = self::validate_parameters(
            self::create_facilitador_parameters(), array(
                'ID' => $id,
            )
        );

        $endpoint = get_config('local_pubsub', 'endpointgetfacilitators').$params["ID"];
        $response = \local_pubsub\metodos_comunes::request($endpoint);
        $responsedata = array('response' => json_encode($response));
        metodos_comunes::save_event_response_facilitator(\context_system::instance(), $responsedata);
        if($response["status"] > 299) {
            throw new moodle_exception("error request:".$response["status"]." Endpoint: ".$endpoint);
        }
        
        
        $response = json_decode($response["data"], true);
        $newuserid = null;

        if(!empty($response["error"])) {
            throw new moodle_exception($response["error"]);
        }

        if (!empty($response)) {
            $rut = strtolower($response["Rut"]."-".$response["DV"]);
            $apellido = !empty($response['ApellidoPaterno']) ? $response['ApellidoPaterno'] : $response['ApellidoMaterno'];
            if(
                !empty($response['Nombre']) && 
                !empty($apellido) &&
                !empty($response['CorreoElectronico']) 
               ){
                 $createuser = array(
                     'username' => $rut,
                     'firstname' => $response['Nombre'],
                     'lastname' => $apellido,
                     'email' => $response['CorreoElectronico'],
                     'mnethostid' => 1,
                     'confirmed' => 1,
                     'password' => $rut
                 );

                $get_user = $DB->get_record("user", array("username" => $createuser["username"]));

                if (!empty($get_user)) {
                    //si el usuario ya existe lo actualizo
                    $createuser["id"] = $get_user->id;
                    \user_update_user((object)$createuser);
                    $newuserid = $get_user->id;
                } else {
                    //si el usuario no existe lo creo
                   // print_r($createuser); exit();
                    $newuserid = \user_create_user((object)$createuser);
                }
                //seteo las el guid a las preferencias 
                //metodo set_user_preference crea o actualiza
                set_user_preference('guid_facilitador',$response['Id'],$newuserid);
                // Se registran los datos tal como llega del BAck
                \local_pubsub\back\facilitador::insert_update_facilitador_back($response, $newuserid);

             } else {                 
                 $other = array(
                    'error' => get_string('validatedatareisterfacilitator', 'local_pubsub'),
                    'Identificador' => $response['Identificador'],
                    'Nombre' => $response['Nombre'],
                    'ApellidoPaterno' => $apellido,
                    'CorreoElectronico' => $response['CorreoElectronico'],
                );
                metodos_comunes::save_event_facilitador(\context_system::instance(), $other);
                throw new moodle_exception("Facilitador no encontrado");
             }
        }
        return [
            "moodlefacilitadorid" => $newuserid
        ];
        
    }

    /**
     * @return external_single_structure
     */
    public static function create_facilitador_returns() {
        return new external_single_structure(
            array(
                'moodlefacilitadorid' => new external_value(PARAM_RAW, 'mje facilitador creado'),
            )
        );
    }

}