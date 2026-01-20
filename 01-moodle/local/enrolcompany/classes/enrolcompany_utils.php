<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_enrolcompany;

defined('MOODLE_INTERNAL') || die();

global $DB;

use stdClass;
use core_text;
use context_course;

class enrolcompany_utils
{
    
    /**
     * Validation callback function - verified the column line of csv file.
     * Converts column names to lowercase too.
     */
    public static function validate_user_upload_columns(&$columns)
    {
        $processed = array();
        foreach (self::array_colums() as $key => $column) {
            if (!in_array($column, $columns) ) {
                return 'No existe la cabecera "' . $column . '" en el csv';
            }
            if (in_array($column, $processed)) {
                return 'Columna duplicada ' . $column;
            }
            $processed[] = $column;
        }
        
        return true;
    }

    public static function validation_row($user, $username){
        global $DB;
        $error = false;
        $erroredusers = [];
        
        if (!isset($user['ParticipanteNombre']) || $user['ParticipanteNombre'] === '') {
            $erroredusers[] = 'Nombre del participante es obligatorio';
            $error = true;
        }
        if (!isset($user['ParticipanteApellido1']) || $user['ParticipanteApellido1'] === '') {
            $erroredusers[] = 'Primer apellido del participante es obligatorio';
            $error = true;
        }
        if (!isset($user['ParticipanteEmail']) || $user['ParticipanteEmail'] === '') {
            $erroredusers[] = 'Correo del participante es obligatorio';
            $error = true;
        }
        if (empty($username)) {
            $erroredusers[] = 'Documento y dígito verificador son obligatorios para el crear usuario';
            $error = true;
        }
        $existinguser = $DB->get_record('user', array('username' => $username));
        if (!empty($existinguser)) {
            if (is_siteadmin($existinguser->id)) {
                $erroredusers[] = 'No se puede modificar un admin';
                $error = true;
            } 
        } 
        
        $course_back = $DB->get_record('curso_back',array('codigocurso' => $user['CodigoCurso'])); 
        if(empty($course_back)){
            $erroredusers[] = 'Curso no creado desde portal';
            $error = true;
        } else {
            $course = $DB->get_record('course',array('id' => $course_back->id_curso_moodle)); 
            if(empty($course)){
                $erroredusers[] = 'Curso de portal registrado pero no creado en moodle';
                $error = true;
            }
            if(empty($course_back->productoid)){
                $erroredusers[] = 'Curso no tiene guardado productoid';
            }
        }
        
        if (is_numeric($user['ResponsableRUT']) == false) {
            $erroredusers[] = 'El rut del responsable es obligatorio y solo puede ser numérico';
            $error = true;
        }

        if (is_numeric($user['ParticipanteRutAdherente']) == false || empty($user['ParticipanteRutAdherente'])) {
            $erroredusers[] = 'El rut del adherente es obligatorio y solo puede ser numérico';
            $error = true;
        }
        if (self::parse_document_int($user['ParticipanteTipoDocumento']) == 1){
            if (empty($user['ParticipanteRUT'])){
                $erroredusers[] = 'El campo Rut de paticipante no puede ser vacio para TipoDocumento = 1';
                $error = true;
            }
            if (is_numeric($user['ParticipanteRUT']) == false){
                $erroredusers[] = 'El rut del participante solo puede ser numérico';
                $error = true;
            }
        } else if (self::parse_document_int($user['ParticipanteTipoDocumento']) == 100) {
            if (self::parse_document_int($user)){
                $erroredusers[] = 'El campo ParticipantePasaporte no puede ser vacio para TipoDocumento = 100';
                $error = true;
            }          
        } else {
            $erroredusers[] = 'El campo ParticipanteTipoDocumento no corresponde con un valor aceptado (1 - 100) ';
            $error = true;
        }
        return array('error' => $error, 'erroredusers' => $erroredusers);
    }
    public static function process_data_row($line, $columns){
        $user = self::array_user_process();
        $username = ['', ''];
        foreach ($line as $key => $value) {
            $key = $columns[$key];
            if ($value !== '') {
                switch ($key) {
                    case 'Código (Producto (curso)) (Producto)':
                        $user['shortname'] = $value;
                        $user['CodigoCurso'] = $value;
                        break;
                    case 'Nombre Representante':
                        $user['ResponsableNombre'] = $value;
                        break;
                    case 'Apellido Paterno':
                        $user['ResponsableApellido1'] = $value;
                        break;
                    case 'Apellido Materno':
                        $user['ResponsableApellido2'] = $value;
                        break;
                    case 'RUT Persona':
                        $user['ParticipanteRUT'] = $value;
                        $username[0] = $value;
                        break;
                    case 'DV Persona':
                        $user['ParticipanteDV'] = $value;
                        $username[1] = $value;
                        break;
                    case 'Nombre Persona':
                        $user['ParticipanteNombre'] = $value;
                        break;
                    case 'Apellido Paterno Persona':
                        $user['ParticipanteApellido1'] = $value;
                        break;
                    case 'Email Persona':
                        $user['ParticipanteEmail'] = $value;
                        break;
                    case 'Código SUSESO (Género Persona) (Sexo)':
                        $user['ParticipanteIdSexo'] = $value;
                        break;
                    case 'Pais Participante':
                        $user['ParticipantePais'] = $value;
                        break;
                    case 'Fecha de Nacimiento Persona':
                        $user['ParticipanteFechaNacimiento'] = $value;
                        break;
                    case 'Apellido Materno Persona':
                        $user['ParticipanteApellido2'] = $value;
                        break;
                    case 'RUT Adherente':
                        $user['ParticipanteRutAdherente'] = $value;
                        break;
                    case 'dv (Adherente) (Cliente)':
                        $user['ParticipanteDvAdherente'] = $value;
                        break;
                    case 'RUT Representante':
                        $user['ResponsableRUT'] = $value;
                        break;
                    case 'DV':
                        $user['ResponsableDV'] = $value;
                        break;
                    case 'Nro Contrato':
                        $user['nroadherente'] = $value;
                        break;
//                    case 'GUID Producto':
//                        $user['ParticipanteProductId'] = $value;
//                        break;
                    //cambiar a id de curso
                    case '(No modificar) Asistencias Otros Proveedores Capacitación':
                        $user['ParticipanteIdRegistroParticipante'] = $value;
                        break;
                    case 'Fecha Inscripción Portal Mutual':
                        $user['ParticipanteFechaInscripcion'] = $value;
                        break;
                    case 'Tipo de Identificación Persona':
                        $user['ParticipanteTipoDocumento'] = $value;
                        break;
                    case 'Pasaporte Persona':
                        $user['ParticipantePasaporte'] = $value;
                        break;
                    case 'Telefono Móvil Persona':
                        $user['ParticipanteTelefonoMovil'] = $value;
                        break;
                    case 'Teléfono fijo Persona':
                        $user['ParticipanteTelefonoFijo'] = $value;
                        break;
                    case 'Cargo Persona':
                        $user['ParticipanteCargo'] = $value;
                        break;
                    case 'Rol Participante':
                        $user['ParticipanteIdRol'] = $value;
                        break;
                    case 'Código (Comuna) (Comuna)':
                        $user['ParticipanteCodigoComuna'] = $value;
                        break;
                    case 'Dirección':
                        $user['ParticipanteDireccion'] = $value;
                        break;
                    case 'Tipo de Identificación':
                        $user['ResponsableTipoDocumento'] = $value;
                        break;
                    case 'Pasaporte':
                        $user['ResponsablePasaporte'] = $value;
                        break;
                    case 'Fecha de Nacimiento':
                        $user['ResponsableFechaNacimiento'] = $value;
                        break;
                    case 'Código SUSESO (Género) (Sexo)':
                        $user['ResponsableIdSexo'] = $value;
                        break;
                    case 'Email':
                        $user['ResponsableEmail'] = $value;
                        break;
                    case 'Teléfono Móvil':
                        $user['ResponsableTelefonoMovil'] = $value;
                        break;
                    case 'Teléfono Fijo':
                        $user['ResponsableTelefonoFijo'] = $value;
                        break;
                    case 'Cargo':
                        $user['ResponsableCargo'] = $value;
                        break;
                    case 'Código (Comuna Representante) (Comuna)':
                        $user['ResponsableCodigoComuna'] = $value;
                        break;
                    case 'Código (Region) (Región)':
                        $user['ResponsableCodigoRegion'] = $value;
                        break;
                    case 'Dirección Representante':
                        $user['ResponsableDireccion'] = $value;
                        break;
                    case 'Nombre de fantasía (Adherente) (Cliente)':
                        $user['CompanyName'] = $value;
                        break;
                    default:
                       break;
                }
            } 
        }
        return array(
            'data' => $user, 
            'username' => $username
        );
    }

