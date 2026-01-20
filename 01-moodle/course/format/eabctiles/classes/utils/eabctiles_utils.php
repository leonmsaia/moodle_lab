<?php

namespace format_eabctiles\utils;

use dml_exception;
use stdClass;
use context_module;
use mod_eabcattendance_structure;
use moodle_exception;

define('EABCTILES_CLOSE_GROUP', 1);
define('EABCTILES_GROUP_SUSPEND', '1');

class eabctiles_utils {
    /**
     * @param $groupid
     * @param $status
     * @param int $courseid
     * @throws dml_exception
     */
    public static function change_status_group($groupid, $status, $courseid = 1) {
        global $DB;
        $dataobject = new stdClass();
        $dataobject->id = $groupid;
        $dataobject->status = $status;
        $dataobject->timemodified = time();
        $DB->update_record("format_eabctiles_closegroup", $dataobject);
        
        self::create_event_close_group($groupid, $status, $courseid);
    }
    
    public static function insert_data_close($groupid, $courseid = 1) {
        global $DB;
        $dataobject = new stdClass();
        $dataobject->groupid = $groupid;
        $dataobject->status = 1;
        $dataobject->timecreated = time();
        $dataobject->timemodified = time();
        $DB->insert_record("format_eabctiles_closegroup", $dataobject);
        self::create_event_close_group($groupid, EABCTILES_CLOSE_GROUP, $courseid);
    }

    public static function create_event_close_group($groupid, $status, $courseid){
        global $DB;
        $course = $DB->get_record('course', array('id' => $courseid));
        //evento de crear curso front
        $event = \format_eabctiles\event\eabctiles_close_group::create(
                        array(
                            'context' => \context_course::instance($course->id),
                            'other' => array(
                                'shortname' => $course->shortname,
                                'fullname' => $course->fullname,
                                'groupid' => $groupid,
                                'status' => $status,
                            ),
                            'courseid' => $course->id,
                        )
        );
        $event->trigger();
    }
    
    public static function create_event_suspend_group($groupid, $courseid, $motivo, $textother){
        global $DB, $USER;
        $course = $DB->get_record('course', array('id' => $courseid));
        //evento de crear curso front
        $event = \format_eabctiles\event\eabctiles_suspend_group::create(
                        array(
                            'context' => \context_course::instance($course->id),
                            'other' => array(
                                'shortname' => $course->shortname,
                                'fullname' => $course->fullname,
                                'groupid' => $groupid,
                                'status' => EABCTILES_GROUP_SUSPEND,
                                'motivo' => $motivo,
                                'textother' => $textother,
                                'rut' => $USER->username
                            ),
                            'courseid' => $course->id,
                        )
        );
        $event->trigger();
    }
    
    public static function save_suspend($groupid, $courseid, $motivo, $textother){
        global $DB, $USER;
        try {
            $transaction = $DB->start_delegated_transaction();
            
            $data = new stdClass();
            $data->groupid = $groupid;
            $data->courseid = $courseid;
            $data->motivo = $motivo;
            $data->textother = $textother;
            $data->rut = $USER->username;
            $data->timecreated = time();
            $DB->insert_record('format_eabctiles_suspendgrou', $data);

            $groupclose = $DB->get_record('format_eabctiles_closegroup', array('groupid' => $groupid));
            if(!empty($groupclose)){
                $DB->delete_records('format_eabctiles_closegroup', array('id' => $groupclose->id));
            }
            $transaction->allow_commit();

       } catch(Exception $e) {
            $transaction->rollback($e);
       }
    }
    
    public static function get_closequiestions_configtiles() {
        $suspension_motive_array = array();
        $suspension_motive = get_config('format_eabctiles', 'motivosuspencion');
        if(empty($suspension_motive)){
            return '';
        }
        foreach (explode(',', $suspension_motive) as $suspension_motive_arr) {
            array_push($suspension_motive_array, array('motivo' => $suspension_motive_arr));
        }
        return $suspension_motive_array;
    }
    
