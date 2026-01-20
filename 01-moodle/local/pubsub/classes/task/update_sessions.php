<?php

namespace local_pubsub\task;

use local_pubsub\sistema_get;
use WindowsAzure\ServiceBus\Models\ReceiveMessageOptions;
use moodle_exception;
use local_pubsub\metodos_comunes;
use local_pubsub\utils;
use Exception;
use stdClass;

class update_sessions extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return 'Update Sesiones';
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/lib/datalib.php';
        require_once $CFG->dirroot . '/lib/filelib.php';
        require_once $CFG->dirroot . '/mod/eabcattendance/classes/structure.php';
        $courseid = 0;

        $endpoint = get_config('local_pubsub', 'endpointcursos');
        $tokenapi = get_config('local_pubsub', 'tokenapi');
        $subscriptionkey = get_config('local_pubsub', 'subscriptionkey');

        $msg = sistema_get::update_session();    
         
        //No borrar, para pruebas locales
           /*  $arraymsg = array(
            "AgenciaMutual" => 'SD000011',
            "Auditorio" => '',
            "CantidadParticipantes" => '',
            "CapacitacionPortal" => False,
            "CodigoCurso" => 'S-000000000000133',
            "Estado" => '100000000',
            "Id" => '9685809c-0f53-ea11-a812-000d3a4f62e7',
            "IdEjecutivo" => '',
            "IdEvento" => '',
            "IdRelator" => 'estudiante1',
            "InicioCapacitacion" => '2020-01-27T18:04:00Z',
            "MotivoSuspension" => '',
            "TerminoCapacitacion" => '2020-02-18T20:00:00Z',
        ); */
        $msg = new \stdClass();
        $msg->mensaje = $arraymsg;
        $msg->publicador = 'BACK';
        $msg->guid = '9685809c-0f53-ea11-a812-000d3a4f62e7';
        $msg->accion = 'Actualizacion';
        $msg->idcurso = '034c7028-0a42-ea11-a812-000d3a4f62e7';
        $msg->idevento = 'abe6fcb2-8408-4a70-8633-f972475e0e2a';
        $msg->publicador = 'BACK'; 
        
    
        while ($msg != false) {  
            
            try {
                /* $args = array(
                    'id' => $msg->guid,
                    'idcurso' => $msg->idcurso,
                    'idevento' => $msg->idevento,
                    'action' => $msg->accion
                );
                $response = \external_api::call_external_function('update_sesion', $args, true); */
                $timestart  = utils::date_to_timestamp($msg->mensaje['InicioCapacitacion']);
                $timeend    = utils::date_to_timestamp($msg->mensaje['TerminoCapacitacion']);
                $timesecond = $timeend - $timestart ;
                echo $timestart; exit();

                $valdiateguilmodle = $DB->get_record('eabcattendance_course_gu', array('guid' => $msg->idcurso));
                    if (!empty($valdiateguilmodle)) {
                        $courseid = $valdiateguilmodle->courseid;
                    }
                    else {
//                        si no consigui el guil en moodle voy contra el ws para ver si esta en back
                        $get_course_back_by_guil = sistema_get::get_request_endpoint($endpoint . $msg->idcurso, $tokenapi, $subscriptionkey);
                        if(!empty($get_course_back_by_guil)){
                            $get_course = $DB->get_record('course', array('fullname' => $get_course_back_by_guil['ProductoCurso']));
                            if(!empty($get_course)){
                                $courseid = $get_course->id;
                            } else {
                                
                                $event = \local_pubsub\event\get_sessions::create(
                                                array(
                                                    'context' => \context_course::instance($courseid),
                                                    'other' => array(
                                                        'error' => 'guil de curso no encontrado en moodle',
                                                        'guid' => $msg->guid,
                                                    ),
                                                    'courseid' => $courseid,
                                                )
                                );
                                $event->trigger();
                                
                                //TODO add string (JSALGADO)
                                echo('guil de curso no encontrado en moodle');
                                
                            }
                        } else {
                            //TODO add string (JSALGADO)
                            $event = \local_pubsub\event\get_sessions::create(
                                                array(
                                                    'context' => \context_course::instance($courseid),
                                                    'other' => array(
                                                        'error' => 'guil de curso no encontrado en back',
                                                        'guid' => $msg->guid,
                                                    ),
                                                    'courseid' => $courseid,
                                                )
                                );
                                $event->trigger();
                                
                                echo('guil de curso no encontrado en back');
                        }
                    }

                $role = get_config("eabcattendance", "rolwscreateactivity");
                $roleid = (!empty($role)) ? $role : 3;
                $course = $DB->get_record('course',array('id'=>$courseid));
                if (empty($course)){
                    $event = \local_pubsub\event\get_sessions::create(
                        array(
                            'context' => \context_course::instance($courseid),
                            'other' => array(
                                'error' => 'Curso id no encontrado',
                                'guid' => $msg->guid,
                            ),
                            'courseid' => $courseid,
                        )
                    );
                    $event->trigger();
                    
                    echo ('Curso id no encontrado');
                    return false;
                }
                $group = $DB->get_record('eabcattendance_course_groups', array('uuid'=>$msg->idevento));
                if (empty($group)){
                    $event = \local_pubsub\event\get_sessions::create(
                        array(
                            'context' => \context_course::instance($courseid),
                            'other' => array(
                                'error' => 'Grupo id no encontrado',
                                'guid' => $msg->guid,
                            ),
                            'courseid' => $courseid,
                        )
                    );
                    $event->trigger();
                    
                    echo ('Grupo id no encontrado');
                    return false;
                }
                $user = $DB->get_record('user',array('username'=>$msg->mensaje['IdRelator']));
                if ($user){
                    metodos_comunes::enrol_user($course, $user->id, $group->grupo, $roleid);
                }else{
                    $event = \local_pubsub\event\get_sessions::create(
                        array(
                            'context' => \context_course::instance($courseid),
                            'other' => array(
                                'error' => 'Sin id Relator en la Sesión',
                                'guid' => $msg->guid,
                            ),
                            'courseid' => $courseid,
                        )
                    );
                    $event->trigger();
                    
                    echo ('Sin id Relator en la Sesión');
                }
                            
                $get_attendances = $DB->get_record('eabcattendance_sessions', array('guid' => $msg->guid));

                if (empty($get_attendances)) {

                    $event = \local_pubsub\event\get_sessions::create(
                                    array(
                                        'context' => \context_course::instance($courseid),
                                        'other' => array(
                                            'error' => 'este curso no tiene creada la actividad asistencia',
                                            'guid' => $msg->guid,
                                        ),
                                        'courseid' => $msg->mensaje['Id'],
                                    )
                    );
                    $event->trigger();
                    echo('este curso no tiene creada la actividad asistencia');
                    
                    return false;
                }else{
                    $dataobject = new stdClass();
                    $dataobject->id = $get_attendances->id;
                    $dataobject->sessdate = $timestart;
                    $dataobject->duration = $timesecond;
                    $DB->update_record('eabcattendance_sessions', $dataobject);
                }                
            
            }catch (Exception $e) {
                echo "<h3>" . $e->getMessage() . "</h3>";
                $event = \local_pubsub\event\get_sessions::create(
                                array(
                                    'context' => \context_course::instance($courseid),
                                    'other' => array(
                                        'error' => $e->getMessage(),
                                        'guid' => $msg->guid,
                                    ),
                                )
                );
                $event->trigger();
            }
            
            $msg = sistema_get::update_session();
        }
        
    }
}
