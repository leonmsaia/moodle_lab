<?php

namespace local_pubsub\task;

use local_pubsub\sistema_get;
use WindowsAzure\ServiceBus\Models\ReceiveMessageOptions;
use moodle_exception;
use local_pubsub\metodos_comunes;
use local_pubsub\utils;
use stdClass;
use mod_eabcattendance\utils\frontutils;

class get_sessions extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('highsessions', 'local_pubsub');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/lib/datalib.php';
        require_once $CFG->dirroot . '/lib/filelib.php';
        require_once $CFG->dirroot . '/mod/eabcattendance/classes/structure.php';
        require_once $CFG->dirroot . "/course/lib.php";
        $courseid = 0;

        $endpoint = get_config('local_pubsub', 'endpointcursos');
        $tokenapi = get_config('local_pubsub', 'tokenapi');
        $subscriptionkey = get_config('local_pubsub', 'subscriptionkey');

        $msg = sistema_get::get_session();
//        $arraymsg = array(
//            "AgenciaMutual" => 'Santiago',
//            "Auditorio" => '',
//            "CantidadParticipantes" => 180,
//            "CapacitacionPortal" => False,
//            "CodigoCurso" => '60',
//            "Estado" => '100000000',
//            "Id" => '2eb1bf1b-aa58-ea11-a811-000d3a4f6c1a',
//            "IdEjecutivo" => '',
//            "IdEvento" => 'c3296ae8-8f9e-46e4-8ab6-88813df6dd84',
//            "IdRelator" => '',
//            "InicioCapacitacion" => '2020-02-24T09:35:00Z',
//            "MotivoSuspension" => '',
//            "TerminoCapacitacion" => '2020-02-26T11:35:00Z',
//        );
//        $msg = new \stdClass();
//        $msg->mensaje = $arraymsg;
//        $msg->publicador = 'BACK';
//        $msg->guid = '2eb1bf1b-aa58-ea11-a811-000d3a4f6c1a';
//        $msg->accion = 'Alta';
//        $msg->idcurso = '5757df6b-0057-ea11-a811-000d3a4f62e7';
//        $msg->idevento = 'c3296ae8-8f9e-46e4-8ab6-88813df6dd84';
//        $msg->publicador = 'BACK';
//        echo "<br>alta sesion<br>";
//        echo "<br>".print_r($msg, true)."<br>";
        while (($msg != false)) {

            //creacion de cursos		
            if ($msg->accion == "Alta") {
                try {
//echo "<br>entroal try";
                    $attendanceverify = $DB->get_record('eabcattendance_sessions', array('guid' => $msg->guid));
                    //valido que el guid no este guardado
                    if (empty($attendanceverify)) {
                        $timestart = utils::date_to_timestamp($msg->mensaje['InicioCapacitacion']);
                        $timeend = utils::date_to_timestamp($msg->mensaje['TerminoCapacitacion']);
                        $timesecond = $timeend - $timestart;
//                    buscar guil en moodle
                        $valdiateguilmodle = $DB->get_record('eabcattendance_course_gu', array('guid' => $msg->idcurso));
                        if (!empty($valdiateguilmodle)) {
                            $courseid = $valdiateguilmodle->courseid;
                        } else {
//                        si no consigui el guil en moodle voy contra el ws para ver si esta en back
                            $get_course_back_by_guil = sistema_get::get_request_endpoint($endpoint . $msg->idcurso, $tokenapi, $subscriptionkey);
                            if (!empty($get_course_back_by_guil)) {
                                $get_course = $DB->get_record('course', array('fullname' => $get_course_back_by_guil["ProductoCurso"]));
                                if (!empty($get_course)) {
                                    $courseid = $get_course->id;
                                } else {
                                    //curso no existe en moodle
                                    //crear curso en moodle con los datos del enpoint 
                                    $coursenamesetting = get_config('local_pubsub', 'coursename');
                                    if(!empty($coursenamesetting)){
                                        $coursename = $get_course_back_by_guil[get_config('local_pubsub', 'coursename')];
                                    } else {
                                        $coursename = $get_course_back_by_guil["ProductoCurso"];
                                    }

                                    $courseshortnamesetting = get_config('local_pubsub', 'courseshortname');
                                    if(!empty($courseshortnamesetting)){
                                        $courseshortname = $get_course_back_by_guil[get_config('local_pubsub', 'courseshortname')];
                                    } else {
                                        if(empty($courseshortname)){
                                            $courseshortname = $coursename;
                                        } else {
                                            $courseshortname = $get_course_back_by_guil["ProductoCurso"];
                                        }
                                    }
                                    $coursecategorysetting = get_config('local_pubsub', 'coursecategory');
                                    if(!empty($coursecategorysetting)){
                                        $coursecategory = $get_course_back_by_guil[get_config('local_pubsub', 'coursecategory')];
                                    } else {
                                        $coursecategory = 1;
                                    }
                                    $new_course = metodos_comunes::crear_curso($coursename, $courseshortname, $coursecategory);
                                    $courseid = $new_course;
                                    $dataObj = new \stdClass();
                                    $dataObj->courseid = $courseid;
                                    $dataObj->guid = $msg->idcurso;
                                    $DB->insert_record('eabcattendance_course_gu', $dataObj);
                                    
                                }
                            } else {
                                //guardar evento
                                $other = array(
                                    'error' => get_string('guidnotfoundback', 'local_pubsub'),
                                    'guid' => $msg->guid,
                                );
                                metodos_comunes::save_event_sessions(\context_system::instance(), $other);
                                echo get_string('guidnotfoundback', 'local_pubsub');
                            }
                        }

                        $get_attendances = $DB->get_records('eabcattendance', array('course' => $courseid));
                        if (empty($get_attendances)) {
                            //guardar evento
                            $other = array(
                                'error' => get_string('coursenotattendance', 'local_pubsub'),
                                'guid' => $msg->guid,
                            );
                            metodos_comunes::save_event_sessions(\context_course::instance($courseid), $other, $courseid);
                            echo get_string('coursenotattendance', 'local_pubsub');

                            return false;
                        }
                        foreach ($get_attendances as $get_attendance) {

                            $cm = get_coursemodule_from_instance('eabcattendance', $get_attendance->id, 0, false, MUST_EXIST);
                            $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
                            $attendanceid = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);
                            // Check permissions.
                            // Validate group.
                            $groupmode = (int) groups_get_activity_groupmode($cm);
                            //valido si el guid del grupo ya existe en mooodle asociado a un grupo
                            $groups = $DB->get_record('eabcattendance_course_groups', array('curso' => $course->id, 'uuid' => $msg->idevento));

                            if (empty($groups)) {

                                //si no existe el guid asocuado a un curso creo el grupo
                                $creategropup = array("createname" => date("d-m-Y", $timestart));
                                $groupid = metodos_comunes::create_group($creategropup, $course);
                                metodos_comunes::eabcattendance_course_groups($groupid, $course->id, $msg->idevento);
                                $groups = $DB->get_record('eabcattendance_course_groups', array('curso' => $course->id, 'grupo' => $groupid, 'uuid' => $msg->idevento));
                            }
                            if ($groupmode === SEPARATEGROUPS || ($groupmode === VISIBLEGROUPS && $groups > 0)) {
                                // Determine valid groups.
                                metodos_comunes::create_session($attendanceid, $cm, $course, $timestart, $timesecond, $groups, $msg, $course->id);
                            } else {
                                //guardar evento
                                $other = array(
                                    'error' => get_string('activitynoconfigure', 'local_pubsub'),
                                    'guid' => $msg->guid,
                                );
                                metodos_comunes::save_event_sessions(\context_course::instance($courseid), $other, $courseid);
                                echo get_string('activitynoconfigure', 'local_pubsub');
                            }
                            break;
                        }
                    } 
                   
                } catch (\Exception $e) {
                    echo "<h3>" . $e->getMessage() . "</h3>";
                    $other = array(
                        'error' => $e->getMessage(),
                        'guid' => $msg->guid,
                    );
                    metodos_comunes::save_event_sessions(\context_system::instance(), $other);
                }
            }
            
             $msg = sistema_get::get_session();
//            $msg = false;
        }
    }

}
