<?php

namespace local_mutual\front;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use dml_exception;
use Exception;
use stdClass;
use local_company;

class utils {
    /*Saber si un curso es streaming */
    public static function is_course_streaming($courseid){
        global $DB;
        $course = $DB->get_record('curso_back', array('id_curso_moodle' => $courseid, 'tipomodalidad' => get_config('local_pubsub', 'tipomodalidaddistancia'), 'modalidaddistancia' => get_config('local_pubsub', 'modalidaddistanciastreaming')));
        if(empty($course)){
            return false;
        } else {
            return true;
        }
    }

    /*Saber si curso es elearning */
    public static function is_course_elearning($courseid){
        global $DB;
        $course = $DB->get_record('curso_back', array('id_curso_moodle' => $courseid, 'tipomodalidad' => get_config('local_pubsub', 'tipomodalidaddistancia'), 'modalidaddistancia' => get_config('local_pubsub', 'modalidaddistanciaelearning')));
        if(empty($course)){
            return false;
        } else {
            return true;
        }
    }

    
    /*Saber si curso es presencial */
    public static function is_course_presencial($courseid){
        global $DB;
        $course = $DB->get_record('curso_back', array('id_curso_moodle' => $courseid, 'tipomodalidad' => get_config('local_pubsub', 'tipomodalidadpresencial')));
        if(empty($course)){
            return false;
        } else {
            return true;
        }
    }


