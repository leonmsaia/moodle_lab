<?php

namespace local_pubsub\task;

use local_pubsub\sistema_get;
use WindowsAzure\ServiceBus\Models\ReceiveMessageOptions;
use moodle_exception;
use local_pubsub\metodos_comunes;

class register_facilitator extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return 'Registrar facilitador';
        
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB;
        echo 'registrar facilitador';
        $conectionstring = get_config('local_pubsub', 'conexionhighfacilitators');
        $subscription = get_config('local_pubsub', 'subscription');
        $subscriptionkey = get_config('local_pubsub', 'subscriptionkey');
        $endpoint = get_config('local_pubsub', 'endpointgetfacilitators');
        $topic = get_config('local_pubsub', 'topichighfacilitators');
        $tokenapi = get_config('local_pubsub', 'tokenapi');

        if (empty($conectionstring)) {
            print_error(get_string('configempty', 'local_pubsub', 'conexionhighfacilitators'));
        }

        if (empty($subscriptionkey)) {
            print_error(get_string('configempty', 'local_pubsub', 'subscriptionkey'));
        }
        
        if (empty($subscription)) {
            print_error(get_string('subscription-empty', 'local_pubsub'));
        }

        if (empty($topic)) {
            print_error(get_string('configempty', 'local_pubsub', 'topichighfacilitators'));
        }

        if (empty($endpoint)) {
            print_error(get_string('configempty', 'local_pubsub', 'endpointgetfacilitators'));
        }
        
        $msg = sistema_get::get_facilitators($conectionstring, $topic, $subscription, $endpoint, $tokenapi, $subscriptionkey);
        
//        $arraymsg = array(
//            "ApellidoMaterno" => '',
//            "ApellidoPaterno" => '',
//            "CorreoElectronico" => 'secertariadecolegiosancarlos@gmail.com',
//            "DV" => '3',
//            "Id" => 'fdf44ba3-4f53-ea11-a812-000d3a4f6c1a',
//            "Modalidad" => '201320000',
//            "Nombre" => '',
//            "Rut" => '16100195',
//            "Telefono" => '225862514',
//            "TipoFacilitador" => '100000000',
//        );
//        $msg = new \stdClass();
//        $msg->mensaje = $arraymsg;
//        $msg->IdInterno = 'fdf44ba3-4f53-ea11-a812-000d3a4f6c1a';
//        $msg->Identificador = '16100195-3';
//        $msg->Modalidad = '201320000';
//        $msg->TipoFacilitador = '100000000';
        
        
//        echo "<pre>" . print_r($msg, true) . "</pre>";
        $role = get_config("eabcattendance", "rolwscreateactivity");
        
        while($msg != false){
        
        //id del rol que se creará se tomara de la configuración dle plugin
        //en caso de no configurar nada tomar profesor sin permiso de edición(3)
        //Falta data del usuario
        //Falta data del curso y grupo
            
            
            $rut = strtolower(trim($msg->Identificador));
            
            if(
               !empty($rut) && 
               !empty($msg->mensaje['Nombre']) && 
               !empty($msg->mensaje['ApellidoPaterno']) && 
               !empty($msg->mensaje['CorreoElectronico']) 
              ){
                $createuser = array(
                    'username' => $rut,
                    'firstname' => $msg->mensaje['Nombre'],
                    'lastmame' => $msg->mensaje['ApellidoPaterno'],
                    'email' => $msg->mensaje['CorreoElectronico'],
                    'sexo' => null,
                    'nombre_adherente' => null,
                    'rut_adherente' => null,
                    'participantefechanacimiento' => null,
                    'roles' => null,
                );
                $newuserid = metodos_comunes::create_user($createuser);
                
//              metodos_comunes::enrol_user($course, $newuserid, $gid, $roleid);
            } else {
                $event = \local_pubsub\event\register_facilitator::create(
                                array(
                                    'context' => \context_system::instance(),
                                    'other' => array(
                                        'error' => get_string('validatedatareisterfacilitator', 'local_pubsub'),
                                        'Identificador' => $msg->Identificador,
                                        'Nombre' => $msg->mensaje['Nombre'],
                                        'ApellidoPaterno' => $msg->mensaje['ApellidoPaterno'],
                                        'CorreoElectronico' => $msg->mensaje['CorreoElectronico'],
                                    ),
                                )
                );
                $event->trigger();
            }
            $msg = sistema_get::get_facilitators($conectionstring, $topic, $subscription, $endpoint, $tokenapi, $subscriptionkey);
        }
    }

}
