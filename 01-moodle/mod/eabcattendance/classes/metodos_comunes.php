<?php
// (25/11/2019 FHS)
//Definicion de la clase eabcattendance_metodos_comunes.

/* Contiene metodos para:
 
	* Crear usuarios                                    			[crear_usuario($user)]
	* Enviar los datos de las altas de usuario por post 			[post_user_fields($user)]
	* Enrolar usuarios en cursos                        			[enrolar_usuario($user_id, $course_id)] 
	* Asignar un usuario a un grupo en un curso         			[asignar_grupo_usuario($user_id, $group_id)]
	* Hacer campos de usuario personalizados						[make_custom_user_fields()]  
	* Validar RUT													[validar_rut($rut)]  
 
*/

namespace mod_eabcattendance;


use coding_exception;
use dml_exception;
use moodle_exception;
use profile_field_customdata;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG, $DB, $OUTPUT, $PAGE, $COURSE;

//require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/profile/definelib.php');
require_once($CFG->dirroot . '/user/profile/field/menu/define.class.php');
require_once($CFG->dirroot . '/user/profile/field/text/define.class.php');
require_once($CFG->dirroot . '/user/profile/field/datetime/define.class.php');
require_once($CFG->dirroot . '/mod/eabcattendance/locallib.php');
require_once($CFG->dirroot . '/user/lib.php'); //To create a new user (20/11/2019 FHS)
require_once($CFG->dirroot . '/group/lib.php');

class metodos_comunes
{


    public function __construct()
    {

        return 0;
    }

    /**
     * @param $user
     * @return int
     * @throws moodle_exception
     * @throws dml_exception
     */
    static public function crear_usuario($user, $enrol_passport = false, $password_generate = "")
    {
        global $DB;
        // it get a objet with the user data and return the new user ID

        // Add missing required fields.
        $user->confirmed = 1;
        $user->deleted = 0;
        $user->timezone = '99';
        $user->mnethostid = 1;
        $user->auth = 'manual';
        if(!empty($password_generate)) {
            $user->password = $password_generate;
        } else {
            $user->password = $user->username;
            if($enrol_passport == true) {
                $user->password = $user->username . '-';
            }
        }

        // Register new user (20/11/2019 FHS)
        $exist = $DB->get_record('user', ["username" => $user->username]);
        if(empty($exist)) {
            $usr = user_create_user($user);
            //pido cambio de clave para proximo ingreso
            set_user_preference('auth_forcepasswordchange', 1, $usr);
        } else {
            $usr = $exist->id;
        }

        $user->userid = $usr;

        // Save custom profile fields data.
        self::save_extrafields($user);

        return $usr;
    }

    /**
     * @param $user
     * @return array|bool
     * @throws dml_exception
     * @throws moodle_exception
     */
    static public function post_user_fields($user)
    {

        global $DB;

        $attendance_sesions = $DB->get_records('eabcattendance_sessions', ['groupid' => $user->group]);

        $ret = false;

        $endpoint = get_config('eabcattendance', 'endpointparticipantes');

        if(empty($endpoint)) {
            throw new moodle_exception("Debe configurar el endpoint de registro de participantes");
        }

        foreach ($attendance_sesions as $attendance_sesion) {
            if (!empty($attendance_sesion)) {
                $guidsessionbd = $DB->get_record('sesion_back', array('id_sesion_moodle' => $attendance_sesion->id));
                $guid_sesion = $guidsessionbd->idsesion;                
                $data = [
                    "IdSesion" => $guid_sesion,
                    'IdEvento' => $guidsessionbd->idevento,
                    "IdRolParticipante" => $user->rol,
                    "ParticipanteIdentificador" => $user->username,
                    "ParticipanteTipoDocumento" => ($user->tipodoc == 2) ? 100 : 1,
                    "ParticipanteNombre" => $user->firstname,
                    "ParticipanteApellido1" => $user->lastname,
                    "ParticipanteApellido2" => $user->apellidomaterno,
                    "ParticipantePais" => $user->pais,
                    "ParticipanteEmail" => $user->email,
                    "ParticipanteFono" => "", // 56957826674
                    "ResponsableIdentificador" => $user->empresarut,
                    "ResponsableNombres" => "",
                    "ResponsableEmail" => "",
                    "NumeroAdherente" => $user->nroadherente,
                    "IdSexo" => (int)$user->participantesexo,
                    "ParticipanteNacimiento" => date('Y-m-d', $user->participantefechanacimiento)
                ];
                $response = \local_pubsub\metodos_comunes::request($endpoint, $data, 'post');
                if ($response["status"] > 299 ) {
                    throw new moodle_exception($response["data"]);
                } else {
                    $ret = array($response["status"], json_decode($response["data"]));
                }
            }
        }

        return $ret;
    }