    /**
     * @param $obj_end
     * @return stdClass
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function create_object_user_custon_field($obj_end): stdClass
    {
        global $DB;
        $data = new stdClass();
        //datos adicionales
        $data->participantecargo = $obj_end->participantecargo;
        $data->participantefechanacimiento = $obj_end->participantefechanacimiento;
        $data->participantetipodocumento = $obj_end->participantetipodocumento;
        $data->participantedocumento = $obj_end->participanterut. '-' . $obj_end->participantedv;
        $data->participantesexo = $obj_end->participanteidsexo;
        //datos contacto
        $data->contactoapellidomaterno = $obj_end->responsableapellido2;
        $data->contactoapellidopaterno = $obj_end->responsableapellido1;
        $data->contactocargo = $obj_end->responsablecargo;
        $data->contactocelular = $obj_end->responsabletelefonomovil;
        $data->contactofechanac = $obj_end->responsablefechanacimiento;
        $data->contactoemail = $obj_end->responsableemail;
        $data->contactotipodoc = $obj_end->responsabletipodocumento;
        $data->cintactoiddoc = $obj_end->responsablerut . '-' . $obj_end->responsabledv;
        $data->contactonombres = $obj_end->responsablenombre;
        $data->contactotelefono = $obj_end->responsabletelefonofijo;
        $data->contactoidcomuna = $obj_end->participantecargo;
        $data->contactonombrecomuna = $obj_end->responsablecodigocomuna;
        $data->contactodireccion = ($obj_end->responsabledireccion == "0") ? "" : $obj_end->responsabledireccion;
        $data->contactoidregion = $obj_end->responsablecodigoregion;
        $data->contactonombreregion = ""; //no esta en la tabla
        //datos empresa
        $data->empresarut = $obj_end->participanterutadherente . "-" . $obj_end->participantedvadherente;

        //para evitar registros duplicados en company_users le borro todos los registros existentes
        //y lo asigno nuevamente a la nueva empresa
        $DB->delete_records('company_users', array('userid' => $obj_end->id_user_moodle));

        $get_company_by_rut = $DB->get_record('company', array('rut' => $obj_end->participanterutadherente . "-" . $obj_end->participantedvadherente));
        if (!empty($get_company_by_rut)) {
            $data->empresarazonsocial = $get_company_by_rut->razonsocial;
            $data->empresacontrato = $get_company_by_rut->contrato;
            self::assign_user_company($get_company_by_rut->id, $obj_end->id_user_moodle);
        }else{
            // Si no existe la empresa, se buscan los datos en el servicio Nominativo y se crea
            $datosNominativo = \local_mutual\back\utils::get_personas_nominativo($data->participantedocumento, $data->participantetipodocumento);
            if ($datosNominativo->return->error == 0){
                foreach($datosNominativo->return->empresas as $empresa){
                    // Se verifica que la empresa esté activa (1) el valor (0) es inactiva solo historico
                    if($empresa->activo == 1){ 
                        $dataempresa                = new stdClass();
                        $dataempresa->rut           = (string) $empresa->rut."-".$empresa->dv;
                        $dataempresa->contrato      = (string) $empresa->contrato;
                        $dataempresa->razon_social  = (string) $empresa->razonSocial;// Se crea la empresa en caso de que no exista y se le asigna al usuario
                        $review_company = $DB->get_record('company', array('rut' => (string) $empresa->rut."-".$empresa->dv));
                        if(empty($review_company)){
                            $companyid      = self::create_company($dataempresa);  
                        } else {
                            $companyid      = $review_company->id;  
                        }       
                        self::assign_user_company($companyid, $obj_end->id_user_moodle);                
                        // Seteo campos personalizados
                        $data->empresacontrato      = $dataempresa->contrato;  
                        $data->empresarazonsocial   = $dataempresa->razon_social; 
                        $data->empresarut   = (string) $empresa->rut."-".$empresa->dv;
                        break;
                    }
                }
            }else{
                $event = \local_pubsub\event\inscripcion_elearning::create(
                    array(
                        'context' => \context_system::instance(),
                        'other' => array(
                            'error' => 'Error con el servicio nominativo de Personas, codigo error: '.$datosNominativo->return->error .'Mensaje: '.$datosNominativo->return->mensaje,
                        ),
                    )
                );
                $event->trigger();
            }
        }
        return $data;
    }

    /**
     * @param $userid
     * @param $user_data
     * @return array
     */
    public static function insert_custom_fields_user($userid, $user_data): array
    {
        try {
            $array_aditional_files = array(
                //datos adicionales
                "participantecargo" => (string) $user_data->participantecargo,
                "participantefechanacimiento" => (string) $user_data->participantefechanacimiento,
                "participantetipodocumento" => (string) $user_data->participantetipodocumento,
                "participantedocumento" => (string) $user_data->participantedocumento,
                "participantesexo" => (string) self::parse_genger_str($user_data->participantesexo),
                //datos contacto
                "contactoapellidomaterno" => (string) $user_data->contactoapellidomaterno,
                "contactoapellidopaterno" => (string) $user_data->contactoapellidopaterno,
                "contactocargo" => (string) $user_data->contactocargo,
                "contactocelular" => (string) $user_data->contactocelular,
                "contactofechanac" => (string) $user_data->contactofechanac,
                "contactoemail" => (string) $user_data->contactoemail,
                "contactotipodoc" => (string) $user_data->contactotipodoc,
                "cintactoiddoc" => (string) $user_data->cintactoiddoc,
                "contactonombres" => (string) $user_data->contactonombres,
                "contactotelefono" => (string) $user_data->contactotelefono,
                "contactoidcomuna" => (string) $user_data->contactoidcomuna,
                "contactonombrecomuna" => (string) $user_data->contactonombrecomuna,
                "contactodireccion" => (string) $user_data->contactodireccion,
                "contactoidregion" => (string) $user_data->contactoidregion,
                "contactonombreregion" => (string) $user_data->contactonombreregion,
                //            //datos empresa
                "empresarut" => (string) $user_data->empresarut,
                "empresarazonsocial" => (string) $user_data->empresarazonsocial,
                "empresacontrato" => (string) $user_data->empresacontrato
            );
            profile_save_custom_fields($userid, $array_aditional_files);
            return array('error' => '', 'msg' => true);
        } catch (Exception $e) {
            return array('error' => $e->getMessage(), 'msg' => $e->getMessage());
        }
    }
    /**
     * @param $genger int
     * @return string genger user
     */
    public static function parse_genger_str($genger){
        $array_genero = [
            1 => "H",
            2 => "M",
            3 => "O"
        ];
        if(!empty($array_genero[$genger])){
            return $array_genero[$genger];
        }
        return "H";
    }

    /**
     * @param $genger int
     * @return string genger user
     */
    public static function parse_genger_int($genger){
        $array_genero = [
            "H" => 1,
            "M" => 2,
            "O" => 3
        ];
        if(!empty($array_genero[$genger])){
            return $array_genero[$genger];
        }
        return 1;
    }

    /**
     * @param $rut_company
     * @param $datacompany
     * @return array
     */
     public static function get_or_create_company($rut_company, $datacompany): array
     {
        global $DB;
        try {
            //validate company if not exist
            $get_company_by_rut = $DB->get_record('company', array('rut' => $rut_company));
            if (!$get_company_by_rut) {
                $companyid = self::create_company($datacompany);
            } else {
                $companyid = $get_company_by_rut->id;
            }
            return array('error' => '', 'msg' => $companyid);
        } catch (Exception $e) {
            return array('error' => $e->getMessage(), 'msg' => $e->getMessage());
        }
    }

