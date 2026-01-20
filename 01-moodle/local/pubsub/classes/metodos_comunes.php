<?php

// (27/01/2020 FHS)
//Definicion de la clase metodos_comunes.

/* Contiene metodos para:

 * Crear cursos                                      [crear_curso($fullname, $short_name, $id_category)]
 * Borrar cursos										[borrar_curso($id)]
 * Actualizar cursos									[actualizar_curso($datos_curso)]
 */

namespace local_pubsub;

use coding_exception;
use dml_exception;
use mod_quiz\question\qubaids_for_users_attempts;
use moodle_exception;
use stdClass;
use local_pubsub\back;
use local_pubsub\back\inscripcion_elearning;
use Exception;
use mysqli_native_moodle_database;

defined('MOODLE_INTERNAL') || die();

global $CFG, $DB, $OUTPUT, $PAGE, $COURSE;


//require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/pubsub/lib.php');
require_once($CFG->dirroot . "/local/company/classes/metodos_comunes.php"); 

class metodos_comunes
{

    public function __construct()
    {

        return 0;
    }

    /**
     * @param $fullname
     * @param $short_name
     * @param $id_category
     * @param string $summary
     * @return int
     * @throws dml_exception
     * @throws moodle_exception
     */
    static public function crear_curso($fullname, $short_name, $id_category, $summary = "", $modalidad_curso = "", $modalidad_curso_distancia = "")
    {
        // $fullname se mostrara en la lista de cursos

        $data = new \stdClass();
        $data->fullname = $fullname;
        $data->categoryid = $id_category;
        $data->shortname = !empty($short_name)? $short_name : $fullname;
        if (is_array($summary)) {
            $summary = implode("\n", $summary);
        }
        $data->summary = $summary;
        $data->timecreated = time();
        $data->timemodified = $data->timecreated;
        $data->visible = true;

        $tipo_modalidad_distancia       = get_config('local_pubsub', 'tipomodalidaddistancia');
        $modalidad_distancia_elearning  = get_config('local_pubsub', 'modalidaddistanciaelearning');
        $modalidad_distancia_streaming  = get_config('local_pubsub', 'modalidaddistanciastreaming');
        $modalidad_distancia_mobile     = get_config('local_pubsub', 'modalidaddistanciamobile');

        if  (($modalidad_curso) && ($modalidad_curso == $tipo_modalidad_distancia)){ // Modalidad a Distancia
            if ($modalidad_curso_distancia == $modalidad_distancia_elearning){
                // Modalidad a Distancia Tipo Elearning
                $rutaarchivombz = get_config('local_pubsub', 'rutaelearningmbz');                
            }elseif($modalidad_curso_distancia == $modalidad_distancia_streaming){
                // Modalidad a Distancia Tipo Streming
                $rutaarchivombz = get_config('local_pubsub', 'rutastreamingmbz');
            }elseif($modalidad_curso_distancia == $modalidad_distancia_mobile){
                // Modalidad a Distancia Tipo Mobile
                $rutaarchivombz = get_config('local_pubsub', 'rutamobilembz');
            }else{
                $rutaarchivombz = get_config('local_pubsub', 'rutaarchivombz');
            }                            
        }else{
            $rutaarchivombz = get_config('local_pubsub', 'rutaarchivombz');
        }

        if(empty($rutaarchivombz)) {
            throw new moodle_exception("Debe colocar la ruta del archivo MBZ correspondiente al tipo de Modalidad en la configuración del plugin Publish Suscribe");
        }  
        $rutadirarchivombz = dirname($rutaarchivombz);
        $data->template = basename($rutaarchivombz);

        $courseid = make_course_by_template((array)$data, $rutadirarchivombz);

        
        $data->id = $courseid;
        unset($data->template);
        update_course($data);

        return $courseid;
        //return create_course($data);
    }

    static public function borrar_curso($id)
    {
        global $DB, $SITE;
        $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

        if ($SITE->id == $course->id || !can_delete_course($id)) {
            print_error('cannotdeletecourse');
            exit;
        }

        return delete_course($course);
    }

    /**
     * @param $data
     * @throws moodle_exception
     */
    static public function actualizar_curso($data)
    {


//		$data->id = 0;
//		$data->shortname = "";
//		$data->fullname = "";
//		$data->category = "";		

        $data->visible = '1';
        $data->hiddensections = "0";
        $data->coursedisplay = "0";
        //$data->idnumber = $data->id;


        return update_course($data, $editoroptions = NULL);    /// course/lib.php	(linea 2551)

    }

