<?php

namespace local_pubsub\external;

use dml_exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use moodle_exception;
use mod_eabcattendance_structure;
use context_module;
use stdClass;
use local_pubsub\metodos_comunes;
use context_course;
use local_pubsub\sistema_get;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');

class attendance extends external_api
{
    /**
     * @return external_function_parameters
     */
    public static function user_parameters()
    {
        return new external_function_parameters(
            array(
                'idsesion' => new external_value(PARAM_RAW, 'Guid de la sesión'),
                'rut' => new external_value(PARAM_RAW, 'Rut del participante'),
                'trx' => new external_value(PARAM_RAW, 'Id de transacción'),
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
    public static function user($idsesion, $rut, $trx)
    {
        global $DB, $CFG;
        require_once $CFG->dirroot . '/mod/eabcattendance/locallib.php';
        require_once $CFG->dirroot . '/lib/filelib.php';

        /** @var external_api $api */
        $api = new class extends external_api {};
        $params = $api::validate_parameters(
            self::user_parameters(), array(
                'idsesion' => $idsesion,
                'rut' => $rut,
                'trx' => $trx,
            )
        );

        $rutformat = strtolower(trim($params["rut"]));
        $flaguserattendance = false;
        try{
            $get_sesion = $DB->get_record("eabcattendance_sessions", array("guid" => $params["idsesion"]));
            if(!empty($get_sesion)){
                
                $attendance = $DB->get_record('eabcattendance', array('id' => $get_sesion->eabcattendanceid));
                $module = $DB->get_record("modules", ["name" => "eabcattendance"]);
                $mod_attendance = $DB->get_record('course_modules', array('course' => $attendance->course, 'instance' => $attendance->id, "module" => $module->id));
                
                $cm = get_coursemodule_from_id('eabcattendance', $mod_attendance->id, 0, false, MUST_EXIST);
                $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
                $att = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);

                $pageparams = new stdClass();
                $pageparams->sessionid = $get_sesion->id;
                $pageparams->grouptype = 0;

                $att = new mod_eabcattendance_structure($att, $cm, $course, context_module::instance($cm->id), $pageparams);
                
                $get_user = $DB->get_record('user', array('username' => $rutformat));
                if(!empty($get_user)){
                    metodos_comunes::save_rate_huellero($attendance, $get_sesion, $mod_attendance, $get_user, $att);
                    set_user_preference('local_pubsu_trx', $params["trx"], $get_user);
                    //guardar evento
                    $other = array(
                        'rut' => $rutformat,
                        'idsesion' => $get_sesion->id,
                        'guidsesion' => $params["idsesion"],
                        'trx' => $params["trx"],
                    );
                    metodos_comunes::save_event_rate_huellero(context_course::instance($course->id), $other, $course->id);
                } else {
                    $participantessesion = get_config('local_pubsub', 'endpointupdatesession');
                    $tokenapi = get_config('local_pubsub', 'tokenapi');
                    $subscriptionkey = get_config('local_pubsub', 'subscriptionkey');
            
                    $endpoint_session_str = str_replace("{idSesion}", $params["idsesion"], $participantessesion);
                    $response = sistema_get::get_request_endpoint($endpoint_session_str, $tokenapi, $subscriptionkey);
//                    $response = array(
//                        array(
//                            'ParticipanteIdentificador' => '15664979-9',
//                            'ParticipanteNombre' => 'jose9',
//                            'ParticipanteApellido1' => 'salgado9',
//                            'ParticipanteEmail' => 'salgado9@salgado.com',
//                            'IdSexo' => '1',
//                            'ResponsableNombres' => 'test nombre adherente',
//                            'ResponsableIdentificador' => 'test responsable adherente',
//                        )
//                    );
                    
                    if(!empty($response)){
                        foreach($response as $resp){
                            if($resp["ParticipanteIdentificador"] == $rutformat){
                                //verifico si trae los parametros minimos
                                if ($resp['ParticipanteIdentificador'] &&
                                    $resp['ParticipanteNombre'] &&
                                    $resp['ParticipanteApellido1'] &&
                                    $resp['ParticipanteEmail']) {
                                    //crear usuario y matricularlo y agregarlo al grupo
                                    metodos_comunes::register_participants($rutformat, $resp, $course, $get_sesion);
                                    $get_user = $DB->get_record('user', array('username' => $rutformat));
                                    set_user_preference('local_pubsu_trx', $params["trx"], $get_user);
                                    //guardar la nota
                                    metodos_comunes::save_rate_huellero($attendance, $get_sesion, $mod_attendance, $get_user, $att);
                                    //guardar evento
                                    $other = array(
                                        'rut' => $rutformat,
                                        'idsesion' => $get_sesion->id,
                                        'guidsesion' => $params["idsesion"],
                                        'trx' => $params["trx"],
                                    );
                                    metodos_comunes::save_event_rate_huellero(context_course::instance($course->id), $other, $course->id);
                                    $flaguserattendance = true;
                                }
                            }
                        }
                        if(!$flaguserattendance){
                            throw new moodle_exception('errormsg', 'mod_eabcattendance', '', get_string('usuarionotexist', 'local_pubsub'));
                        }
                    } else {
                        throw new moodle_exception('errormsg', 'mod_eabcattendance', '', get_string('usuarionotexist', 'local_pubsub'));
                    }
                
                }
                
            } else {
                throw new moodle_exception('errormsg', 'mod_eabcattendance', '', get_string('idsesionnoregister', 'local_pubsub'));
            }
            
        } catch (Exception $ex) {
            
        }
        
        
        return array(
            "sesionid" => $get_sesion->id
        );
    }

    /**
     * @return external_single_structure
     */
    public static function user_returns()
    {
        return new external_single_structure(
            array(
                'sesionid' => new external_value(PARAM_RAW, 'id la sesión'),
            )
        );
    }
}