    public static function array_colums(){
        return array(
            '(No modificar) Asistencias Otros Proveedores Capacitación',
            '(No modificar) Suma de comprobación de fila',
            '(No modificar) Fecha de modificación',
            'Propietario',
            'Fecha de creación',
            'Nombre',
            'ID de Proceso',
            'Fecha Inscripción Portal Mutual',
            'Producto (curso)',
            'Código Capacitación (Mutual) (Producto (curso)) (Producto)',
            'Código (Producto (curso)) (Producto)',
            'Codigo unico proveedor (Proveedor) (Proveedores Cursos Distancia)',
            'Nombre Representante',
            'Apellido Paterno',
            'Apellido Materno',
            'Tipo de Identificación',
            'RUT Representante',
            'DV',
            'Pasaporte',
            'Fecha de Nacimiento',
            'Género',
            'Código SUSESO (Género) (Sexo)',
            'Email',
            'Teléfono Fijo',
            'Teléfono Móvil',
            'Cargo',
            'Comuna Representante',
            'Código (Comuna Representante) (Comuna)',
            'Código SUSESO (Comuna Representante) (Comuna)',
            'Region',
            'Código (Region) (Región)',
            'Dirección Representante',
            'Tipo de Identificación Persona',
            'RUT Persona',
            'DV Persona',
            'Pasaporte Persona',
            'Nombre Persona',
            'Apellido Paterno Persona',
            'Apellido Materno Persona',
            'Fecha de Nacimiento Persona',
            'Género Persona',
            'Código SUSESO (Género Persona) (Sexo)',
            'Email Persona',
            'Telefono Móvil Persona',
            'Teléfono fijo Persona',
            'Pais Participante',
            'Cargo Persona',
            'Rol Participante',
            'Dirección',
            'Comuna',
            'Código (Comuna) (Comuna)',
            'Nro Contrato',
            'RUT Adherente',
            'dv (Adherente) (Cliente)',
            'Nombre de fantasía (Adherente) (Cliente)'
        );
    }
    