    /*
    Summary: assign use company
    get:  userid: id user
            user_data: object data custom field
    return: array message and data companyid and userid
    */
    public static function assign_user_company($companyid, $userid): array
    {
        global $DB, $CFG;        
        try {
            //asignar usuario a compañia
            /* $company = new \company($companyid);
            if (!$DB->get_record('company_users', array('companyid' => $companyid, 'userid' => $userid))) {
                $company->assign_user_to_company($userid);
                return array('error' => '', 'msg' => array('userid' => $userid, 'companyid' => $companyid));
            } else {
                $company->unassign_user_from_company($userid);
                $company->assign_user_to_company($userid);
                return array('error' => '', 'msg' => array('userid' => $userid, 'companyid' => $companyid));
            } */
            if (!$DB->get_record('company_users', array('companyid' => $companyid, 'userid' => $userid))) {
                \local_company\metodos_comunes::assign($companyid, $userid);
                return array('error' => '', 'msg' => array('userid' => $userid, 'companyid' => $companyid));
            } else {
                \local_company\metodos_comunes::unassign($userid, $companyid);
                \local_company\metodos_comunes::assign($companyid, $userid);
                return array('error' => '', 'msg' => array('userid' => $userid, 'companyid' => $companyid));
            }
        } catch (Exception $e) {
            return array('error' => $e->getMessage(), 'msg' => $e->getMessage());
        }
    }


    /**
     * @param $datacompany
     * @return array
     */
    public static function create_company($datacompany): array
    {
        global $DB, $CFG;
        try {
            $data = new stdClass();
            $data->name = trim((string) $datacompany->razon_social);
            $data->shortname = trim((string) $datacompany->razon_social);
            $data->city = "";
            $data->country = $CFG->country;
            $data->rut = trim((string) $datacompany->rut);
            $data->contrato = trim((string) $datacompany->contrato);
            $data->razonsocial = trim((string) $datacompany->razon_social);
            $companyid = $DB->insert_record('company', $data);
            $eventother = array('companyid' => $companyid);
            /* $event = \block_comp_company_admin\event\company_created::create(array(
                'context' => \context_system::instance(),
                'other' => $eventother
            ));
            $event->trigger(); */
            return array('error' => '', 'msg' => $companyid);
        } catch (Exception $e) {
            return array('error' => $e->getMessage(), 'msg' => $e->getMessage());
        }
    }

    /*
    Metodo trae la ultima fecha de cierre de los de la sesion a la cual pertenece un usuario, solo las sesiones que tenga calificada
    recibe userid, courseid
    */
    public static function get_last_session_close_user($userid, $courseid){
        global $DB;
        $sql = "SELECT MAX(ats.sessdate) as sessdate, ats.id, cg.id as closeid
        FROM {course} AS c
        JOIN {groups} AS g ON g.courseid = c.id
        JOIN {groups_members} AS m ON g.id = m.groupid
        JOIN {user} AS u ON m.userid = u.id
        JOIN {eabcattendance_sessions} as ats ON ats.groupid = g.id
        JOIN {eabcattendance_log} AS l ON (l.sessionid = ats.id AND l.studentid = ".$userid.")
        JOIN {eabcattendance_statuses} as ates ON l.statusid = ates.id 
        JOIN {format_eabctiles_closegroup} as cg ON (cg.groupid = g.id AND cg.status = 1)
        WHERE c.id = " . $courseid . " AND u.id = " . $userid . " ";
        $date = $DB->get_record_sql($sql);
        if(!empty($date)){
            return $date;
        }
        return null;
    }

    /*
    Metodo trae la ultima fecha de suspencion de los grupos al cual pertenece un usuario
    recibe userid, courseid
    */
    public static function get_last_session_suspend_user($userid, $courseid){
        global $DB;
        $sql = "SELECT MAX(cg.timecreated) as timesuspend, ats.id, cg.id as group_close, cg.motivo
        FROM {course} AS c
        JOIN {groups} AS g ON g.courseid = c.id
        JOIN {groups_members} AS m ON g.id = m.groupid
        JOIN {user} AS u ON m.userid = u.id
        JOIN {eabcattendance_sessions} as ats ON ats.groupid = g.id
        JOIN {format_eabctiles_suspendgrou} as cg ON cg.groupid = g.id
        WHERE c.id = " . $courseid . " AND u.id = " . $userid . " ";
        $date = $DB->get_record_sql($sql);
        if(!empty($date)){
            return $date;
        } 
        return null;
    }