    public static function get_openquiestions_configtiles() {
        $suspension_motiveopen_array = array();
        $suspension_motiveopen = get_config('format_eabctiles', 'motivosuspencionopen');
        if(empty($suspension_motiveopen)){
            return '';
        }
        foreach(explode(',', $suspension_motiveopen) as $suspension_motiveopen_arr){
            array_push($suspension_motiveopen_array, array('motivoopen' => $suspension_motiveopen_arr));
        }
        return $suspension_motiveopen_array;
    }
    
    public static function eabctiles_response_suspendsession($context, $response, $groupid = 0, $motivo = ''){
        $event = \format_eabctiles\event\eabctiles_response_suspendsession::create(
                        array(
                            'context' => $context,
                            'other' => array(
                                'groupid' => $groupid,
                                'motivo' => $motivo,
                                'response' => json_encode($response),
                            ),
                        )
        );
        $event->trigger();
    }
    
    public static function eabctiles_response_cloasesession($context, $response, $groupid = 0, $motivo = ''){
        $event = \format_eabctiles\event\eabctiles_response_closesession::create(
                        array(
                            'context' => $context,
                            'other' => array(
                                'response' => json_encode($response),
                                'groupid' => $groupid,
                            ),
                        )
        );
        $event->trigger();
    }
    
    public static function is_course_streaming($courseid){
        global $DB;
        $isstreaming = false;
        $curso_back = $DB->get_record('curso_back', array('id_curso_moodle' => $courseid));
        if(!empty($curso_back)){
            if($curso_back->tipomodalidad == get_config('local_pubsub', 'tipomodalidaddistancia') && $curso_back->modalidaddistancia == get_config('local_pubsub', 'modalidaddistanciastreaming')){
                $isstreaming = true;
            }
        }
        return $isstreaming;
    }

    public static function is_valid_date_link($sesion){
        global $DB;
        $terminocapacitacion = false;
        if (!empty($sesion->finicio) && !empty($sesion->duracion)) {
                //si la fecha de la conferencia ya paso no la imprimo
                if(time() > ($sesion->finicio + $sesion->duracion)){
                    $terminocapacitacion = false;
                } else {
                    $terminocapacitacion = true;
                }
        }
        return $terminocapacitacion;
    }

    public static function send_grade_save($courseid, $userid, $finalgradeuser, $grade_course_gradepass){
        global $DB;
        //enviar nota
        $enrolmentback = "select ie.id as id, ie.id_user_moodle as id_user_moodle, ie.id_curso_moodle as id_curso_moodle, ie.participanteidregistroparticip as participanteidregistroparticip 
                            from {inscripcion_elearning_back} ie
                            where ie.timereported = 0 AND ie.id_curso_moodle = " . $courseid . " AND ie.id_user_moodle = " . $userid;
        $enrollments = $DB->get_records_sql($enrolmentback);
        $enrol = end($enrollments);
        if(!empty($enrol)){
            $nota = \local_pubsub\back\inscripcion_elearning::calc_nota($finalgradeuser, $grade_course_gradepass);
            if (floatval($finalgradeuser) >= floatval($grade_course_gradepass)) {
                //Aprobado - Tiene course completions y grade del usuario supera grade pass course                              
                $observacionInscripcion = "Terminó todo y aprobó";
                $resultado = 1;
            } else {
                //si termino todas las actividades, termino el quiz pero reprobó. - tiene course completions y grade del usuario no supera grade pass del curso
                $observacionInscripcion = "Reprobado por nota baja";
                $resultado = 2;
            }
            $asistencia = 100; //consultar
            $today = time();//consultar

            $testing = get_config('format_eabctiles','sendgrade_course');
            $data = new stdClass();
            $data->userid = $userid;
            $data->courseid = $courseid;
            $data->grade = floatval($finalgradeuser);
            $data->timestamp = $today;
            
            if(!empty($testing)){
                $send = \local_pubsub\back\inscripcion_elearning::enviar_cierre_course_back($enrol->participanteidregistroparticip, $nota, $finalgradeuser, $resultado, $today, $observacionInscripcion, $asistencia, $enrol->id_user_moodle, $enrol->id_curso_moodle, false);
                if(!empty($send)){
                    //modo produccion con envio de ws
                    $DB->insert_record('format_eabctiles_send_course', $data);
                }
            } else {
                //modo testing
                $DB->insert_record('format_eabctiles_send_course', $data);
            }
            
        }
        
    }
}