    public static function array_user_process(){
        return array(
            'ParticipanteNombre' => '',
            'ParticipanteApellido1' => '',
            'ParticipanteEmail' => '',
            'ParticipanteIdSexo' => '',
            'ParticipantePais' => '',
            'ParticipanteFechaNacimiento' => '',
            'ParticipanteApellido2' => '',
            'ParticipanteRutAdherente' => '',
            'ParticipanteDvAdherente' => '',
            'nroadherente' => '',
            'ParticipanteProductId',
            'ParticipanteIdRegistroParticipante' => '',
            'ParticipanteFechaInscripcion' => '',
            'ParticipanteTipoDocumento' => '',
            'ParticipanteRUT' => '',
            'ParticipanteDV' => '',
            'ParticipantePasaporte' => '',
            'ParticipanteTelefonoMovil' => '',
            'ParticipanteTelefonoFijo' => '',
            'ParticipanteCargo' => '',
            'ParticipanteIdRol' => '',
            'ParticipanteCodigoComuna' => '',
            'ParticipanteDireccion' => '',
            'ResponsableApellido1' => '',
            'ResponsableNombre' => '',
            'ResponsableApellido2' => '',
            'ResponsableTipoDocumento' => '',
            'ResponsablePasaporte' => '',
            'ResponsableFechaNacimiento' => '',
            'ResponsableIdSexo' => '',
            'ResponsableEmail' => '',
            'ResponsableTelefonoMovil' => '',
            'ResponsableTelefonoFijo' => '',
            'ResponsableCargo' => '',
            'ResponsableCodigoComuna' => '',
            'ResponsableCodigoRegion' => '',
            'ResponsableDireccion' => '',
            'ResponsableDireccion' => '',
            'ResponsableRUT' => '',
            
        );
    }
    
    public static function parse_document_int($document){
        if ($document == 'Rut'){
            return 1;            
        } else if ($document == 'Pasaporte') {
            return 100;
        }
        return null;
    }
    