    static public function post_user_fields_multi($users, $guid_sesion, $guid_evento, $identificador_proceso)
    {
        $endpoint = get_config('eabcattendance', 'endpointupdateparticipantes');
    
        if (empty($endpoint)) {
            throw new moodle_exception("Debe configurar el endpoint update participantes");
        }
    
        $inscripciones = [];
        $actualizaciones = [];
        $countInscripciones = 0;
        $countActualizaciones = 0;
        
        foreach ($users as $user) {
            $rut = explode('-', $user->username);
            $userData = [
                "Apellido1" => $user->lastname,
                "Apellido2" => $user->apellidomaterno,
                "Cargo" => "",
                "Dv" => $rut[1],
                "Email" => $user->email,
                "FechaNacimiento" => date('Y-m-d', $user->participantefechanacimiento),
                "IdRol" => $user->rol,
                "IdSexo" => (int)$user->participantesexo,
                "Nombre" => $user->firstname,
                "NumeroContrato" => (string)$user->nroadherente,
                "Pais" => (int)$user->pais,
                "Pasaporte" => ($user->tipodoc == 2) ? $user->username : null,
                "Rut" => $rut[0],
                "TipoDocumento" => ($user->tipodoc == 2) ? 100 : 1,
                "ResponsableIdentificador" => $user->empresarut,
                "ResponsableNombres" => "",
                "ResponsableEmail" => ""
            ];
    
            if ($user->tipo == 'inscripcion') {
                $inscripciones[] = $userData;
                $countInscripciones++;
            } elseif ($user->tipo == 'actualizacion') {
                // Agregar campos específicos de "Actualizaciones"
                $userData["IdRegistroDynamics"] = $user->idregistrodynamics;
                $userData["IdRegistroFront"] = $user->idregistrofront;
                $actualizaciones[] = $userData;
                $countActualizaciones++;
            }
        }
    
        $data = [
            "Operacion" => "MASIVO",
            "IdentificadorProceso" => $identificador_proceso,
            "IdSesion" => $guid_sesion,
            "IdEvento" => $guid_evento,
            "Inscripciones" => $inscripciones,
            "Actualizaciones" => $actualizaciones
        ];
    
        $response = \local_pubsub\metodos_comunes::request($endpoint, $data, 'post');
    
        return array(
            'status' => $response["status"], 
            'data' => json_decode($response["data"]),
            'total_inscripciones' => $countInscripciones,
            'total_actualizaciones' => $countActualizaciones
        );
    }
    
    /**
     * @param $user
     * @return array|bool
     * @throws dml_exception
     * @throws moodle_exception
     */
    static public function update_user_fields($user)
    {

        global $DB;

        $attendance_sesions = $DB->get_records('eabcattendance_sessions', ['groupid' => $user->group]);

        $ret = false;

        $endpoint = get_config('eabcattendance', 'endpointupdateparticipantes');

        if(empty($endpoint)) {
            throw new moodle_exception("Debe configurar el endpoint de actualización de participantes");
        }

        foreach ($attendance_sesions as $attendance_sesion) {
            if (!empty($attendance_sesion)) {
                $guidsessionbd = $DB->get_record('sesion_back', array('id_sesion_moodle' => $attendance_sesion->id));
                $guid_sesion = $guidsessionbd->idsesion;
                $rut = explode('-', $user->username);
                $data = [
                    "Operacion" => "UPD",
                    "IdSesion" => $guid_sesion,
                    'IdEvento' => $guidsessionbd->idevento,
                    "IdRegistroDynamics" => $user->id_interno,
                    "IdRegistroFront" => $attendance_sesion->id,
                    "Participante" => [
                        "Apellido1" => $user->lastname,
                        "Apellido2" => $user->apellidomaterno,
                        "Cargo" => $user->profile_field_participantecargo,
                        "Dv" => substr($user->username, -1),
                        "Email" => $user->email,
                        "FechaNacimiento" => date('Y-m-d\TH:i:s', $user->participantefechanacimiento) . 'Z',
                        "IdRol" => $user->rol,
                        "IdSexo" => (int)$user->participantesexo,
                        "Nombre" => $user->firstname,
                        "NumeroContrato" => $user->profile_field_empresacontrato,
                        "Pais" => $user->pais,
                        "Pasaporte" => null,
                        "Rut" => $rut[0],
                        "TipoDocumento" => ($user->tipodoc == 2) ? 100 : 1
                    ]
                ];


                $response = \local_pubsub\metodos_comunes::request($endpoint, $data, 'post');
                
                if ($response["status"] > 299 ) {
                    throw new moodle_exception($response["data"]);
                } else {
                    $ret = array($response["status"], json_decode($response["data"]));
                }
            }
        }

        return $ret;
    }