    /**
     * Delete all the attempts belonging to a user in a particular quiz.
     *
     * @param object $quizid int
     * @param object $userid int
     */
    public static function quiz_delete_user_attempts_user($quizid, $userid)
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        \question_engine::delete_questions_usage_by_activities(new \qubaids_for_quiz_user($quizid, $userid));
        $params = [
            'quiz' => $quizid,
            'userid' => $userid,
        ];
        $DB->delete_records('quiz_attempts', $params);
        $DB->delete_records('quiz_grades', $params);
    }

    public static function clear_attemps_course_user($userid, $courseid)
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');
        $key = $userid . '_' . $courseid;
        $completioncache = \cache::make('core', 'completion');
        $completioncache->delete($key);

        $cache = \cache::make('core', 'coursecompletion');
        $cache->delete($key);

        //limpiar criterios de completado de actividad por curso
        $completioncrit = $DB->get_records('course_completion_crit_compl', array('userid' => $userid, 'course' => $courseid));
        if ($completioncrit) {
            $DB->delete_records("course_completion_crit_compl", array('userid' => $userid, 'course' => $courseid));
        }

        $modinfo = \get_fast_modinfo($courseid);
        $cms = $modinfo->get_cms();
        foreach ($cms as $cm) {
            //limpiar intentos de completado de actividad scomr
            if ($cm->modname == "scorm") {
                $atemps_scroms = scorm_get_all_attempts($cm->instance, $userid);
                $scorm = $DB->get_record('scorm', array('id' => $cm->instance));
                foreach ($atemps_scroms as $atemps_scrom) {
                    scorm_delete_attempt($userid, $scorm, $atemps_scrom);
                }
            }
            //limpiar intentos de completado de actividad quiz
            if ($cm->modname == "quiz") {
                self::quiz_delete_user_attempts_user($cm->instance, $userid);
            }
        }
        $send_course = $DB->get_records('format_eabctiles_send_course', array("userid" => $userid, "courseid" => $courseid));
        if(!empty($send_course)){
            $DB->delete_records('format_eabctiles_send_course', array("userid" => $userid, "courseid" => $courseid));
        }
    }


    public static function validation_row($row){
        global $DB;
        $error = false;
        $errorrow = [];
        
        if (!isset($row['rut']) || $row['ParticipanteNombre'] === '') {
            $errorrow[] = 'Rut de usuario es obligatorio';
            $error = true;
        }
        if (!isset($row['courseid']) || $row['courseid'] === '') {
            $errorrow[] = 'Id de curso es obligatorio';
            $error = true;
        }
        if (!isset($row['cmid']) || $row['cmid'] === '') {
            $errorrow[] = 'Course module Id es obligatorio';
            $error = true;
        }
        return array('error' => $error, 'errorrow' => $errorrow);
    }

    public static function process_data_row($line, $columns){
        $data = self::array_user_process();
        $username = ['', ''];
        foreach ($line as $key => $value) {
            $key = $columns[$key];
            if ($value !== '') {
                switch ($key) {
                    case 'rut':
                        $data['rut'] = $value;
                        break;
                    case 'courseid':
                        $data['courseid'] = $value;
                        break;
                    case 'cmid':
                        $data['cmid'] = $value;
                        break;
                    default:
                       break;
                }
            } 
        }
        return array(
            'data' => $data, 
        );
    }

    public static function array_user_process(){
        return array(
            'rut' => '',
            'courseid' => '',
            'cmid' => '',
        );
    }

    public static function get_first_session_user($courseid, $userid){
        global $DB;
           
        $params     = array();
        $grouparray = array();
        $sesion     = '';
        $sqlgroup   = 'SELECT DISTINCT g.id
                        FROM {course} AS c
                        JOIN {groups} AS g ON g.courseid = c.id
                        JOIN {groups_members} AS m ON g.id = m.groupid
                        WHERE c.id = ' . $courseid . ' AND m.userid = ' . $userid;
        $results_groups = $DB->get_records_sql($sqlgroup, $params);

        if(!empty($results_groups)){
            foreach($results_groups as $results_group){
                $grouparray[] = $results_group->id;
            }
            $str_group = implode (",", $grouparray);
            $sql = 'select from_unixtime(s.sessdate, "%d-%m-%Y") as finicio,from_unixtime(s.sessdate, "%H:%i") as hora, s.direction as direccion
                    FROM {eabcattendance_sessions}  as s
                    WHERE s.groupid IN (' . $str_group . ') 
                    order by s.sessdate
                    limit 1 ';
            $sesion = $DB->get_record_sql($sql);
        }
        return $sesion;
    }
}