    public static function insert_update_inscripciones_elearning_back($response, $idcurso_moodle, $iduser_moodle){
        // @codingStandardsIgnoreLine
        /** @var \moodle_database $DB */
        global $DB;

        $record = new \stdClass();
        
        $record->participanteidregistroparticip = $response['ParticipanteIdRegistroParticipante'];
        $record->participanteproductid          = $response['ParticipanteProductId'];
        $record->participantefechainscripcion   = $response['ParticipanteFechaInscripcion'];
        $record->participantenombre             = $response['ParticipanteNombre'];
        $record->participanteapellido1          = $response['ParticipanteApellido1'];
        $record->participanteapellido2          = $response['ParticipanteApellido2'];
        $record->participantetipodocumento      = $response['ParticipanteTipoDocumento'];
        $record->participanterut                = $response['ParticipanteRUT'];
        $record->participantedv                 = $response['ParticipanteDV'];
        $record->participantepasaporte          = $response['ParticipantePasaporte'];
        $record->participantefechanacimiento    = $response['ParticipanteFechaNacimiento'];
        $record->participanteidsexo             = $response['ParticipanteIdSexo'];
        $record->ParticipanteEmail              = $response['ParticipanteEmail'];
        $record->participantetelefonomovil      = $response['ParticipanteTelefonoMovil'];
        $record->participantetelefonofijo       = $response['ParticipanteTelefonoFijo'];
        $record->ParticipantePais               = $response['ParticipantePais'];
        $record->participantecargo              = $response['ParticipanteCargo'];
        $record->participanteidrol              = $response['ParticipanteIdRol'];
        $record->participantecodigocomuna       = $response['ParticipanteCodigoComuna'];
        $record->participantedireccion          = $response['ParticipanteDireccion'];
        $record->participanterutadherente       = $response['ParticipanteRutAdherente'];
        $record->participantedvadherente        = $response['ParticipanteDvAdherente'];
        $record->responsablenombre              = $response['ResponsableNombre'];
        $record->responsableapellido1           = $response['ResponsableApellido1'];
        $record->responsableapellido2           = $response['ResponsableApellido2'];
        $record->responsabletipodocumento       = $response['ResponsableTipoDocumento'];
        $record->responsablerut                 = $response['ResponsableRUT'];
        $record->responsabledv                  = $response['ResponsableDV'];
        $record->responsablepasaporte           = $response['ResponsablePasaporte'];
        $record->responsablefechanacimiento     = $response['ResponsableFechaNacimiento'];
        $record->responsableidsexo              = $response['ResponsableIdSexo'];
        $record->responsableemail               = $response['ResponsableEmail'];
        $record->responsabletelefonomovil       = $response['ResponsableTelefonoMovil'];
        $record->responsabletelefonofijo        = $response['ResponsableTelefonoFijo'];
        $record->responsablecargo               = $response['ResponsableCargo'];
        $record->responsablecodigocomuna        = $response['ResponsableCodigoComuna'];
        $record->responsablecodigoregion        = $response['ResponsableCodigoRegion'];
        $record->responsabledireccion           = $response['ResponsableDireccion'];
        $record->id_curso_moodle                = $idcurso_moodle;
        $record->id_user_moodle                 = $iduser_moodle;
        $record->empresacontrato                 = $iduser_moodle;
        $record->id_user_moodle                 = $iduser_moodle;


        $get_inscripciones = $DB->get_record("inscripcion_elearning_back", array("participanteidregistroparticip" => $record->participanteidregistroparticip));

        $today = date('Y-m-d H:i:s');
        if (!empty($get_inscripciones)) {
            $record->id         = $get_inscripciones->id;   
            $record->updatedat  = $today;
            $DB->update_record('inscripcion_elearning_back', $record);
        }else{            
            $record->createdat  = $today;
            $DB->insert_record('inscripcion_elearning_back', $record);
        }
        
        $log = new \stdClass();
        $log->id_curso_moodle   = $idcurso_moodle;
        $log->id_user_moodle    = $iduser_moodle;
        //$log->participanteproductid = $response['ParticipanteProductId'];
        $log->participanteidregistroparticip = $response['ParticipanteIdRegistroParticipante'];
        $log->created_at        = $today;

        $DB->insert_record('inscripcion_elearning_log', $log);

        /** CAMPOS PERSONALIZADOS */
        $data = self::create_object_user_custon_field($record);
        $custom_field = \local_mutual\front\utils::insert_custom_fields_user($iduser_moodle, $data);
        if($custom_field["error"] != ""){            
            $event = \local_enrolcompany\event\enrolcompany_csv::create(
                array(
                    'context' => \context_system::instance(),
                    'other' => array(
                        'error' => 'Error cargando data de campos personalizados: '.$custom_field,
                    ),
                )
            );
            $event->trigger();
        }
    }
    