    static public function enrolar_usuario($user_id, $course_id)
    {
        //It gets user ID and couse ID

        global $DB;

        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course_id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance = $courseenrolinstance;
                break;
            }
        }
        $enrol->enrol_user($instance, $user_id, 5);

        set_user_preference('migrado45', 2, $user_id);

        //busco la matriculacion y la devuelvo
        $enroled = $DB->get_record_sql('SELECT * FROM {user_enrolments} WHERE userid = ' . $user_id . ' ORDER BY id DESC LIMIT 1');
        //	echo var_dump($enroled);
        return $enroled;
    }


    static public function asignar_grupo_usuario($group_id, $user_id)
    {

        groups_add_member($group_id, $user_id);
    }


    /**
     * @return bool
     * @throws dml_exception
     */
    static public function make_custom_user_fields()
    {

        global $DB;

        try {
	//Definicion de cada campo segun su tipo (menu, text, date)
            $campo1 = new \profile_define_menu();
//            $campo3 = new \profile_define_text();			RUT empresa   (fuera de uso)
            $campo4 = new \profile_define_datetime();
            $campo5 = new \profile_define_menu();
            $campo7 = new \profile_define_text();
			$campo8 = new \profile_define_text();
			
	//Busqueda de los campos ya creados por su nombre (name), si se recupera el registro el campo no se crea
            $campo1_viejo = $DB->get_records_sql('SELECT id FROM {user_info_field} WHERE ' . $DB->sql_compare_text('shortname') . ' = ' . $DB->sql_compare_text(':shortname'), ['shortname' => 'participantesexo']);
//            $campo3_viejo = $DB->get_records_sql('SELECT id FROM {user_info_field} WHERE ' . $DB->sql_compare_text('shortname') . ' = ' . $DB->sql_compare_text(':shortname'), ['shortname' => 'empresarut']);
            $campo4_viejo = $DB->get_records_sql('SELECT id FROM {user_info_field} WHERE ' . $DB->sql_compare_text('shortname') . ' = ' . $DB->sql_compare_text(':shortname'), ['shortname' => 'participantefechanacimiento']);
            $campo5_viejo = $DB->get_records_sql('SELECT id FROM {user_info_field} WHERE ' . $DB->sql_compare_text('shortname') . ' = ' . $DB->sql_compare_text(':shortname'), ['shortname' => 'Roles']);
            $campo7_viejo = $DB->get_records_sql('SELECT id FROM {user_info_field} WHERE ' . $DB->sql_compare_text('shortname') . ' = ' . $DB->sql_compare_text(':shortname'), ['shortname' => 'apellidom']);
			$campo8_viejo = $DB->get_records_sql('SELECT id FROM {user_info_field} WHERE ' . $DB->sql_compare_text('shortname') . ' = ' . $DB->sql_compare_text(':shortname'), ['shortname' => 'NumeroAdherente']);

	
	//Setting de los campos, se establecen sus propiedades y se guardan en la base de datos
	
	//Selector de sexo del participante
            $data_c1 = new stdClass();
            $data_c1->id = 0;
            $data_c1->action = "editfield";
            $data_c1->datatype = "menu";
            $data_c1->shortname = "participantesexo";
            $data_c1->name = "Sexo";
            $data_c1->description = "Sexo";
            $data_c1->required = "1";
            $data_c1->locked = "0";
            $data_c1->forceunique = "0";
            $data_c1->signup = "1";
            $data_c1->visible = "2";
            $data_c1->categoryid = "1";
            $data_c1->param1 = "M\nF";
            $data_c1->defaultdata = "M";
            $data_c1->submitbutton = "Guardar cambios";
            $data_c1->descriptionformat = "1";
            if (!$campo1_viejo) {
	//Lo guarda solo si no se encontro el campo en la base de datos
                $campo1->define_save($data_c1);
            }

/*
	//RUT de la empresa
            $data_c3 = new \stdClass();
            $data_c3->id = 0;
            $data_c3->action = "editfield";
            $data_c3->datatype = "text";
            $data_c3->shortname = "empresarut";
            $data_c3->name = "RUT empresa";
            $data_c3->description = "RUT empresa";
            $data_c3->required = "1";
            $data_c3->locked = "0";
            $data_c3->forceunique = "0";
            $data_c3->signup = "1";
            $data_c3->visible = "2";
            $data_c3->categoryid = "1";
            $data_c3->defaultdata = "";
            $data_c3->param1 = 30;
            $data_c3->param2 = 2048;
            $data_c3->param3 = "0";
            $data_c3->param4 = "";
            $data_c3->param5 = "";
            $data_c3->submitbutton = "Guardar cambios";
            $data_c3->descriptionformat = "1";
            if (!$campo3_viejo) {
                $campo3->define_save($data_c3);
            }
*/



	//Fecha de nacimiento del participante
            $data_c4 = new stdClass();
            $data_c4->id = 0;
            $data_c4->action = "editfield";
            $data_c4->datatype = "datetime";
            $data_c4->shortname = "participantefechanacimiento";
            $data_c4->name = "Fecha de Nacimiento";
            $data_c4->description = "Fecha de nacimiento";
            $data_c4->required = "1";
            $data_c4->locked = "0";
            $data_c4->forceunique = "0";
            $data_c4->signup = "1";
            $data_c4->visible = "2";
            $data_c4->categoryid = "1";
            $data_c4->param1 = "2019";
            $data_c4->param2 = "2019";
            $data_c4->startday = 1;
            $data_c4->startmonth = 1;
            $data_c4->startyear = 1;
            $data_c4->endday = 1;
            $data_c4->endmonth = 1;
            $data_c4->endyear = 1;
            $data_c4->defaultdata = 0;
            $data_c4->submitbutton = "Guardar cambios";
            $data_c4->descriptionformat = "1";
            if (!$campo4_viejo) {
                $campo4->define_save($data_c4);
            }


    //Selector de roles        
    //Creo el selector segun los roles ingresado en el settings.php

            $guid_roles = explode("\n", get_config('eabcattendance', 'guidroles'));
            $i = 0;

            foreach ($guid_roles as $r) {

                $guid_roles[$i] = explode("/", $r);
                $i++;
            }
            //	echo var_dump($guid_roles);

            $data_c5 = new stdClass();
            $data_c5->id = 0;
            $data_c5->action = "editfield";
            $data_c5->datatype = "menu";
            $data_c5->shortname = "Roles";
            $data_c5->name = "Roles";
            $data_c5->description = "Roles";
            $data_c5->required = "1";
            $data_c5->locked = "0";
            $data_c5->forceunique = "0";
            $data_c5->signup = "1";
            $data_c5->visible = "2";
            $data_c5->categoryid = "1";
            $data_c5->param1 = "";
            
            foreach ($guid_roles as $r) {
                $data_c5->param1 = $data_c5->param1 . $r[1] . "\n";
            }
            
            $data_c5->defaultdata = "Trabajador";
            $data_c5->submitbutton = "Guardar cambios";
            $data_c5->descriptionformat = "1";
            if (!$campo5_viejo) {
                $campo5->define_save($data_c5);
            }



	//Apellido materno
            $data_c7 = new stdClass();
            $data_c7->id = 0;
            $data_c7->action = "editfield";
            $data_c7->datatype = "text";
            $data_c7->shortname = "apellidom";
            $data_c7->name = "Apellido materno";
            $data_c7->description = "Apellido materno del nuevo usuario";
            $data_c7->required = "1";
            $data_c7->locked = "0";
            $data_c7->forceunique = "0";
            $data_c7->signup = "1";
            $data_c7->visible = "2";
            $data_c7->categoryid = "1";
            $data_c7->defaultdata = "";
            $data_c7->param1 = 30;
            $data_c7->param2 = 2048;
            $data_c7->param3 = "0";
            $data_c7->param4 = "";
            $data_c7->param5 = "";
            $data_c7->submitbutton = "Guardar cambios";
            $data_c7->descriptionformat = "1";
            if (!$campo7_viejo) {
                $campo7->define_save($data_c7);
            }
           
           
	//Numero de adherente
            $data_c8 = new stdClass();
            $data_c8->id = 0;
            $data_c8->action = "editfield";
            $data_c8->datatype = "text";
            $data_c8->shortname = "NumeroAdherente";
            $data_c8->name = "Numero de adherente";
            $data_c8->description = "Numero de adherente del nuevo usuario";
            $data_c8->required = "1";
            $data_c8->locked = "0";
            $data_c8->forceunique = "0";
            $data_c8->signup = "1";
            $data_c8->visible = "2";
            $data_c8->categoryid = "1";
            $data_c8->defaultdata = "";
            $data_c8->param1 = 30;
            $data_c8->param2 = 2048;
            $data_c8->param3 = "0";
            $data_c8->param4 = "";
            $data_c8->param5 = "";
            $data_c8->submitbutton = "Guardar cambios";
            $data_c8->descriptionformat = "1";
            if (!$campo8_viejo) {
                $campo8->define_save($data_c8);
            }
           
            
        } catch (Exception $e) {

            echo 'Excepcion : ', $e->getMessage(), "\n";

            return false;
        }

        return true;
    }


    //Retorna true cuando es valido
    static public function validar_rut($rut)
    {

        if (!is_string($rut)) {
            return false;
        }
    
        if (!preg_match("/^[0-9]{7,8}+-[0-9kK]{1}$/", $rut)) {
            return false;
        }
	    if (strlen($rut) > 10){
            return false;
        }

        if(strlen($rut) == 9) {
            $rut = '0' . $rut;
        }
        
        $rut = preg_replace('/[^k0-9]/i', '', $rut);
        $dv = substr($rut, -1);
        $numero = substr($rut, 0, strlen($rut) - 1);

        if (!is_numeric($numero)) {
            return false;
        }

        if (strlen($numero) < 8){
            return false;
        }

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
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param stdClass $user
     * @throws dml_exception
     */
    public static function save_extrafields(stdClass $user)
    {
        global $DB;
        $genero = 'H';

        switch ($user->participantesexo) {
            case 1:
                $genero = 'H';
                break;
            case 2:
                $genero = 'M';
                break;
            case 3:
                $genero = 'O';
                break;
            default:
                $genero = 'H';
                break;
        }
        $array_aditional_files = array(
                        "empresarut"            => $user->empresarut,
                        "empresarazonsocial"    => $user->empresarazonsocial,
                        "empresacontrato"       => $user->nroadherente,
                        "participantesexo"      => $genero,
                        "participantefechanacimiento" => $user->participantefechanacimiento,
                        "Roles"                 => $user->rol,
                        "RUT"                   => $user->tname,
                        "apellidom"             => $user->apellidomaterno
                    ); 

        //Guardo en campos personalizados del usuario
        profile_save_custom_fields($user->userid, $array_aditional_files);

        //Se busca la empresa 
                    //Se busca la empresa 
        //Se busca la empresa 
        /* $get_company_by_rut = $DB->get_record('company', array('rut' => $array_aditional_files['empresarut']));
        if (!empty($get_company_by_rut)) {
            // Si la empresa existe obtengo el Id
            $companyid = $get_company_by_rut->id;
        }else{
            // Si la empresa No existe se registra y obtengo el ID
            $dataempresa                = new stdClass();
            $dataempresa->rut           = $array_aditional_files['empresarut'];
            $dataempresa->contrato      = $array_aditional_files['empresacontrato'];
            $dataempresa->razon_social  = $array_aditional_files['empresarazonsocial'];
            $companyid      = \local_mutual\front\utils::create_company($dataempresa);            
                        $companyid      = \local_mutual\front\utils::create_company($dataempresa);            
            $companyid      = \local_mutual\front\utils::create_company($dataempresa);            
        }
        // Se le asigna la empresa al usuario
        \local_mutual\front\utils::assign_user_company($companyid, $user->userid); */

    }

    /**
     * @param $mform
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function add_extrafields(\MoodleQuickForm $mform) {
        

        $options = array();
        $guid_roles = explode("\n", get_config('eabcattendance', 'guidroles'));

        foreach ($guid_roles as $r) {
            if(!empty($r)){
                $option = explode("/", $r);
                $options[$option[0]] = $option[1];
            }
        }
        $mform->addElement('select', 'rol', "Rol", $options);
        $mform->addRule('rol', get_string('required'), 'required', null, 'client');
        $mform->setDefault('rol','56b5d471-fe15-ea11-a811-000d3a4f6db7');

        $mform->addElement('text', 'nroadherente','Número de adherente');
        $mform->setType('nroadherente', PARAM_RAW);
        $mform->addRule('nroadherente', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'empresarut','RUT empresa');
        $mform->setType('empresarut', PARAM_RAW);
        $mform->addRule('empresarut', get_string('required'), 'required', null, 'client');
    }
    
    
    public static function save_event_register_participant($context, $data){
        $event = \mod_eabcattendance\event\eabcattendance_request_registerparticipants::create(
                        array(
                            'context' => $context,
                            'other' => $data
                        )
        );
        $event->trigger();
    }

    public static function get_country_name_by_id($id){
        $country = '';
        if($id == 1){
            $country = "Chilena";
        } else if($id == 2) {
            $country = "Extranjera";
        }
        return $country;
    }
    
    public static function get_sexo_name_by_id($id){
        $sexo = '';
        if($id == 1){
            $sexo = "M";
        } else if($id == 2) {
            $sexo = "F";
        }
        return $sexo;
    }
    
    public static function get_role_by_id($id){
        $guid_roles = explode("\n", get_config('eabcattendance', 'guidroles'));


        foreach ($guid_roles as $r) {
            if(!empty($r)){
                $option = explode("/", $r);
                $options[$option[0]] = $option[1];
            }
        }
        if(array_key_exists($id, $options)){
            return $options[$id];
        } else {
            return "";
        }
    }

    public static function get_data_download($sessionid, $abierto, $course){
        global $DB, $CFG, $COURSE;
        
        $session = $DB->get_record('eabcattendance_sessions', array('id' => $sessionid));
        
        //data de usuarios
        $groupusers = groups_get_groups_members($session->groupid, ['username']);
        
        $get_session = $DB->get_record("sesion_back", array("id_sesion_moodle" => $sessionid));
                
        $estado = '';
        $adherentename = '';
        $adherentenumber = '';
        $adherenterut = '';
        if(!empty($get_session)){
            $estado = $get_session->estado;
            $adherentenumber = $get_session->numeroadherente;
            $adherenterut = $get_session->rutadherente;
            $adherentename = $get_session->nombreadherente;
        } 
        $dataheaders = array();
        $exporttitle = array();
        $datafile = array();
        $bodytable = array();
        $facilitador = array();
        //cabeceras archivo en caso de abierto o cerrado
        if($abierto == $estado){
            $dataheaders = array(
                array(get_string('course'), $COURSE->fullname),
                array(get_string('date', 'eabcattendance'), Date('d/m/Y', $session->sessdate)),
                array(get_string('hora', 'eabcattendance'), Date('H:m', $session->sessdate)),
                array(get_string('duration', 'eabcattendance'), ($session->duration/60)),
            );
            
            $exporttitle = array(
                get_string('rut', 'eabcattendance'),
                get_string('name'),
                get_string('lastname'),
                get_string('genger', 'eabcattendance'),
                get_string('birthdate', 'eabcattendance'),
                get_string('email'),
                get_string('adherentename', 'eabcattendance'),
                get_string('adherentenumber', 'eabcattendance'),
                get_string('adherenterut', 'eabcattendance'),
                get_string('role'),
                get_string('firma', 'eabcattendance'),
            );
        } else {
            $dataheaders = array(
                array(get_string('course'), $COURSE->fullname),
                array(get_string('date', 'eabcattendance'), Date('d/m/Y', $session->sessdate)),
                array(get_string('hora', 'eabcattendance'), Date('H:m', $session->sessdate)),
                array(get_string('duration', 'eabcattendance'), ($session->duration/60)),
                
            );
            $exporttitle = array(
                get_string('rut', 'eabcattendance'),
                get_string('name'),
                get_string('lastname'),
                get_string('genger', 'eabcattendance'),
                get_string('birthdate', 'eabcattendance'),
                get_string('email'),
                get_string('role'),
                get_string('firma', 'eabcattendance'),
            );
        }
        
        foreach ($groupusers as $groupuser) {
            $sexo = '';
            $fechanacimeinto = '';
            $numeroadherente = '';
            $nombreadherente = '';
            $role = '';
            $exportdata = array();
            $extrafield = $DB->get_record('eabcattendance_extrafields', array('userid' => $groupuser->id));
            if(!empty($extrafield)){
                $sexo = self::get_sexo_name_by_id($extrafield->participantesexo);
                $fechanacimeinto = Date('d/m/Y', $extrafield->participantefechanacimiento);
                $numeroadherente = $extrafield->nroadherente;
                $nombreadherente = $extrafield->empresarut;
                $role = self::get_role_by_id($extrafield->rol);
            }
            
            $get_facilitador = get_user_preferences('guid_facilitador', null, $groupuser);
            if(!empty($get_facilitador)){
                $facilitador[] = fullname($groupuser);
            } else {
                if ($abierto == $estado) {
                    $exportdata[] = $groupuser->username;
                    $exportdata[] = $groupuser->firstname;
                    $exportdata[] = $groupuser->lastname;
                    $exportdata[] = $sexo;
                    $exportdata[] = $fechanacimeinto;
                    $exportdata[] = $groupuser->email;
                    $exportdata[] = '';
                    $exportdata[] = $numeroadherente;
                    $exportdata[] = $nombreadherente;
                    $exportdata[] = $role;
                    $exportdata[] = '';
                } else {
                    $exportdata[] = $groupuser->username;
                    $exportdata[] = $groupuser->firstname;
                    $exportdata[] = $groupuser->lastname;
                    $exportdata[] = $sexo;
                    $exportdata[] = $fechanacimeinto;
                    $exportdata[] = $groupuser->email;
                    $exportdata[] = $role;
                    $exportdata[] = '';
                }
                $bodytable[] = $exportdata;
            }
            

        }
        
        $datafile['head'] = $dataheaders;
        $datafile['headerbody'] = $exporttitle;
        $datafile['bodytable'] = $bodytable;
        $datafile['facilitador'] = $facilitador;
        return $datafile;
    }
    
    public static function pdf_attendance($datas){
        global $OUTPUT;
        
        $head = array();
        $facilitadores = array();
        
        foreach($datas['head'] as $data){
            $head[] = array('head' => $data[0], 'data' => $data[1]);
        }
        
        $tableheader = array();
        foreach($datas['headerbody'] as $data){
            $tableheader[] = array('data' => $data);
        }
        
        foreach($datas['facilitador'] as $data){
            $facilitadores[] =  array('data' => $data);
        }
        
        $tablebody = array();
        foreach($datas['bodytable'] as $datas){
            $row = array();
            foreach($datas as $data){
                $row['row'][] =  array('data' => $data);
            }
            $tablebody[] = $row;
        }
        
        $htmlbody = $OUTPUT->render_from_template("mod_eabcattendance/download_nomina_pdf", array('head' => $head, 'tableheader' => $tableheader, 'tablebody' => $tablebody, 'facilitadores' => $facilitadores));
        
        //echo $htmlbody;exit;
        $pdf = new download_nomina_pdf('L');
        // set document information
        $pdf->SetCreator(PDF_CREATOR);

        $tagvs = array(
            'h1' => array(
                0 => array('h' => '', 'n' => 2),
                1 => array('h' => 1.3, 'n' => 1)
            ),
            'div' => array(
                0 => array('h' => 0, 'n' => 0),
                1 => array('h' => 0, 'n' => 0),
                2 => array('h' => 0, 'n' => 0),
                3 => array('h' => 0, 'n' => 0),
                4 => array('h' => 0, 'n' => 0),
                5 => array('h' => 0, 'n' => 0),
                6 => array('h' => 0, 'r' => 0)
            ),
            'table' => array(
                0 => array('h' => 10, 'n' => 10),
                1 => array('h' => 10, 'r' => 10)
            ),
        );
        $pdf->setHtmlVSpace($tagvs);

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(10, PDF_MARGIN_TOP, 10);
        $pdf->SetHeaderMargin(1);
        $pdf->SetTopMargin(40);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set font
        $pdf->SetFont('helvetica', '', 10);

        // add a page
        $pdf->AddPage();

        // output the HTML content
        $pdf->writeHTML($htmlbody, true, 5, true, 5);

        // reset pointer to the last page
        $pdf->lastPage();

        $pdf->Output(get_string('downloadnomina', 'eabcattendance').'.pdf', 'D');

    }
    
    public static function process_enrol_attendance($user, $ret, $course, $output, $filtercontrols, $grupo, $enrol_passport = false, $password_generate = "", $masivo = false){
        global $DB;
        $user = $DB->get_record('user', array('username' => $user->username));

        if ($user) {
            $user_enroll = self::enrolar_usuario($user->id, $course->id);
            //guardo el id de matriculacion junto con el id interno del back en la tabla que los asocia (eabcattendance_enrol_idin)
            $dataObj = new \stdClass();
            $dataObj->id_usr_enrolment = $user_enroll->id;
            $dataObj->id_interno = $ret[1]->IdInterno;
            $DB->insert_record('eabcattendance_enrol_idin', $dataObj);

            $sessgroups = $filtercontrols->get_sess_groups_list();
            
            if ($sessgroups) {
                //Pongo el usuario en un grupo (si los hay) (28/11/2019 FHS)
                if (!empty($grupo)) {
                    $group_id = $grupo;
                } else {
                    $group_id = $filtercontrols->get_current_sesstype();
                }
                self::asignar_grupo_usuario($group_id, $user->id);
                $attendance_sesions = $DB->get_records('eabcattendance_sessions', ['groupid' => $group_id]);
                            foreach ($attendance_sesions as $attendance_sesion) {
                                \local_mutualnotifications\utils::course_welcome_streaming_presencial($user->id, $course, $attendance_sesion, $enrol_passport, $password_generate);
                                self::save_inscripcion_back($ret[1], $attendance_sesion->id);
                            }
                if (!$masivo){
                    echo \html_writer::tag('h3', get_string('userenrolled', 'eabcattendance') . ' (' . $sessgroups[$group_id] . ')', array('class' => 'text-center'));
                }
                return true;
            } else {
                //si no hay grupos, solamente despliego el mensage de usuario enrolado (28/11/2019 FHS)
                if (!$masivo){
                    echo \html_writer::tag('h3', get_string('userenrolled', 'eabcattendance'), array('class' => 'text-center'));
                }
                return true;
            }            
//            echo $output->footer($course);
//            die();
        } else {
            //if user is not found
            echo '<div id="USER_NOT_FOUND">';
            echo '<center>';
            echo '<h3>' . get_string('errorsingup', 'eabcattendance') . '</h3>';
            echo '</center>';
            echo '</div>';
            return false;
        }
    }

    public static function create_and_enrol_attendanceform($mform_signup, $course, $output, $filtercontrols, $context, $enrol_passport = false)
    {
        global $DB;
        if ($user = $mform_signup->get_data()) {
            $grupo = $user->group;
            $ret = \mod_eabcattendance\metodos_comunes::post_user_fields($user);

            //guardo evento
            $data = array(
                'datasend' => json_encode($user),
                'response' => json_encode($ret),
            );

            \mod_eabcattendance\metodos_comunes::save_event_register_participant($context, $data);
            //si back no responde muestro error 
            if (!$ret) {
                echo "<h3>" . get_string('backerror', 'eabcattendance') . "</h3>";
                echo $output->footer($course);
                exit();
            }

            //si es estatus de la respuesta no esta entre 200 y 299 trae un error o mensaje de back para el usuario
            if ($ret[0] > 299) {
                $messa = '';
                if(isset($ret[1]->Mensaje) || isset($ret[1]->Message) || isset($ret[1]->message)){
                    if(isset($ret[1]->Mensaje)){
                        $messa .= 'Error en respuesta de back: ' . $ret[1]->Mensaje.'<br>';
                    }
                    if(isset($ret[1]->Message)){
                        $messa .= 'Error en respuesta de back: ' . $ret[1]->Message.'<br>';
                    }
                    if(isset($ret[1]->message)){
                        $messa .= 'Error en respuesta de back: ' . $ret[1]->message.'<br>';
                    }
                    \core\notification::add($messa);
                }
            } else {
                //si todo esta ok entre 200 y 299
                //verifico si se registro devolviendo el id del enrolamiento en el back
                if (!isset($ret[1]->IdInterno)) {
                    echo \html_writer::tag('h3', get_string('backregistererror', 'eabcattendance'), array('class' => 'text-center'));
                    echo $output->footer($course);
                    exit();
                }
                $password_generate = \local_pubsub\metodos_comunes::generarCadenaAleatoria(6, $user->empresarut);
                $get_user = $DB->get_record("user", array("username" => strtolower($user->username)));
                if(!empty($get_user)) {
                    $password_generate = 'Clave enviada anteriormente';
                }
                //creo el usuario
                $user->id = \mod_eabcattendance\metodos_comunes::crear_usuario($user, $enrol_passport, $password_generate);
                //It fetch user by ID after sign it up, if it is found it display it, else send a error message.
                $process_user = \mod_eabcattendance\metodos_comunes::process_enrol_attendance($user, $ret, $course, $output, $filtercontrols, $grupo, $enrol_passport, $password_generate);
                //todo ok en el registro
                echo $output->footer($course);
                die();
            }
        }

        $mform_signup->display();
    }

        /**
     * Convierte varios formatos de fecha a timestamp.
     * Acepta timestamp, dd/mm/aaaa, dd-mm-aaaa.
     *
     * @param string|int $date Fecha a convertir.
     * @return int|null Timestamp o null si es inválido.
     */
    public static function validate_date_for_set_data($date) {
        if (empty($date)) {
            return null;
        }

        // Si ya es timestamp válido.
        if (is_numeric($date) && (int)$date > 0) {
            return (int)$date;
        }

        // Formatos permitidos.
        $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d'];

        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $date);
            if ($d && $d->getTimestamp()) {
                return $d->getTimestamp();
            }
        }

        // Intentar strtotime como fallback.
        $timestamp = strtotime($date);
        return $timestamp ?: null;
    }


    public static function validate_enrol_user_attendance_form($user, $course, $cm, $enrollform, $output, $filtercontrols){

        // Cargo los campos personalizados del usuario
        profile_load_custom_fields($user);
        
        $usr_inf = array(
            'text_user_name' => get_string('tusername', 'eabcattendance'),
            'text_user_email' => get_string('emailuser', 'eabcattendance'),
            'u_id' => $user->id,
            'u_acc_name' => $user->firstname . ' ' . $user->lastname,
            'u_name' => $user->username,
            'u_email' => $user->email,
            'in_course' => ''
        );

        $context = \context_course::instance($course->id);
        //verifico si no esta matriculado
        /* if (!is_enrolled($context, $user->id, '')) { */
            echo $output->render_from_template('eabcattendance/user_info', $usr_inf);

            $extrafield = new \stdClass();
            $extrafield->id = $cm->id;
            $extrafield->user_id = $user->id;
            $extrafield->tname = $user->username;
            $extrafield->tipodoc = $filtercontrols->tipodoc;
            $extrafield->empresarut         = isset($user->profile['empresarut']) ? $user->profile['empresarut'] : '';
            $extrafield->participantesexo   = ($user->profile['participantesexo'] == 'M') ? 1 : 2;
            $extrafield->nroadherente       = isset($user->profile['empresacontrato']) ? $user->profile['empresacontrato'] : '';
            $extrafield->apellidomaterno    = isset($user->profile['apellidom']) ? $user->profile['apellidom'] : '';
            $extrafield->participantefechanacimiento = isset($user->profile['participantefechanacimiento']) ?
                self::validate_date_for_set_data($user->profile['participantefechanacimiento']) :
                null;
            $extrafield->empresarazonsocial    = $filtercontrols->empresarazonsocial;
                                    
            $enrollform->set_data($extrafield);

            if ($user = $enrollform->get_data()) {  
                $user-> empresarazonsocial = $filtercontrols->empresarazonsocial;             
                self::enrol_user_attendance_form($user, $course, $output, $context, $filtercontrols);
            }
            $enrollform->display();
    }

    public static function enrol_user_attendance_form($user, $course, $output, $context, $filtercontrols, $masivo = false)
    {
        global $DB;
        $userpost = $DB->get_record('user', array('id' => $user->user_id));
        $user->userid = $user->user_id;
        //guardo o actualizo campos nuevos

        self::save_extrafields($user);
        
        $userpost->group = $user->group;

        $obj_usuario = (object) array_merge(
            (array) $userpost,
            (array) $user
        );

        //validamos que tenga los capos opcionales
        if (!empty($user->nroadherente)) {

            $groups_user = groups_get_all_groups($course->id, $user->userid);
            //si el usuario no esta en este grupo lo registro pero le borro todo el historial
            if(in_array($user->group, array_keys($groups_user)) == false){ 
                //busco los grupos del usuario actuales
                //si no esta en el grupo actual limpio el historial
                //soporte para multiples matriculaciones borrar calificaciones
                \local_download_cert\download_cert_utils::clear_attemps_course_user($user->userid, $course->id);
                \local_pubsub\metodos_comunes::clear_attendance_user($user->userid, $course->id);
                \local_pubsub\metodos_comunes::clean_completion_critery($user->userid, $course->id);
                \local_pubsub\metodos_comunes::clean_completion_cache_course($user->userid, $course->id);      
            }
                        
            //envio la data a back
            $ret = \mod_eabcattendance\metodos_comunes::post_user_fields($obj_usuario);

            //guardo evento
            $data = array(
                'datasend' => json_encode($obj_usuario),
                'response' => json_encode($ret),
            );
            \mod_eabcattendance\metodos_comunes::save_event_register_participant($context, $data);
            //si no responde back o hay error muestro un error
            if (!$ret) {
                //validaciones por parte de back
                if (!empty($ret[1])) {
                    echo "<h3>" . get_string('backerror', 'eabcattendance') . " " . $ret[1]->Message . "</h3>";
                } else {
                    echo "<h3>" . get_string('backerror', 'eabcattendance') . "</h3>";
                }
                echo $output->footer($course);
                exit();
            } else {
                //alguna notificacion por parte de back la muestro en pantalla
                if ($ret[0] > 299) {
                    $messa = '';
                    if(isset($ret[1]->Mensaje) || isset($ret[1]->Message)){
                        if(isset($ret[1]->Mensaje)){
                            $messa .= $ret[1]->Mensaje.'<br>';
                        }
                        if(isset($ret[1]->Message)){
                            $messa .= $ret[1]->Message.'<br>';
                        }
                        if(isset($ret[1]->message)){
                            $messa .= $ret[1]->message.'<br>';
                        }
                        \core\notification::add($messa);
                    }
                } else {
                    //registro el usuario que viene de back
                    $user_enroll = self::enrolar_usuario($user->user_id, $course->id);
                    $dataObj = new \stdClass();
                    $dataObj->id_usr_enrolment = $user_enroll->id;
                    $dataObj->id_interno = $ret[1]->IdInterno;
                    $DB->insert_record('eabcattendance_enrol_idin', $dataObj);

                    $sessgroups = $filtercontrols->get_sess_groups_list();
                    if ($user->group) {
                        //Pongo el usuario en un grupo (si los hay) (28/11/2019 FHS)
                        //$group_id = $filtercontrols->get_current_sesstype();
                        if ($user->group==-1){
                            foreach($filtercontrols->get_sess_groups_list() as $idgrupo => $grup){
                                if ($idgrupo!=-1){
                                    \mod_eabcattendance\metodos_comunes::asignar_grupo_usuario($idgrupo, $user->user_id);
                                    $attendance_sesions = $DB->get_records('eabcattendance_sessions', ['groupid' => $idgrupo]);
                                    foreach ($attendance_sesions as $attendance_sesion) {
                                        \local_mutualnotifications\utils::course_welcome_streaming_presencial($user->user_id, $course, $attendance_sesion, false, "Clave enviada anteriormente");
                                    }
                                    echo '<center><h3>' . get_string('userenrolled', 'eabcattendance') . ' (' . $sessgroups[$idgrupo] . ')</h3></center>';
                                }                                
                            }
                        }else{
                            \mod_eabcattendance\metodos_comunes::asignar_grupo_usuario($user->group, $user->user_id);
                            $attendance_sesions = $DB->get_records('eabcattendance_sessions', ['groupid' => $user->group]);
                            foreach ($attendance_sesions as $attendance_sesion) {
                                \local_mutualnotifications\utils::course_welcome_streaming_presencial($user->user_id, $course, $attendance_sesion, false, "Clave enviada anteriormente");
                                self::save_inscripcion_back($ret[1], $attendance_sesion->id);
                            }
                            if(!$masivo){
                                echo '<center><h3>' . get_string('userenrolled', 'eabcattendance') . ' (' . $sessgroups[$user->group] . ')</h3></center>';
                            }
                        }
                        
                    } else {
                        //si no hay grupos, solamente despliego el mensage de usuario enrolado 
                        if(!$masivo){
                            echo '<center><h3>' . get_string('userenrolled', 'eabcattendance') . '</h3></center>';
                        }
                    }
                    //registro el usuario que viene de back                                        
                    //si todo esta ok cierro
                    if(!$masivo){
                        echo $output->footer($course);
                        die();
                    }
                }
            }
        } else {
            echo "<br>Los parámetros participante sexo, Roles, apellido materno, Numero Adherente son obligatorios.<br>";
        }

        
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

    public static function get_quota_session_by_sessionid($sessionid){
        global $DB;
         //LEER LA SESION PARA CONSULTAR CUPOS DISPONIBLES
        $attendance_sesions = $DB->get_record('eabcattendance_sessions', ['id' => $sessionid]);
                
        if(!$attendance_sesions->guid){
            echo 'La sesión no tiene un GUID asociado';
            print_error('guidnotvalid', 'mod_eabcattendance', '', $sessionid);
        }

        $endpoint = get_config('local_pubsub', 'endpointsession') . $attendance_sesions->guid;
        
        $response = \local_pubsub\metodos_comunes::request($endpoint);
        if ($response["status"] > 299) {
            throw new moodle_exception("error request:" . $response["status"] . "endpoint: " . $endpoint);
        }
        $response = json_decode($response["data"], true);
        
        $attendance_sesions->maximoSession = $response['CuposDisponibles'];

        return $attendance_sesions;
    }

    public static function save_inscripcion_back($response, $sesionid){
        global $DB;

        $record = new \stdClass();
        $record->idinterno                  = $response->IdInterno;
        $record->idsexo                     = $response->IdSexo;
        $record->numeroadherente            = $response->NumeroAdherente;
        $record->participanteapellido1      = $response->ParticipanteApellido1;
        $record->participanteapellido2      = $response->ParticipanteApellido2;
        $record->participanteemail          = $response->ParticipanteEmail;
        $record->participantefono           = $response->ParticipanteFono;
        $record->participanteidentificador  = $response->ParticipanteIdentificador;
        $record->participantenombre         = $response->ParticipanteNombre;
        $record->participantepais           = $response->ParticipantePais;
        $record->participantetipodocumento  = $response->ParticipanteTipoDocumento;
        $record->responsableemail           = $response->ResponsableEmail;
        $record->responsableidentificador   = $response->ResponsableIdentificador;
        $record->responsablenombres         = $response->ResponsableNombres;
        $record->id_sesion_moodle           = $sesionid;
        $DB->insert_record('inscripciones_back', $record);
    }
}