    static public function close_sesion($groupid)
    {
        global $DB;

        $sesions = $DB->get_records("eabcattendance_sessions", array("groupid" => $groupid));
        foreach ($sesions as $sess) {
            if (!empty($sess->guid)) {
                $attendance = $DB->get_record("eabcattendance", array("id" => $sess->eabcattendanceid));
                $gradeitemparamscourse = [
                    'itemtype' => 'course',
                    'courseid' => $attendance->course,
                ];
                $grade_course = \grade_item::fetch($gradeitemparamscourse);

                $respuesta = self::get_participantes_sesion($sess->guid);
                $dataresponse = array('response' => json_encode($respuesta), 'guid' => $sess->guid);
                self::save_event_sessionparticipants(\context_course::instance($attendance->course), $dataresponse);
                
                foreach ($respuesta as $result) {
                    $ParticipanteIdentificador = $result['ParticipanteIdentificador'];
                    $user = $DB->get_record_sql('SELECT id FROM {user} 
                                where username = "' . $ParticipanteIdentificador . '"');

                    if ($user){                        
                        $grades_user = \grade_grade::fetch_users_grades($grade_course, array($user->id), false);
                        $asistencias = $DB->get_record("eabcattendance_log", array("sessionid" => $sess->id, "studentid" => $user->id));
                        $estatus = $DB->get_record("eabcattendance_statuses", array("id" => $asistencias->statusid));
        
                        $nota = round($grades_user[$user->id]->finalgrade);
                        $NotaEvaluacion = ($nota >= 75) ? (($nota - 75) * 0.12) + 4 : ($nota * 0.04) + 1;
                        $asistencia = str_replace("%", '', $estatus->description);
                        if ($asistencia == null){
                            $asistencia = 0;
                        }
        
                        $participantes[] = array(
                            "IdInscripcion" => $result['IdInscripcion'],
                            "NotaEvaluacionPorcentaje" => $nota,
                            "NotaEvaluacion" => round($NotaEvaluacion),
                            "Asistencia" => $asistencia
                        );
                    }
                    
                }
                if ($participantes) {
                    $data['Participantes'] = $participantes;
                    $response = self::send_close_sesion($sess->guid, $data);
                } else {
                    $response = null;
                    throw new Exception('Ocurrio un error en la funcion close_sesion, Respuesta: '. json_encode($respuesta));
                }
            }
        }

        return $response;
    }

    /** Informa suspensión de una sesión al Back **/
    static public function suspend_sesion($groupid, $motivo)
    {
        global $DB;
        $sesions = $DB->get_records("eabcattendance_sessions", array("groupid" => $groupid));
        foreach ($sesions as $sess) {
            $data['Motivo'] = $motivo;
            $endpointsuspendsession = get_config('local_pubsub', 'endpointsuspendsession');
            $cURLConnection = curl_init();
            curl_setopt($cURLConnection, CURLOPT_URL, $endpointsuspendsession . $sess->guid);
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cURLConnection, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
                'Authorization: ' . get_config('local_pubsub', 'tokenapi'),
                'Ocp-Apim-Subscription-Key: ' . get_config('local_pubsub', 'subscriptionkey'),
                'Content-Type:application/json'
            ));
            $respuesta = curl_exec($cURLConnection);
            $httpcode = curl_getinfo($cURLConnection, CURLINFO_HTTP_CODE);
            curl_close($cURLConnection);
        }