    public static function create_object_user_custon_field($obj_end){
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
        $data->empresarazonsocial = '';
        $data->empresacontrato = '';
        $empresarut = strtolower(trim($obj_end->participanterutadherente))."-".strtolower(trim($obj_end->participantedvadherente));
        $get_company_by_rut = $DB->get_records('company', array('rut' => $empresarut));
        if (!empty($get_company_by_rut)) {
            $company_by_rut = end($get_company_by_rut);
            $data->empresarazonsocial = $company_by_rut->razonsocial;
            $data->empresacontrato = $company_by_rut->contrato;
        }
        return $data;
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
    static public function register_participant_elearning($username, $externaluser, $course)
    {      
        global $DB, $CFG;
        //Verificar si la Empresa existe
        $empresarut = strtolower(trim($externaluser['ParticipanteRutAdherente']))."-".strtolower(trim($externaluser['ParticipanteDvAdherente']));
        
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
            'nroadherente' => $externaluser["nroadherente"],
            'empresarut' => $empresarut,            
        );
        
        $newuserid = self::create_user($createuser, $course);
        $enrol_data = \local_pubsub\metodos_comunes::enrol_user_elearning($course, $newuserid , 5);
        $get_company_by_rut = $DB->get_records('company', array('rut' => $empresarut));        
        $company_by_rut = end($get_company_by_rut);
        $company_obj = new \stdClass();
        $company_obj->name = trim((string) $externaluser["CompanyName"]);
        $company_obj->shortname = trim((string) $externaluser["CompanyName"]);
        $company_obj->city = "";
        $company_obj->country = $CFG->country;
        $company_obj->rut = $empresarut;
        $company_obj->contrato = trim((string) $externaluser["nroadherente"]);
        $company_obj->razonsocial = trim((string) $externaluser["CompanyName"]);

        if (!empty($company_by_rut)){
            //actualizo la empresa si existe
            $company_obj->id = $company_by_rut->id;
            $DB->update_record('company', $company_obj);
            $companyid = $company_by_rut->id;
        } else {
            //crear empresa
            $companyid = $DB->insert_record('company', $company_obj);
        }
        //asignar usuario a compañia
        //$company = new \company($companyid);
        //$company->assign_user_to_company($newuserid);
        \local_company\metodos_comunes::assign($companyid, $newuserid);

        $event = \local_enrolcompany\event\enrolcompany_csv::create(
            array(
                'context' => \context_system::instance(),
                'other' => array(
                    'userid' => $newuserid,
                    'company' => $companyid,
                    'enrolid' => $enrol_data['enrolId']->id,
                    'estatus' => $enrol_data['estatus'],
                    'guidenrol' => $externaluser['ParticipanteIdRegistroParticipante']
                ),
            )
        );
        $event->trigger();

        $data = [
            'newuserid'=> $newuserid,
            'enrolid' => $enrol_data['enrolId'],
            'estatus' => $enrol_data['estatus']
        ];

        return $data;
    }
    
    /**
     * @param $createuser
     * @param null $course
     * @return int
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function create_user($createuser, $course = null)
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        $create_user = new stdClass();
        $get_user = $DB->get_record("user", array("username" => $createuser["username"]));

        $create_user->username = $createuser["username"];
        $create_user->auth = 'manual';
        $create_user->firstname = $createuser["firstname"];
        $create_user->lastname = $createuser["lastmame"];
        $create_user->mnethostid = 1;
        $create_user->confirmed = 1;
        $create_user->email = $createuser["email"];
        $create_user->password = $createuser["username"];

        if (!empty($get_user)) {
            //si el usuario ya existe lo actualizo
            $create_user->id = $get_user->id;
            user_update_user($create_user);
            $newuserid = $get_user->id;
        } else {
            //si el usuario no existe lo creo
            $newuserid = user_create_user($create_user);
        }

        return $newuserid;
    }
    
}
