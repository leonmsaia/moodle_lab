<?php

namespace local_pubsub\task;

use local_pubsub\sistema_get;
use WindowsAzure\ServiceBus\Models\ReceiveMessageOptions;
use moodle_exception;
use \mod_eabcattendance\utils\frontutils;
use local_pubsub\metodos_comunes;
use stdClass;
use local_pubsub\back;

class session_participants extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return 'Participantes por sesion';
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB;
        /* obtener las sesiones de los cursos */
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/lib/enrollib.php');
        require_once($CFG->dirroot . '/lib/datalib.php');
        require_once($CFG->dirroot . '/group/lib.php');
        
        try {
            $transaction = $DB->start_delegated_transaction();
            $subscription = get_config('local_pubsub', 'subscription');
            $endpoint_update_session = get_config('local_pubsub', 'endpointupdatesession');
            $tokenapi = get_config('local_pubsub', 'tokenapi');
            $subscriptionkey = get_config('local_pubsub', 'subscriptionkey');
            $endpointsessions = get_config('local_pubsub', 'endpointsession');
        
            if (empty($subscription)) {
                print_error(get_string('subscription-empty', 'local_pubsub'));
                $event = \local_pubsub\event\session_participants::create(
                                array(
                                    'context' => \context_system::instance(),
                                    'other' => array(
                                        'error' => get_string('subscription-empty', 'local_pubsub')
                                    ),
                                )
                );
                $event->trigger();
            }

            if (empty($endpoint_update_session)) {
                print_error(get_string('validate-endpointupdatesession', 'local_pubsub'));
                $event = \local_pubsub\event\session_participants::create(
                                array(
                                    'context' => \context_system::instance(),
                                    'other' => array(
                                        'error' => 'no se configuro endpointupdatesession'
                                    ),
                                )
                );
                $event->trigger();
            }
            //hora actual menos 3 horas
            $now = time() - (60 * 60 * 3);
            $sql = /** @lang text */
                'SELECT s.*, a.course as course FROM {eabcattendance_sessions} AS s JOIN {eabcattendance} AS a ON a.id = s.eabcattendanceid where s.guid is not null and s.sessdate >= '. $now;
            $get_sessions = $DB->get_records_sql($sql);
            
            foreach ($get_sessions as $get_session) {
                mtrace('sesion');
                mtrace(date('m/d/Y H:i:s', $get_session->sessdate));
                $course = get_course($get_session->course);
                if (!empty($get_session->guid) && ($get_session->groupid != 0)) {
                    
                    $endpoint_participants_session_str = str_replace("{idSesion}", $get_session->guid, $endpoint_update_session);
                    $response = sistema_get::get_request_endpoint($endpoint_participants_session_str, $tokenapi, $subscriptionkey);
                    //consulto data de sesion
                    $endpoint_get_sesion = sistema_get::get_request_endpoint($endpointsessions.$get_session->guid, $tokenapi, $subscriptionkey);
                    
//                    $response = array(
//                        array(
//                            'ParticipanteIdentificador' => '15664979-1',
//                            'ParticipanteNombre' => 'jose',
//                            'ParticipanteApellido1' => 'salgado',
//                            'ParticipanteEmail' => 'salgado@salgado.com',
//                            'IdSexo' => '1',
//                            'ResponsableNombres' => 'test nombre adherente',
//                            'ResponsableIdentificador' => 'test responsable adherente',
//                        ),
//                        array(
//                            'ParticipanteIdentificador' => '17210522-K',
//                            'ParticipanteNombre' => 'jose1',
//                            'ParticipanteApellido1' => 'salgado1',
//                            'ParticipanteEmail' => 'salgado1@salgado.com',
//                            'IdSexo' => '1',
//                            'ResponsableNombres' => 'test nombre adherente',
//                            'ResponsableIdentificador' => 'test responsable adherente',
//                        )
//                    );
                    
                    //validar que el response no tenga errores
                    if (isset($response['error'])) {
                        echo print_r($response['error'], true);
                        $event = \local_pubsub\event\session_participants::create(
                                        array(
                                            'context' => \context_system::instance(),
                                            'other' => $response['error'],
                                        )
                        );
                        $event->trigger();
                    } else {
                        $arrayrutsws = array();
                        //recorro el arreglo de usuarios
                        foreach ($response as $resp) {
                            
                            if ($resp['ParticipanteIdentificador'] &&
                                    $resp['ParticipanteNombre'] &&
                                    $resp['ParticipanteApellido1'] &&
                                    $resp['ParticipanteEmail']) {

                                $rut = strtolower(trim($resp['ParticipanteIdentificador']));
                                
                                $gm = groups_get_members($get_session->groupid);
                                
                                $inarray = $DB->get_record('user', array('username' => $rut));
//                                echo print_r($resp, true);
                                if(!in_array($inarray, $gm)){
                                    metodos_comunes::register_participants($rut, $resp, $course, $get_session);
                                    echo '<br>usuario ' . $rut . ' se agrego a la sesion ' . $get_session->id;
                                }
                                array_push($arrayrutsws, $rut);

                                \local_pubsub\back\inscripciones::insert_update_inscripciones_back($resp, $get_session->id);

                            } else {
                                echo 'parametros obligatorios ParticipanteIdentificador, ParticipanteNombre, ParticipanteApellido1, ParticipanteEmail';
                                $event = \local_pubsub\event\session_participants::create(
                                                array(
                                                    'context' => \context_system::instance(),
                                                    'other' => array(
                                                            'error' => 'parametros obligatorios ParticipanteIdentificador, ParticipanteNombre, ParticipanteApellido1, ParticipanteEmail',
                                                        ),
                                                )
                                );
                                $event->trigger();
                            }
                        }
                        $gms = groups_get_members($get_session->groupid);
                        // revisar si hay usuarios por fuera
                        foreach($gms as $get_member){
                            if (!in_array($get_member->username, $arrayrutsws)) {
                                if($endpoint_get_sesion["IdRelator"] != $get_member->username){
                                    groups_remove_member($get_session->groupid, $get_member->id);
                                }
                            }
                        }
                    }
                } else {
                    echo "<br>".get_string('validatehuidandgroup', 'local_pubsub', $get_session->id)."<br>";
                }
                
            }
            $transaction->allow_commit();
        } catch (\Exception $e) {
            echo "<h3>" . $e->getMessage() . "</h3>";
            $event = \local_pubsub\event\session_participants::create(
                            array(
                                'context' => \context_system::instance(),
                                'other' => array(
                                    'error' => $e->getMessage(),
                                ),
                            )
            );
            $event->trigger();
            $transaction->rollback($e);
        }
    }

}