        return $httpcode;
    }

    public static function get_participantes_sesion($guid)
    {
        $endpointparticipantessession = get_config('local_pubsub', 'endpointparticipantessession');
        $endpointparticipantessession_str = str_replace("{idSesion}", $guid, $endpointparticipantessession);
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, $endpointparticipantessession_str);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
            'Authorization: ' . get_config('local_pubsub', 'tokenapi'),
            'Ocp-Apim-Subscription-Key: ' . get_config('local_pubsub', 'subscriptionkey')
        ));
        $respuesta = curl_exec($cURLConnection);
        $httpcode = curl_getinfo($cURLConnection, CURLINFO_HTTP_CODE);
        curl_close($cURLConnection);

        if ($httpcode > 299) {
            throw new Exception('Ocurrio un error en la funcion get_participantes_sesion, Respuesta: '.json_encode($respuesta));
        }
        
        return json_decode($respuesta, true);
    }

    public static function send_close_sesion($guid, $data)
    {
        $endpoint_update_session = get_config('local_pubsub', 'endpointclosesession');
        $endpoint_update_session_str = str_replace("{idSesion}", $guid, $endpoint_update_session);
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, $endpoint_update_session_str);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
            'Authorization: ' . get_config('local_pubsub', 'tokenapi'),
            'Ocp-Apim-Subscription-Key: ' . get_config('local_pubsub', 'subscriptionkey'),
            'Content-Type:application/json'
        ));

        $respuesta = curl_exec($cURLConnection);
        $httpcode = curl_getinfo($cURLConnection, CURLINFO_HTTP_CODE);
        curl_close($cURLConnection);
    
        self::save_event_sessions(\context_system::instance(), [
            "error" => json_encode(
                [
                    "endpointCierre" => $endpoint_update_session_str,
                    "data" => json_encode($data),
                    "code" => $httpcode
                ]
            )
        ]);

        if ($httpcode > 299) {
            throw new Exception('Ocurrio un error en la funcion send_close_sesion, Respuesta: '.json_encode($respuesta));
        }
        
        return $httpcode;
    }

    /**
     * @param $createuser
     * @param null $course
     * @return int
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function create_user($createuser, $course = null, $enrol_passport = false, $password_generate = "")
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        $create_user = new stdClass();
        $username = \core_user::clean_field(strtolower(trim($createuser["username"])), 'username');
        $get_user = $DB->get_record("user", array("username" => $username));

        $create_user->username = $username;
        $create_user->auth = 'manual';
        $create_user->firstname = $createuser["firstname"];
        $create_user->lastname = $createuser["lastmame"];
        $create_user->mnethostid = 1;
        $create_user->confirmed = 1;
        $create_user->email = $createuser["email"];
        $password = '';

        if (!empty($get_user)) {
            //si el usuario ya existe lo actualizo
            $create_user->id = $get_user->id;
            user_update_user($create_user);
            $newuserid = $get_user->id;
            $custom = $DB->get_record('eabcattendance_extrafields', array('userid'  => $newuserid));
            if($custom){
                self::insert_extra_fields($createuser, $newuserid, true);
            } else {
                self::insert_extra_fields($createuser, $newuserid);
            }
            
        } else {

            if(!empty($password_generate)) {
                $password = $password_generate;
            } else {
                $password = $createuser["username"];
                //por regla de negocio con el cliente si es pasaporte le agrego un guion(-) al final
                if($enrol_passport) {
                    $password = $createuser["username"] . '-';
                }
            }
            
            //paso parametro passoword solo para usuarios nuevos para no actualizar clave
            $create_user->password = $password;
            //si el usuario no existe lo creo
            $newuserid = user_create_user($create_user);
            //pido cambio de clave para proximo ingreso
            set_user_preference('auth_forcepasswordchange', 1, $newuserid);
            //insertar campos adicionales del usuario
            self::insert_extra_fields($createuser, $newuserid);
        }

        self::saveApellidoMaterno($createuser, $newuserid );

        return $newuserid;
    }


    public static function saveApellidoMaterno($createuser, $newuserid )
    {
        global $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');
        
        $array_aditional_files = array();
        if ($createuser["apellidomaterno"]==''){
            $datosNominativo = \local_mutual\back\utils::get_personas_nominativo($createuser["username"], 1);

            if ( isset($datosNominativo->return->error) &&  ($datosNominativo->return->error == 0)){
                $array_aditional_files = array("apellidom" => (string) $datosNominativo->return->persona->apellido2);
            }
        }else{
            $array_aditional_files = array(
                "apellidom"  => $createuser["apellidomaterno"]
            ); 
        }

        profile_save_custom_fields($newuserid, $array_aditional_files);
    }

    public static function insert_extra_fields($createuser, $newuserid, $update = false)
    {
        global $DB;

        if ($update) {
            $custom = $DB->get_record('eabcattendance_extrafields', array('userid'  => $newuserid));
            
            $sexo = (!empty($createuser["sexo"])) ? $createuser["sexo"] : $custom->participantesexo;
            $pais = (!empty($createuser["pais"])) ? $createuser["pais"] : $custom->pais;
            $fecha_nacimiento = (!empty($createuser["participantefechanacimiento"])) ? $createuser["participantefechanacimiento"] : $custom->participantefechanacimiento;
            $roles = (!empty($createuser["roles"])) ? $createuser["roles"] : $custom->rol;
            $apellidomaterno = (!empty($createuser["apellidomaterno"])) ? $createuser["apellidomaterno"] : $custom->apellidomaterno;
            $nroadherente = (!empty($createuser["nroadherente"])) ? $createuser["nroadherente"] : $custom->nroadherente;
            $empresarut = (!empty($createuser["empresarut"])) ? $createuser["empresarut"] : $custom->empresarut;
            
            $array_aditional_files = new \stdClass();
            $array_aditional_files->id = $custom->id;
            $array_aditional_files->userid = $newuserid;
            $array_aditional_files->participantesexo = $sexo;
            $array_aditional_files->pais = $pais;
            $array_aditional_files->participantefechanacimiento = $fecha_nacimiento;
            $array_aditional_files->rol = $roles;
            $array_aditional_files->apellidomaterno = $apellidomaterno;
            $array_aditional_files->nroadherente = $nroadherente;
            $array_aditional_files->empresarut = $empresarut;
            
            $DB->update_record('eabcattendance_extrafields', $array_aditional_files);
        } else {
            
            $sexo = (!empty($createuser["sexo"])) ? $createuser["sexo"] : '';
            $pais = (!empty($createuser["pais"])) ? $createuser["pais"] : '';
            $fecha_nacimiento = (!empty($createuser["participantefechanacimiento"])) ? $createuser["participantefechanacimiento"] : '';
            $roles = (!empty($createuser["roles"])) ? $createuser["roles"] : '';
            $apellidomaterno = (!empty($createuser["apellidomaterno"])) ? $createuser["apellidomaterno"] : '';
            $nroadherente = (!empty($createuser["nroadherente"])) ? $createuser["nroadherente"] : '';
            $empresarut = (!empty($createuser["empresarut"])) ? $createuser["empresarut"] : '';
            
            $array_aditional_files = new \stdClass();
            $array_aditional_files->userid = $newuserid;
            $array_aditional_files->participantesexo = $sexo;
            $array_aditional_files->pais = $pais;
            $array_aditional_files->participantefechanacimiento = $fecha_nacimiento;
            $array_aditional_files->rol = $roles;
            $array_aditional_files->apellidomaterno = $apellidomaterno;
            $array_aditional_files->nroadherente = $nroadherente;
            $array_aditional_files->empresarut = $empresarut;
            $DB->insert_record('eabcattendance_extrafields', $array_aditional_files);
        }
    }

    public static function enrol_user($course, $newuserid, $gid, $role, $sesionid = null)
    {
        global $DB, $CFG;

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/lib/enrollib.php');                
        $status_enrol = '';

        $enrolinstances = $DB->get_records('enrol', [
            'courseid'      => $course->id,
            'status'        =>  ENROL_INSTANCE_ENABLED,
            'enrol'         => 'manual'
        ], 'sortorder,id');

        if(empty($enrolinstances)){
            throw new \Exception('No existe la instancia de matriculación manual para el curso id: '.$course->id);
        }
        $instance = reset($enrolinstances);

        $enrol = enrol_get_plugin('manual');

        //validar si el usuario ya esta matriculado
        $enrolId = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $newuserid));
        if (!$enrolId) {
            //si no esta matriculado creo la matriculacion y lo asigno al grupo creado anteriormente
            $enrol->enrol_user($instance, $newuserid, $role);
            $status_enrol = 'nuevo';
        } else {
            $get_sessions_user = $DB->get_record('user', array('id' => $newuserid));
            $user_sesion = $DB->get_records('inscripciones_back', array('participanteidentificador' => $get_sessions_user->username, 'id_sesion_moodle' => $sesionid) );
            //si el usuario no esta en este grupo lo registro pero le borro todo el historial
            if(!in_array($gid, array_keys($user_sesion))){
                //busco los grupos del usuario actuales
                //si no esta en el grupo actual limpio el historial
                //soporte para multiples matriculaciones borrar calificaciones
                \local_download_cert\download_cert_utils::clear_attemps_course_user($newuserid, $course->id);
                \local_pubsub\metodos_comunes::clear_attendance_user($newuserid, $course->id);
                \local_pubsub\metodos_comunes::clean_completion_critery($newuserid, $course->id);
                \local_pubsub\metodos_comunes::clean_completion_cache_course($newuserid, $course->id);
                $status_enrol = 'rematriculado';      
            }
        }
        groups_add_member($gid, $newuserid);
        return $status_enrol;
    }

    
    public static function clear_attendance_user($userid, $courseid){
        global $DB;
        $modinfo = \get_fast_modinfo($courseid);
        $cms = $modinfo->get_instances_of('eabcattendance');
        foreach($cms as $cm){
            $eabcattendanceids = $DB->get_records('eabcattendance', ['id' => $cm->instance]);
            \mod_eabcattendance\privacy\provider::clear_attemp_eabcattendace($userid, $eabcattendanceids);
        }
    }

    public static function clean_completion_critery($userid, $courseid){
        global $DB;
        //limpiar criterios de completado de actividad por curso
        $completioncrit = $DB->get_records('course_completion_crit_compl', array('userid' => $userid, 'course' => $courseid));
        if ($completioncrit) {
            $DB->delete_records("course_completion_crit_compl", array('userid' => $userid, 'course' => $courseid));
        }
    }

    public static function clean_completion_cache_course($userid, $courseid){
        $key = $userid . '_' . $courseid;
        $completioncache = \cache::make('core', 'completion');
        $completioncache->delete($key);
        $cache = \cache::make('core', 'coursecompletion');
        $cache->delete($key);
    }


    public static function enrol_user_elearning($course, $newuserid, $role, $enrol_passport = false, $password_generate = '')
    {
        // @codingStandardsIgnoreLine
        /** @var \moodle_database $DB */
        global $DB, $CFG;

        $days = 30;
        $current_date = date_create();
        $today = date_timestamp_get($current_date);

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/lib/enrollib.php');

        $enrolinstances = enrol_get_instances($course, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance = $courseenrolinstance;
                break;
            }
        }

        $enrol = enrol_get_plugin('manual');

        $enrolId = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $newuserid));
                
        if (!$enrolId) {
            //si no esta matriculado creo la matriculacion
            $enrol->enrol_user($instance, $newuserid, $role);     
            $enrolId = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $newuserid));       
            $estatus = 'nuevo';
        }else {
            $estatus = 'existente';
            //validar si ya pasaron los 30 dias y no tiene aprobado el curso
            $date_from_enrolment_course = inscripcion_elearning::get_enrol_date($course, $newuserid);
            $days_passed = inscripcion_elearning::interval($date_from_enrolment_course->dateroltime, $today);

            //si existe timecompleted es decir ya finalizo el curso o pasaron los 3 dias desde su matriculacion lo matriculo y vuelvo a matricular
            $finalizate_course = $DB->get_record('course_completions', array('course' => $course, 'userid' => $newuserid));
            
            //validacmos condicion
            $enrolmentback = "select id,id_user_moodle, id_curso_moodle, participanteidregistroparticip from {inscripcion_elearning_back} where id_user_moodle = '".$newuserid."' and id_curso_moodle = '".$course."'  and timereported <> 0 order by id desc";

            if (($finalizate_course->timecompleted) || ($days_passed > $days) || ($DB->record_exists_sql($enrolmentback))) {
                //desmatricular si ya finalizo el curso (tiene timecompleted)
                $enrol->unenrol_user($instance, $newuserid);
                //limpiar intentos despues de desmatricular
                $DB->set_field('inscripcion_elearning_back', 'timereported', 0, array('id_curso_moodle' => $course, 'id_user_moodle' => $newuserid ));
                self::clear_user_course_data($newuserid, $course);
                //rematricular
                $enrol->enrol_user($instance, $newuserid, $role);     
                $enrolId = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $newuserid));           
                $estatus = 'rematriculado';
            } 
        }

        \local_mutualnotifications\utils::course_welcome($newuserid, $course, $enrol_passport, $password_generate);
        
        $data = [
            'enrolId'   => $enrolId,
            'estatus'   => $estatus
        ];

        return $data;
    }


    /* validacion de rut formato y codigo verificador
     * validacion de rut de usuarios y empresas, en caso de no tener 
     * el parametro company solo valida rut de usuarios
     */
    public static function validate_rut($rut, $company = false)
    {
        self::validate_format_rut($rut, $company);
        if ($company == false) {
            self::validate_verificator_code($rut);
        }
    }

    /*
     * validacion formato del rut xxxxxxxx-y
     */
    public static function validate_format_rut($rut, $company = false)
    {
        if (!preg_match("/^[0-9]{7,8}+-[0-9kK]{1}$/", $rut)) {
            if ($company) {
                $message = get_string('validaterutcompany', 'mod_eabcattendance');
            } else {
                $message = get_string('validaterutuser', 'mod_eabcattendance');
            }
            echo $message;
//            return false;
        } else {
//            return true;
        }
    }

    /*
     * validate verificator code rut
     */
    public static function validate_verificator_code($rut)
    {
        $rut = explode("-", $rut);
        $dv = $rut[1];
        $numero = $rut[0];

        $i = 2;
        $suma = 0;
        foreach (array_reverse(str_split($numero)) as $v) {
            if ($i == 8)
                $i = 2;

            $suma += $v * $i;
            ++$i;
        }

        $dvr = 11 - ($suma % 11);
        if ($dvr == 11)
            $dvr = 0;
        if ($dvr == 10)
            $dvr = 'K';
        if ($dvr == strtoupper($dv)) {
//            return true;
        } else {
            echo get_string('validatevericatorcode', 'mod_eabcattendance');
        }
    }

    /**
     * @param $rut
     * @param $resp
     * @param $course
     * @param $get_session
     * @param bool $send_mail
     * @return int
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    static public function register_participants($rut, $resp, $course, $get_session, $enrol_passport = false, bool $send_mail = true)
    {
        global $DB;

        $rut = trim(strtolower($rut));

        $get_company = $DB->get_record('company', array('contrato' => $resp['NumeroAdherente']));
        $createuser = array(
            'username' => $rut,
            'firstname' => $resp['ParticipanteNombre'],
            'lastmame' => $resp['ParticipanteApellido1'],
            'email' => $resp['ParticipanteEmail'],
            'sexo' => $resp['IdSexo'],
            'pais' => $resp['ParticipantePais'],
            'participantefechanacimiento' => 0,
            'roles' => null,
            'apellidomaterno' => $resp['ParticipanteApellido2'],
            'nroadherente' => $resp['NumeroAdherente'],
            'empresarut' => $get_company->rut,
        );


        $password_generate = self::generarCadenaAleatoria(6,  $createuser["empresarut"]);
        $get_user = $DB->get_record("user", array("username" => $rut));
        if(!empty($get_user)) {
            $password_generate = 'Clave enviada anteriormente';
        }
        $newuserid = self::create_user($createuser, $course, $enrol_passport, $password_generate);

        $status = self::enrol_user($course, $newuserid, $get_session->groupid, 5, $get_session->id);

        if($send_mail){
            \local_mutualnotifications\utils::course_welcome_streaming_presencial($newuserid, $course, $get_session, $enrol_passport, $password_generate);
        }
       
        $event = \local_pubsub\event\session_participants::create(
            array(
                'context' => \context_system::instance(),
                'other' => array(
                    'sesionid' => $get_session->id,
                    'userid' => $newuserid
                ),
            )
        );
        $event->trigger();

        return $newuserid;
    }

    /**
     * @param $username
     * @param $resp
     * @param $course
     * @param $get_session
     * @return int
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    static public function register_participant_elearning($username, $externaluser, $course, $enrol_passport = false, $guid_inscripcion_elearning = null)
    {      
        global $DB;
        $createuser = array(
            'username' => $username,
            'firstname' => $externaluser['ParticipanteNombre'],
            'lastmame' => $externaluser['ParticipanteApellido1'],
            'email' => $externaluser['ParticipanteEmail'],
            'sexo' => $externaluser['ParticipanteIdSexo'],
            'pais' => $externaluser['ParticipantePais'],
            'participantefechanacimiento' => $externaluser['ParticipanteFechaNacimiento'],
            'roles' => null,
            'apellidomaterno' => $externaluser['ParticipanteApellido2'],
            'nroadherente' => $externaluser['ParticipanteRutAdherente'].$externaluser['ParticipanteDvAdherente'],
            'empresarut' => $externaluser["ParticipanteRutAdherente"] . '-'. $externaluser["ParticipanteDvAdherente"],            
        );
        
        $password_generate = self::generarCadenaAleatoria(6, $createuser["empresarut"]);
        $get_user = $DB->get_record("user", array("username" => strtolower($username)));
        if(!empty($get_user)) {
            $password_generate = 'Clave enviada anteriormente';
        }
        $newuserid = self::create_user($createuser, $course, $enrol_passport, $password_generate);
                
        //$enrolid    = self::enrol_user_elearning($course, $newuserid , 5);
        $enrol_data = self::enrol_user_elearning($course, $newuserid , 5, $enrol_passport, $password_generate);

        //self::asign_company_by_user($newuserid, $createuser["empresarut"]);

        $event = \local_pubsub\event\inscripcion_elearning::create(
            array(
                'context' => \context_system::instance(),
                'other' => array(
                    'response' => 'Participante registrado id: '.$newuserid,
                ),
            )
        );
        $event->trigger();

        $get_company = $DB->get_record('company', ['rut' => $createuser["empresarut"]]);

        $data = [
            'newuserid'=> $newuserid,
            'enrolid' => $enrol_data['enrolId'],
            'estatus' => $enrol_data['estatus'],
            'companyid' => ($get_company) ? $get_company->id : null,
        ];

        return $data;
    }



    /**
     * @param $attendanceid
     * @param $cm
     * @param $course
     * @param $timestart
     * @param $timesecond
     * @param $group
     * @param $msg
     * @param $courseid
     * @return bool|int
     * @throws coding_exception
     * @throws dml_exception
     */
    static public function create_session($attendanceid, $cm, $course, $timestart, $timesecond, $group, $msg, $courseid)
    {
        $attendance = new \mod_eabcattendance_structure($attendanceid, $cm, $course);
        // Create session.
        $sess = new \stdClass();
        $sess->sessdate = intval($timestart);
        $sess->duration = intval($timesecond);
        $sess->descriptionitemid = 0;
        $sess->description = '';
        $sess->descriptionformat = FORMAT_HTML;
        $sess->direction = '';
        $sess->directionformat = FORMAT_HTML;
        $sess->calendarevent = (int)1;
        $sess->timemodified = time();
        $sess->studentscanmark = 0;
        $sess->autoassignstatus = 0;
        $sess->subnet = '';
        $sess->studentpassword = '';
        $sess->automark = 0;
        $sess->automarkcompleted = 0;
        $sess->absenteereport = get_config('attendance', 'absenteereport_default');
        $sess->includeqrcode = 0;
        $sess->subnet = $attendance->subnet;
        $sess->statusset = 0;
        $sess->groupid = $group->grupo;
        $sess->guid = $msg->guid;
        $sessionid = $attendance->add_session($sess);
        if ($sessionid) {
            $event = \local_pubsub\event\get_sessions::create(
                array(
                    'context' => \context_course::instance($courseid),
                    'other' => array(
                        'data' => 'sesion creada con exito',
                        'sessionid' => $sessionid,
                        'groupid' => $group->id,
                        'guid' => $msg->guid
                    ),
                    'courseid' => $courseid,
                )
            );
            $event->trigger();
        }

        return $sessionid;
    }

    /**
     * @param $createschedule
     * @param $course
     * @return int
     * @throws moodle_exception
     */
    public static function create_group($createschedule, $course)
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . '/group/lib.php');

        $groupname = trim($createschedule["createname"]);
        $courseid = $course->id;

        // Obtener todos los grupos con ese nombre en el curso
        $groups = $DB->get_records('groups', [
            'courseid' => $courseid,
            'name' => $groupname
        ], 'id DESC'); // Orden descendente → el primero es el más reciente

        if (!empty($groups)) {
            // Mantener el más reciente (primer elemento)
            $groups_list = array_values($groups);
            $mostrecent = $groups_list[0];

            // Eliminar los más antiguos
            for ($i = 1; $i < count($groups_list); $i++) {
                groups_delete_group($groups_list[$i]);
            }

            // Retornar el id del grupo más reciente
            return $mostrecent->id;
        }

        // Si no existe, crear uno nuevo
        $newgroupdata = new \stdClass();
        $newgroupdata->name = $groupname;
        $newgroupdata->courseid = $courseid;
        $newgroupdata->description = '';

        return groups_create_group($newgroupdata);
    }



    /**
     * @param $groupid
     * @param $courseid
     * @param $idevento
     * @return bool|int
     * @throws dml_exception
     */
    static public function eabcattendance_course_groups($groupid, $courseid, $idevento)
    {
        global $DB;

        $dataobject = new \stdClass();
        $dataobject->grupo = $groupid;
        $dataobject->curso = $courseid;
        $dataobject->uuid = $idevento;

        // 1. Usamos get_records() para obtener TODOS los registros que coincidan
        //    Los ordenamos por 'id' para tener un criterio (quedarnos con el más antiguo)
        $records = $DB->get_records('eabcattendance_course_groups', [
            'curso' => $courseid,
            'uuid'  => $idevento
        ], 'id DESC');

        if (empty($records)) {
            // 2. Si no hay registros, insertamos uno nuevo
            return $DB->insert_record('eabcattendance_course_groups', $dataobject);

        } else {
            // 3. Si hay uno o MÁS registros, tomamos el primero del array
            $record = array_shift($records); // array_shift() saca el primer elemento

            // 4. Actualizamos ese primer registro
            $dataobject->id = $record->id;
            $DB->update_record('eabcattendance_course_groups', $dataobject);

            // 5. (Limpieza Opcional pero Recomendada)
            //    Si quedaban más elementos en $records, eran duplicados. Los borramos.
            if (!empty($records)) {
                $duplicate_ids = array_keys($records); // Obtiene los IDs de los duplicados
                $DB->delete_records_list('eabcattendance_course_groups', 'id', $duplicate_ids);
            }

            // 6. Devolvemos el ID del registro que actualizamos
            return $record->id;
        }
    }

    /**
     * @param $context
     * @param $other
     * @param int $courseid
     * @throws coding_exception
     */
    static function save_event_sessions($context, $other, $courseid = 0)
    {
        $event = \local_pubsub\event\get_sessions::create(
            array(
                'context' => $context,
                'other' => $other,
                'courseid' => $courseid,
            )
        );
        $event->trigger();
    }

    static function save_event_facilitador($context, $other)
    {
        $event = \local_pubsub\event\get_sessions::create(
            array(
                'context' => $context,
                'other' => $other,
            )
        );
        $event->trigger();
    }


    /**
     * @param $endpoint
     * @param null $params
     * @param string $method
     * @return array
     * @throws dml_exception
     */
    public static function request($endpoint, $params = null, $method = "get")
    {
        //Busco con un get los datos del curso en el backend
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, $endpoint);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

        $header = [
            'Authorization: ' . get_config('local_pubsub', 'tokenapi'),
            'Ocp-Apim-Subscription-Key: ' . get_config('local_pubsub', 'subscriptionkey')
        ];

        if($method === "post") {
            curl_setopt($cURLConnection, CURLOPT_POST, true);
            curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($params));
            $header[] = 'Content-Type: application/json';
        }

        curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, $header);

        $respuesta = curl_exec($cURLConnection);

        $httpcode = curl_getinfo($cURLConnection, CURLINFO_HTTP_CODE);

        curl_close($cURLConnection);

        return [
            "data" => $respuesta,
            "status" => $httpcode
        ];
    }

    static public function create_course_base($data){
        global $CFG;
        require_once $CFG->dirroot . "/course/lib.php";
        $coursenamesetting = get_config('local_pubsub', 'coursename');
        if(!empty($coursenamesetting)){
            $coursename = $data[get_config('local_pubsub', 'coursename')];
        } else {
            $coursename = $data["ProductoCurso"];
        }
        
        $courseshortnamesetting = get_config('local_pubsub', 'courseshortname');
        if(!empty($courseshortnamesetting)){
            $courseshortname = $data[get_config('local_pubsub', 'courseshortname')];
        } else {
            $courseshortname = $data["ProductoCurso"];
        }
        $coursecategorysetting = get_config('local_pubsub', 'coursecategory');
        if(!empty($coursecategorysetting)){
            $coursecategory = $data[get_config('local_pubsub', 'coursecategory')];
        } else {
            $coursecategory = 1;
        }
        
        $data = new \stdClass();
        $data->fullname = $coursename;
        $data->category = $coursecategory;
        $data->shortname = $courseshortname;
        $data->summary = '';
        $data->format = 'eabctiles';
        $course = create_course($data);
        return $course;
    }
    
    
    public static function create_attendance($course) {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . '/course/modlib.php');
        $module = $DB->get_record("modules", array('name' => 'eabcattendance'));
        $attendance = $DB->get_record("eabcattendance", array('course' => $course->id));
        if (empty($attendance)) {
            $newattendance = new \stdClass();
            $newattendance->introeditor = array('text' => '', 'format' => '1', 'itemid' => 745111724,);
            $newattendance->showdescription = 0;
            $newattendance->grade = 100;
            $newattendance->grade_rescalegrades = '';
            $newattendance->gradecat = 1;
            $newattendance->gradepass = '';
            $newattendance->visible = 1;
            $newattendance->visibleoncoursepage = 1;
            $newattendance->groupmode = 1;
            $newattendance->availabilityconditionsjson = '{"op":"&","c":[{"type":"eabcgroup"}],"showc":[true]}';
            $newattendance->course = $course->id;
            $newattendance->coursemodule = 0;
            $newattendance->section = 0;
            $newattendance->module = $module->id;
            $newattendance->modulename = 'eabcattendance';
            $newattendance->instance = 0;
            $newattendance->cmidnumber = '';
            $newattendance->add = 'eabcattendance';
            //crear attendance
            $attendance = add_moduleinfo($newattendance, $course);
            
        }
        return $attendance;
    }
    
    public static function save_rate_huellero($attendance, $get_sesion, $mod_attendance, $get_user, $att){
        global $DB, $CFG;
        require_once $CFG->dirroot . '/mod/eabcattendance/locallib.php';
        require_once $CFG->dirroot . '/lib/filelib.php';
        $rate = get_config("pubsub", "ratehuellero");
        $ratesetting = (!empty($rate)) ? $rate : '100%';

        $rate_huellero = $DB->get_record('eabcattendance_statuses', array('eabcattendanceid' => $attendance->id, 'description' => $ratesetting));

        $userid = 'user' . $get_user->id;
        $remarksid = 'remarks' . $get_user->id;

        $data = new stdClass();
        $data->$userid = $rate_huellero->id;
        $data->$remarksid = '';
        $data->grouptype = 0;
        $data->sessionid = intval($get_sesion->id);
        $data->id = $mod_attendance->id;
        $att->take_from_form_data($data);
    }
    public static function save_event_rate_huellero($context, $other, $courseid) {
        $event = \local_pubsub\event\register_attendance::create(
            array(
                'context' => $context,
                'other' => $other,
                'courseid' => $courseid,
            )
        );
        $event->trigger();
    }
    
    
    public static function save_event_sessionparticipants($context, $data) {
        $event = \local_pubsub\event\session_participants::create(
                        array(
                            'context' => $context,
                            'other' => $data,
                        )
        );
        $event->trigger();
    }

    public static function save_event_response_endpointcrearcursos($context, $data) {
        $event = \local_pubsub\event\response_endpointcursos::create(
                        array(
                            'context' => $context,
                            'other' => $data,
                        )
        );
        $event->trigger();
    }

    public static function save_event_response_endpointsession($context, $data) {
        $event = \local_pubsub\event\response_endpointsession::create(
                        array(
                            'context' => $context,
                            'other' => $data,
                        )
        );
        $event->trigger();
    }
    
    public static function save_event_create_course($context, $data) {
        $event = \local_pubsub\event\create_course::create(
                        array(
                            'context' => $context,
                            'other' => $data,
                        )
        );
        $event->trigger();
    }

    public static function save_event_update_course($context, $data) {
        $event = \local_pubsub\event\update_course::create(
                        array(
                            'context' => $context,
                            'other' => $data,
                        )
        );
        $event->trigger();
    }
    
    public static function save_event_response_facilitator($context, $data) {
        $event = \local_pubsub\event\response_endpointfacilitator::create(
                        array(
                            'context' => $context,
                            'other' => $data,
                        )
        );
        $event->trigger();
    }
    public static function suspend_single_session($guid, $motivo = ''){
        $data = array('Motivo' => $motivo);
        $endpointsuspendsession = get_config('local_pubsub', 'endpointsuspendsession');
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, $endpointsuspendsession . $guid);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
            'Authorization: ' . get_config('local_pubsub', 'tokenapi'),
            'Ocp-Apim-Subscription-Key: ' . get_config('local_pubsub', 'subscriptionkey'),
            'Content-Type:application/json'
        ));
        $respuesta = curl_exec($cURLConnection);
        $httpcode = curl_getinfo($cURLConnection, CURLINFO_HTTP_CODE);
        curl_close($cURLConnection);
        return $httpcode;
    }

    public static function save_event_response_suspend_session($context, $data) {
        $event = \local_pubsub\event\response_endpoint_suspendsesion::create(
                        array(
                            'context' => $context,
                            'other' => $data,
                        )
        );
        $event->trigger();
    }

    public static function verify_coursetype_active($modalidad, $modalidad_distancia){
        $modalidad_active = false;
        switch ($modalidad) {
			case get_config('local_pubsub', 'tipomodalidadpresencial'):
				//si la modalidad es presencial
				//verifico si esta activa
				$modalidad_active = (get_config('local_pubsub', 'curso_presencial_active') == 1) ? true : false ;
				break;
			case get_config('local_pubsub', 'tipomodalidadsemipresencial'):
				//si la modalidad es semi presencial
				//verifico si esta activa
				$modalidad_active = (get_config('local_pubsub', 'curso_semi_presencial_active') == 1) ? true : false ;
				break;
			case get_config('local_pubsub', 'tipomodalidaddistancia'):
				//si la modalidad es distancia
				$modalidad_distancia_elearning  = get_config('local_pubsub', 'modalidaddistanciaelearning');
				$modalidad_distancia_streaming  = get_config('local_pubsub', 'modalidaddistanciastreaming');
				$modalidad_distancia_mobile     = get_config('local_pubsub', 'modalidaddistanciamobile');

				if ($modalidad_distancia == $modalidad_distancia_elearning){
					// Modalidad a Distancia Tipo Elearning
					//verifico si esta activa
					$modalidad_active = (get_config('local_pubsub', 'curso_distancia_elearning_active') == 1) ? true : false ;
				}elseif($modalidad_distancia == $modalidad_distancia_streaming){
					// Modalidad a Distancia Tipo Streming
					$modalidad_active = (get_config('local_pubsub', 'curso_distancia_streaming_active') == 1) ? true : false ;
				}elseif($modalidad_distancia == $modalidad_distancia_mobile){
					// Modalidad a Distancia Tipo Mobile
					$modalidad_active = (get_config('local_pubsub', 'curso_distancia_mobile_active') == 1) ? true : false ;
				}else{
					$modalidad_active = false;
				}  
				break;
			default:
				$modalidad_active = false;
        }
        return $modalidad_active;
    }

    /**
     * Reseteo de datos de usuario en curso
     * @param $userid 
     * @param $courseid
     */
    public static function clear_user_course_data($userid, $courseid)
    {
        /** @var \moodle_database $DB */
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');
        require_once($CFG->dirroot . '/lib/gradelib.php');
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

        $DB->delete_records('course_completions', array('userid' => $userid, 'course' => $courseid));
        $DB->set_field('inscripcion_elearning_back', 'timereported', 0, array('id_curso_moodle' => $courseid, 'id_user_moodle' => $userid));

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
        //limpiar registros de local cron
        $DB->delete_records("mutual_log_local_cron", array('userid' => $userid, 'courseid' => $courseid));

        //reset assign
        $params = array('courseid' => $courseid);
        $sql = "SELECT a.id FROM {assign} a WHERE a.course=:courseid";
        if ($assigns = $DB->get_records_sql($sql, $params)) {
            foreach ($assigns as $assign) {
                $DB->delete_records('assign_submission', array('assignment' => $assign->id, 'userid' => $userid ));
                $DB->delete_records('assign_grades', array('assignment' => $assign->id, 'userid' => $userid ));
            }
        }
        //limpiar datos envio manual
        $send_course = $DB->get_records('format_eabctiles_send_course', array("userid" => $userid, "courseid" => $courseid));
        if(!empty($send_course)){
            $DB->delete_records('format_eabctiles_send_course', array("userid" => $userid, "courseid" => $courseid));
        }
        grade_user_unenrol($courseid, $userid);
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
        require_once($CFG->dirroot . '/question/engine/lib.php');

        $qubaids = new qubaids_for_users_attempts($quizid, $userid);
        $params = [
            'quiz'   => $quizid,
            'userid' => $userid,
        ];
        $DB->delete_records('quiz_attempts', $params);
        $DB->delete_records('quiz_grades', $params);
    }

    public static function generarCadenaAleatoria($longitud, $empresarut) {
        global $DB;

        $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $longitudCaracteres = strlen($caracteres);
        $cadenaAleatoria = '';
        $get_secure_password = null;

        for ($i = 0; $i < $longitud; $i++) {
            $indiceAleatorio = rand(0, $longitudCaracteres - 1);
            $caracterAleatorio = $caracteres[$indiceAleatorio];
            $cadenaAleatoria .= $caracterAleatorio;
        }

        $sql = "select * 
            from {local_password_company} as l 
            join {company} as c on c.id = l.companyid
            where c.rut = '" . $empresarut . "' ";
        $get_secure_password = $DB->get_records_sql($sql);

        if(empty($get_secure_password)) {
            return null;
        }
        return $cadenaAleatoria . "*1";
    }

        public static function asign_company_by_user($userid, $empresarut){
            try {
                global $DB;
                $get_company = $DB->get_record('company', array('rut' => $empresarut));
                if(empty($get_company)) {
                    return null;
                }
    
                $get_company_user = $DB->get_record('company_users', array('userid' => $userid, 'companyid' => $get_company->id));
                if(!empty($get_company_user)) {
                    return $get_company_user->id;
                }
    
                $dataobject = new \stdClass();
                $dataobject->userid = $userid;
                $dataobject->companyid = $get_company->id;
                $dataobject->departmentid = 0;
                $dataobject->managertype = 0;
                return $DB->insert_record('company_users', $dataobject);
            } catch (Exception $e) {
                return null;
            }
    
    
    
        }

    /**
     * @param $attendance
     * @param $cm
     * @param $course
     * @param $timestart
     * @param $timesecond
     * @param $group
     * @param $sessionguid
     * @return bool|int|null
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function create_or_update_session($attendance, $cm, $course, $timestart, $timesecond, $group, $sessionguid)
        {
            global $DB;
    
            $existingsession = $DB->get_record("eabcattendance_sessions", ["guid" => $sessionguid]);
    
            if ($existingsession) {
                $dataobject = new \stdClass();
                $dataobject->id = $existingsession->id;
                $dataobject->sessdate = $timestart;
                $dataobject->duration = $timesecond;
                $dataobject->groupid = $group->grupo;
                $DB->update_record("eabcattendance_sessions", $dataobject);
                return $existingsession->id;
            } else {
                $msg = new \stdClass();
                $msg->guid = $sessionguid;
                try {
                    return self::create_session($attendance, $cm, $course, $timestart, $timesecond, $group, $msg, $course->id);
                } catch (\dml_write_exception $e) {
                    $existingsession = $DB->get_record("eabcattendance_sessions", ["guid" => $sessionguid]);
                    return $existingsession->id ?? null;
                }
            }
        }
    }
