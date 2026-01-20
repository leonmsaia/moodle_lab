<?php

function encrypt($data, $key) {
    $encData = openssl_encrypt($data, 'DES-EDE3', $key, OPENSSL_RAW_DATA);
    $encbase64 = base64_encode($encData);
    return $encbase64;
}

function decrypt($data, $key) {
    $data = base64_decode($data);
    $decData = openssl_decrypt($data, 'DES-EDE3', $key, OPENSSL_RAW_DATA);
    return $decData;
}

/**
 * Obtener la fecha de matriculacion
 * @param $courseid
 * @param $userid
 * @return mixed
 * @throws dml_exception
 */
function get_enrol_date($courseid, $userid) {
    global $DB;
    $sql = /** @lang text */
            '
                SELECT
                   ue.timecreated as dateroltime, ue.id
               FROM
                   {user_enrolments} ue,
                   {enrol} e
               WHERE
                   ue.enrolid = e.id AND
                   ue.status = 0 AND
                   e.status = 0 AND
                   e.courseid = ? AND
                   ue.userid = ? 
               GROUP BY
                   ue.userid
            ';
    $result = $DB->get_record_sql($sql, array($courseid, $userid));
    return $result;
}


/**
 * @param $user
 * @param $course
 * @param $resultado
 * @param $motivo_resultado
 * @param string $courseCompletionDate
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 */
function get_xml_body($user, $course, $resultado, $motivo_resultado, $courseCompletionDate = "") {
    global $CFG;
    require_once($CFG->dirroot . "/user/profile/lib.php");
    $custom_fields_user = profile_user_record($user->id);

    $time_enrol = get_enrol_date($course->id, $user->id);

    $dateEnrol = parseTimestampToDate($time_enrol->dateroltime);
    $courseCompletionDate = ($courseCompletionDate) ? parseTimestampToDate($courseCompletionDate) : "";
    $calculate_average_seven = calculate_average($course->id, $user->id, "7");
    $calculate_average_hundred = calculate_average($course->id, $user->id, "100");
    if(!empty($custom_fields_user->participantetipodocumento)) {
        $tipodocumento = $custom_fields_user->participantetipodocumento;
    } else {
        $tipodocumento = ''; 
    }

    if(!empty($custom_fields_user->participantetipodocumento)) {
        $documento = $custom_fields_user->participantedocumento; 
    } else {
        $documento = '';
    }

    $data_array = array(
        "enrol_id" => utf8_encode($time_enrol->id),
        "dateroltime" => utf8_encode($dateEnrol),
        "courseCompletionDate" => utf8_encode($courseCompletionDate),
        "participantetipodocumento" => utf8_encode($tipodocumento),
        "participantedocumento" => utf8_encode($documento),
        "firstname" => utf8_encode($user->firstname),
        "lastname" => utf8_encode($user->lastname),
        "lastnamephonetic" => utf8_encode($user->lastnamephonetic),
        "email" => utf8_encode($user->email),
        "resultado" => utf8_encode($resultado),
        "motivo_resultado" => utf8_encode($motivo_resultado),
        "calculate_average_seven" => utf8_encode($calculate_average_seven),
        "calculate_average_hundred" => utf8_encode(intval($calculate_average_hundred))
    );
    $body_xml = get_string('body_xml_to_encrypt', 'local_cron', $data_array);

    return (utf8_decode($body_xml));
}

/**
 * @param $timestamp
 * @return false|string
 */
function parseTimestampToDate($timestamp) {
    $date = date("d/m/Y H:i:s", $timestamp);
    return $date;
}

/**
 * @param $courseid
 * @param $userid
 * @param $average
 * @return float|int
 * @throws dml_exception
 */
function calculate_average($courseid, $userid, $average)
{
	$grade_user = getcoursegrade($courseid, $userid);
	$total = (($grade_user * $average) / 100);
	return $total;
}

/**
 * @param $courseid
 * @param $userid
 * @return int
 * @throws dml_exception
 */
function getcoursegrade($courseid, $userid)
{
	global $DB;
	$sql =
		/** @lang text */
		"SELECT
        ROUND(gg.finalgrade,5) AS grade
        FROM {course} AS c
        JOIN {context} AS ctx ON c.id = ctx.instanceid
        JOIN {role_assignments} AS ra ON ra.contextid = ctx.id
        JOIN {user} AS u ON u.id = ra.userid
        JOIN {grade_grades} AS gg ON gg.userid = u.id
        JOIN {grade_items} AS gi ON gi.id = gg.itemid
        JOIN {course_categories} AS cc ON cc.id = c.category
        WHERE  gi.courseid = c.id AND gi.itemtype = 'course' and c.id = ? and u.id = ?";

	$rs = $DB->get_record_sql($sql, array($courseid, $userid));
	if (!empty($rs)) {
		$grade = $rs->grade;
		return $grade;
	}

	return 0;
}

/**
 * @param $data
 * @return string
 */
function encrypt_base64($data)
{
	$data = base64_encode($data);
	return $data;
}


/**
 * @param $encrypt_xml_body
 * @return mixed|SimpleXMLElement
 */
function get_personas($rut,$tipo)
{
	global $CFG;
	$soap_request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:req="http://osb.mutual.cl/MUTUAL/OSB/schema/Elearning/Capacitacion/Finalizar/Req-v2017.09">
   <soapenv:Header/>
   <soapenv:Body>
      <cl:obtenerInformacionPersona>
         <solicitud>
            <identificador>' . $rut . '</identificador>
            <tipo> ' .$tipo. '</tipo>
         </solicitud>
      </cl:obtenerInformacionPersona>
   </soapenv:Body>
</soapenv:Envelope>';

	$headers = array(
		"Content-type: text/xml",
		"Accept: text/xml",
		"Cache-Control: no-cache",
		"Pragma: no-cache",
		//"SOAPAction: http://172.30.10.189:8110/MaestroPersonas-web/BuscarPersonaService?WSDL ",
		"Content-length: " . strlen($soap_request),
	);
	$url = 'http://172.30.10.189:8110/MaestroPersonas-web/BuscarPersonaService?WSDL';
	$ch = curl_init($url);

	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $soap_request);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);

	$output = curl_exec($ch);

	curl_close($ch);
	$clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:', 'sevenConsPer:', 'men:', ':'], '', $output);
	$clean_xml = simplexml_load_string($clean_xml);
	return $clean_xml;
}

/**
 * @param $encrypt_xml_body
 * @return mixed|SimpleXMLElement
 */
function get_soap_request($encrypt_xml_body)
{
	global $CFG;
	$soap_request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:req="http://osb.mutual.cl/MUTUAL/OSB/schema/Elearning/Capacitacion/Finalizar/Req-v2017.09">
   <soapenv:Header/>
   <soapenv:Body>
      <req:ElearningCapacitacionFinalizarExpReq>
         <finCapacitacion>

            <token>' . get_time_encrypt() . '</token>
            <usuario>eAbcLearning</usuario>
            <xml>' . $encrypt_xml_body . '</xml>
         </finCapacitacion>
      </req:ElearningCapacitacionFinalizarExpReq>
   </soapenv:Body>
</soapenv:Envelope>';

	$headers = array(
		"Content-type: text/xml",
		"Accept: text/xml",
		"Cache-Control: no-cache",
		"Pragma: no-cache",
		//"SOAPAction: http://url/location/of/soap/method",
		"Content-length: " . strlen($soap_request),
	);
	$url = $CFG->local_cron_endpoint;
	$ch = curl_init($url);

	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $soap_request);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);

	$output = curl_exec($ch);

	curl_close($ch);
	$clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:', 'sevenConsPer:', 'men:', ':'], '', $output);
	$clean_xml = simplexml_load_string($clean_xml);
	return $clean_xml;
}

/**
 * @param $status
 * @param $courseid
 * @param $userid
 * @param $today
 * @param int $usergradeCourse
 * @param int $gradepass
 * @throws dml_exception
 */
function save_log($status, $courseid, $userid, $today, $usergradeCourse = 0, $gradepass = 0)
{
	global $DB;
	$save_log = new stdClass();
	$save_log->status = $status;
	$save_log->userid = $userid;
	$save_log->courseid = $courseid;
	$save_log->timemodified = $today;
	$save_log->gradeuser = floatval($usergradeCourse);
	$save_log->gradeapprovecourse = floatval($gradepass);

	$ws_log = $DB->get_record('mutual_log_local_cron', array('userid' => $userid, 'courseid' => $courseid));
	// validamos si ya tiene registro en el log de envio ws en la tabla mutual_log_local_cron
	//    echo print_r($ws_log, true);
	if (!$ws_log) {
		try {
			$DB->insert_record('mutual_log_local_cron', $save_log);
		} catch (dml_exception $e) {
			throw new moodle_exception($e->debuginfo);
			exit();
		}
	}
}

/**
 * Get xml request an clear
 *
 * @xml xml request.
 * @clean_xml clear xml width function str_ireplace an get internal node xml width function simplexml_load_string.
 * @return $clean_xml internal node xml.
 */
function getxml($xml)
{
	$clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:', 'sevenConsPer:', 'men:', ':'], '', $xml);
	$clean_xml = simplexml_load_string($clean_xml)->soapenvBody->setsolicitudInscripcion->xml;
	return $clean_xml;
}

/**
 * Decrypt xml request
 *
 * @clean_xml_decrypt clear xml width function explode.
 * @return $xmlParse xml decryp parse request.
 */
function format_xml_decrypt($decrypt)
{
	$clean_xml_decrypt = explode('<?xml version="1.0" encoding="UTF-8"?>', $decrypt)[1];
	$xmlParse = simplexml_load_string($clean_xml_decrypt);
	return $xmlParse;
}

/**
 * Creates a user
 *
 * @throws moodle_exception
 * @param stdClass $user info user and course to create
 * @param bool $updatepassword if true, authentication plugin will update password.
 * @param bool $triggerevent set false if user_created event should not be triggred.
 *             This will not affect user_password_updated event triggering.
 * @return int id enrolment user
 */

/**
 * Creates a user
 *
 * @throws moodle_exception
 * @param stdClass $user info user and course to create
 * @param bool $updatepassword if true, authentication plugin will update password.
 * @param bool $triggerevent set false if user_created event should not be triggred.
 *             This will not affect user_password_updated event triggering.
 * @return int id enrolment user
 */
function user_create_enrol_user_xml($user, $updatepassword = true, $triggerevent = true)
{
	global $DB, $CFG;
	require_once($CFG->dirroot . '/user/lib.php');
	require_once($CFG->dirroot . "/user/profile/lib.php");

	//validate company if not exist
	$companyrut = (string) $user->datos_empresa->rut;
	$get_company_by_rut = $DB->get_record('company', array('rut' => $companyrut));
	if (!$get_company_by_rut) {
		$companyid = create_company($user);
	} else {
		$companyid = $get_company_by_rut->id;
	}

	// Validate new user or update?
	$userid = $DB->get_record('user', array('idnumber' => (string) ($user->datos_participante->identificadorDocumento)));

	if (empty($userid)) {
		//create user
		$newuserid = user_create_user(createObjectuser($user), true);

		//asignar usuario a compañia
		//$company = new \company($companyid);
		//$assign_user_to_company = $company->assign_user_to_company($newuserid);
		\local_company\metodos_comunes::assign($companyid, $newuserid);

		//insert custom fields
		insert_custom_fuelds_user($newuserid, $user);
		//enrol user
		$enrolId = enrol_user_xml($user, $newuserid);
		return $enrolId;
	} else {

		//            validar si el usuario cambio de compañia extraemos todos los datos adicionales del participante
		$customprofile = profile_user_record($userid->id);

		//comparamos el campo del rut de la empresa actual con el que viene del ws
		//si es diferente sacamos al participante de la empresa y lo metemos 
		//agregamos en la nueva si no se cumple lo demjamos como esta
		if ($customprofile->empresarut != $companyrut) {
			//sacar usuario de la empresa anterior
			$get_company_before_rut = $DB->get_record('company', array('rut' => $customprofile->empresarut));
			//$companyunassing = new \company($get_company_before_rut->id);
			//$assign_user_to_company = $companyunassing->unassign_user_from_company($userid->id);
			\local_company\metodos_comunes::unassign($userid->id, $get_company_before_rut->id);
			//asignar usuario a la empresa
			//$companyassing = new \company($companyid);
			//$assign_user_to_company = $companyassing->assign_user_to_company($userid->id);
			\local_company\metodos_comunes::assign($companyid, $userid->id);
		 	
		}

		//update user
		$newuserid = user_update_user(updateObjectuser($user, $userid->id));
		//insert custom fields
		insert_custom_fuelds_user($userid->id, $user);
		//enrol user
		$enrolId = enrol_user_xml($user, $userid->id);
		return $enrolId;
	}
}

function insert_custom_fuelds_user($newuserid, $user)
{

	$array_aditional_files = array(
		//datos adicionales
		"participantecargo" => (string) $user->datos_participante->cargo,
		"participantefechanacimiento" => (string) $user->datos_participante->fechaNacimiento,
		"participantetipodocumento" => (string) $user->datos_participante->tipoDocumento,
		"participantedocumento" => (string) $user->datos_participante->identificadorDocumento,
		"participantesexo" => (string) $user->datos_participante->sexo,
		//datos contacto
		"contactoapellidomaterno" => (string) $user->datos_contacto->apellidoMaterno,
		"contactoapellidopaterno" => (string) $user->datos_contacto->apellidoPaterno,
		"contactocargo" => (string) $user->datos_contacto->cargo,
		"contactocelular" => (string) $user->datos_contacto->celular_numero,
		"contactofechanac" => (string) $user->datos_contacto->fechaNacimiento,
		"contactoemail" => (string) $user->datos_contacto->email,
		"contactotipodoc" => (string) $user->datos_contacto->tipoDocumento,
		"cintactoiddoc" => (string) $user->datos_contacto->identificadorDocumento,
		"contactonombres" => (string) $user->datos_contacto->nombres,
		"contactotelefono" => (string) $user->datos_contacto->telefono,
		"contactoidcomuna" => (string) $user->datos_contacto->comuna->id,
		"contactonombrecomuna" => (string) $user->datos_contacto->comuna->nombre,
		"contactodireccion" => (string) $user->datos_contacto->direccion,
		"contactoidregion" => (string) $user->datos_contacto->region->id,
		"contactonombreregion" => (string) $user->datos_contacto->region->nombre,
		//            //datos empresa
		"empresarut" => (string) $user->datos_empresa->rut,
		"empresarazonsocial" => (string) $user->datos_empresa->razon_social,
		"empresacontrato" => (string) $user->datos_empresa->contrato
	);
	profile_save_custom_fields($newuserid, $array_aditional_files);
}

function createObjectuser($user)
{
	global $CFG;
	$user_ws = new \stdClass();
	$user_ws->lastnamephonetic = (string) ($user->datos_participante->apellidoMaterno);
	$user_ws->lastname = (string) ($user->datos_participante->apellidoPaterno);
	$user_ws->phone2 = (string) ($user->datos_participante->celular_prefijo) . " " . (string) ($user->datos_participante->celular_numero);
	$user_ws->username = (string) strtolower($user->datos_participante->identificadorDocumento);
	$user_ws->password = (string) ($user->datos_participante->identificadorDocumento);
	$user_ws->email = (string) ($user->datos_participante->email);
	$user_ws->firstname = (string) ($user->datos_participante->nombres);
	$user_ws->phone1 = (string) ($user->datos_participante->celular_prefijo) . " " . (string) ($user->datos_participante->celular_numero);
	$user_ws->idnumber = (string) ($user->datos_participante->identificadorDocumento);
	$user_ws->confirmed = true;
	$user_ws->mnethostid = $CFG->mnet_localhost_id;
	$user_ws->timecreated = time();
	return $user_ws;
}

function updateObjectuser($user, $userid)
{
	global $CFG;
	$user_update_ws = new \stdClass();
	$user_update_ws->id = $userid;
	$user_update_ws->lastnamephonetic = (string) ($user->datos_participante->apellidoMaterno);
	$user_update_ws->lastname = (string) ($user->datos_participante->apellidoPaterno);
	$user_update_ws->phone2 = (string) ($user->datos_participante->celular_prefijo) . " " . (string) ($user->datos_participante->celular_numero);
	$user_update_ws->username = (string) strtolower($user->datos_participante->identificadorDocumento);
	$user_update_ws->password = (string) ($user->datos_participante->identificadorDocumento);
	$user_update_ws->email = (string) ($user->datos_participante->email);
	$user_update_ws->firstname = (string) ($user->datos_participante->nombres);
	$user_update_ws->phone1 = (string) (($user->datos_participante->celular_prefijo) . " " . ($user->datos_participante->celular_numero));
	$user_update_ws->idnumber = (string) ($user->datos_participante->identificadorDocumento);
	return $user_update_ws;
}

function enrol_user_xml($user, $newuserid)
{
	global $DB, $CFG, $PAGE;
	require_once($CFG->dirroot . '/user/lib.php');
	require_once($CFG->dirroot . '/lib/enrollib.php');
	require_once($CFG->dirroot . '/lib/grade/grade_item.php');
	require_once($CFG->dirroot . '/lib/grade/grade_grade.php');

	$config = get_config('local_cron');
	$days = (int) $config->days_enrol;
	if (empty($days)) {
		$days = 30;
	}

	$courseid = $DB->get_record('course', array('idnumber' => utf8_decode($user->datos_curso->id_curso)));

	$enrolinstances = enrol_get_instances($courseid->id, true);
	foreach ($enrolinstances as $courseenrolinstance) {
		if ($courseenrolinstance->enrol == "manual") {
			$instance = $courseenrolinstance;
			break;
		}
	}

	$enrol = enrol_get_plugin('manual');

	//validar si el usuario ya esta matriculado
	$enrolId = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $newuserid));
	if (!$enrolId) {
		//si no esta matriculado creo la matriculacion
		$newuseridenrol = $enrol->enrol_user($instance, $newuserid, 5);
		$enrolId = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $newuserid));
		return get_string('response', 'local_cron', ['enrolid' => $enrolId->id]);
	} else {
		//validar si ya pasaron los 30 dias y no tiene aprobado el curso
		$date_from_enrolment_course = get_enrol_date($courseid->id, $newuserid);
		$days_passed = \local_cron\utils::interval($date_from_enrolment_course->dateroltime, today());

		//si existe timecompleted es decir ya finalizo el curso o pasaron los 3 dias desde su matriculacion lo matriculo y vuelvo a matricular
		$finalizate_course = $DB->get_record('course_completions', array('course' => $courseid->id, 'userid' => $newuserid));

		if (($finalizate_course->timecompleted) || ($days_passed > $days)) {
			//desmatricular si ya finalizo el curso (tiene timecompleted)
			$enrol->unenrol_user($instance, $newuserid);
			//limpiar intentos despues de desmatricular
			\local_cron\utils::clear_user_course_data($newuserid, $courseid->id);
			//rematricular
			$newuseridenrol = $enrol->enrol_user($instance, $newuserid, 5);
			$enrolId = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $newuserid));
			return get_string('response', 'local_cron', ['enrolid' => $enrolId->id]);
		} else {
			return get_string('user_already_enrolment', 'local_cron');
		}
	}
}

function validate_request($request)
{
	global $DB;
	$validate_response = "";

	$id_number_course = $DB->get_record('course', array('idnumber' => utf8_decode($request->datos_curso->id_curso)));

	//id number invalido
	if (empty($id_number_course)) {
		$message = "Error id_curso invalido";
		$validate_response = get_string('error_message', 'local_cron', ['message' => $message]);
	} else if (empty(utf8_decode($request->datos_participante->identificadorDocumento))) {
		$message = "No se ingreso identificadorDocumento";
		$validate_response = get_string('error_message', 'local_cron', ['message' => $message]);
	} else if (empty(utf8_decode($request->datos_participante->email))) {
		$message = "No se ingreso email ";
		$validate_response = get_string('error_message', 'local_cron', ['message' => $message]);
	} else if ($request->datos_participante->participantetipodocumento == "Rut" || $request->datos_participante->participantetipodocumento == "rut") {
		if (!preg_match("/^[0-9]{7,8}+-[0-9kK]{1}/", $request->datos_participante->identificadorDocumento)) {
			$message = "El número del documento del participante debe tener un formato rut correcto";
			$validate_response = get_string('error_message', 'local_cron', ['message' => $message]);
		} else if (!preg_match("/^[0-9]{7,8}+-[0-9kK]{1}/", $request->datos_empresa->rut)) {
			$message = "El rut de la empresa debe tener un formato rut correcto";
			$validate_response = get_string('error_message', 'local_cron', ['message' => $message]);
		}
	} else if (!preg_match("/^[a-zA-Z0-9-]{4}/", $request->datos_participante->identificadorDocumento)) {
		$message = "El documento debe tener mínimo de 4 dígitos";
		$validate_response = get_string('error_message', 'local_cron', ['message' => $message]);
	}

	return $validate_response;
}

/**
 * Obtener la fecha de actual en timestamp
 * return null
 */
function today()
{
	$current_date = date_create();
	$today = date_timestamp_get($current_date);
	return $today;
}

/**
 * Obtener la nota de aprobación del curso
 * @param type $course curso a consultar nota
 * return float
 */
function get_grade_pass_course($course)
{
	global $DB;
	$grade = $DB->get_record("grade_items", array("courseid" => $course->id, "itemtype" => "course"));
	if ($grade->gradepass) {
		return $grade;
	} else {
		return 0;
	}
}

function getInfoEnrolment($userid, $courseid)
{
	global $DB;
	$enrolId = $DB->get_record('user_enrolments', array('enrolid' => $userid, 'userid' => $courseid));
	return $enrolId;
}

function get_time_encrypt()
{
	global $CFG;
	$data = date("d/m/Y H:i:s", time());
	$encData = openssl_encrypt($data, 'DES-EDE3', $CFG->local_cron_key_decrypt, OPENSSL_RAW_DATA);
	$encbase64 = base64_encode($encData);
	return $encbase64;
}

function event_save_log($context, $dataerror = null)
{
	global $CFG;

	$data["courseid"] = "2";
	require_once($CFG->dirroot . "/local/cron/classes/event/cron_log.php");
	$event = \local_cron\event\cron_log::create(
		array(
			'context' => $context,
			'other' => $dataerror
		)
	);
	$event->trigger();
	return $event;
}

function get_attemp($courseid, $userid)
{
	global $DB;
	$sql = 'SELECT mdl_quiz_attempts.id as attemp
       FROM mdl_quiz, mdl_quiz_attempts, mdl_course, mdl_user
       WHERE mdl_course.id= ?
       AND mdl_user.id = ?
       AND mdl_quiz_attempts.quiz = mdl_quiz.id
       AND mdl_course.id = mdl_quiz.course
       AND mdl_quiz_attempts.userid = mdl_user.id';

	$result = $DB->get_records_sql($sql, array($courseid, $userid));
	return $result[key($result)]->attemp;
}

function get_user_grade($mod, $courseid, $cm, $userid)
{
	global $CFG;
	require_once($CFG->libdir . '/gradelib.php');
	$grades = \grade_get_grades($courseid, $mod, $cm->modname, $cm->instance, $userid);
	if (!empty($grades->items)) {
		$grade = $grades->items[0];
		return $grade;
	}
	return null;
}

function get_state_completion_all_mod_user($coruseid, $userid)
{
	global $DB;
	$modinfo = \get_fast_modinfo($coruseid);
	$cms = $modinfo->get_cms();
	$completionAllMod = true;
	foreach ($cms as $cm) {
		if ($cm->completion != 0 && $cm->visible == 1 && ($cm->module != 16)) {
			$record = $DB->get_records("course_modules_completion", array("coursemoduleid" => $cm->id, "userid" => $userid));
			if (!$record[key($record)]->id) {
				$completionAllMod = false;
			}
		}
	}
	return $completionAllMod;
}

function get_quiz_grade_info($courseid, $userid)
{
	global $DB;
	$completionquizes = true;
	$quizes = $DB->get_records('quiz', array('course' => $courseid));
	if (!$quizes) {
		return true;
	}
	foreach ($quizes as $quiz) {
		$record = $DB->get_record("quiz_grades", array("quiz" => $quiz->id, "userid" => $userid));
		$info_module = get_info_module($quiz->id, $courseid);
		$gradeUser = ($record->grade) ? $record->grade : 0;
		$userattemp = get_attemp_quiz_user($userid, $quiz->id);
		$sql_course_modules = $DB->get_record("course_modules", array("instance" => $quiz->id, "course" => $courseid));

		if (
			(
				($quiz->attempts == $userattemp->attemp) &&
				($gradeUser < $info_module->gradepass)) || ($gradeUser >= $info_module->gradepass)
			|| ($sql_course_modules->visible == 0)
		) {
			$completionquizes = true;
		} else {
			$completionquizes = false;
		}
	}
	return $completionquizes;
}

function get_attemp_quiz_user($userid, $quiz)
{
	global $DB;
	$sql = "SELECT count(qa.id) AS attemp FROM {quiz_attempts} AS qa WHERE qa.userid=" . $userid . " AND qa.quiz=" . $quiz;
	return $DB->get_record_sql($sql);
}

function get_info_module($instanceid, $courseid)
{
	global $DB;
	$sql = "SELECT * FROM {grade_items} AS gi WHERE gi.iteminstance=" . $instanceid .  " AND gi.courseid=" . $courseid;
	return $DB->get_record_sql($sql);
}

function calculate_last_date_mod($courseid, $userid)
{
	global $DB;
	$modinfo = get_fast_modinfo($courseid);
	$cms = $modinfo->get_cms();
	$calculate_last_date_mod = "";
	foreach ($cms as $cm) {
		if ($cm->completion != 0 && $cm->visible == 1 && ($cm->module != 16)) {
			$records = $DB->get_records("course_modules_completion", array("coursemoduleid" => $cm->id, "userid" => $userid));
			foreach ($records as $record) {
				$calculate_last_date_mod = $record->timemodified;
			}
		}
	}
	return $calculate_last_date_mod;
}

function remove_his_user($username, $courseid)
{
	global $DB;

	if ($username && $courseid) {
		$valdiate_user = $DB->get_record("user", array("username" => $username));
		$mutual_log_local_cron = $DB->get_record("mutual_log_local_cron", array("userid" => $valdiate_user->id, "courseid" => $courseid));
		if (!$valdiate_user) {
			notice("usuario no existe");
		} else if (!$mutual_log_local_cron) {
			notice("no hay registro de el usuario en ese curso");
		} else {
			$DB->delete_records("mutual_log_local_cron", array("userid" => $valdiate_user->id, "courseid" => $courseid));
		}
	}
}


/**
 * Obtiene el intento de un quiz
 * @param type $courseid
 * @param type $userid
 * @param \local_mutualnotifications\type $DB
 * @return type
 */
function get_attemp_quiz($courseid, $userid)
{
	global $DB;
	$sql = 'SELECT mdl_quiz_attempts.id as attemp
            FROM mdl_quiz, mdl_quiz_attempts, mdl_course, mdl_user
            WHERE mdl_course.id= ?
            AND mdl_user.id = ?
            AND mdl_quiz_attempts.quiz = mdl_quiz.id
            AND mdl_course.id = mdl_quiz.course
            AND mdl_quiz_attempts.userid = mdl_user.id';

	$result = $DB->get_records_sql($sql, array($courseid, $userid));
	return $result[key($result)]->attemp;
}


function create_company($datacompany)
{
	global $DB, $CFG;
	$data = new stdClass();
	$data->name = trim((string) $datacompany->datos_empresa->razon_social);
	$data->shortname = trim((string) $datacompany->datos_empresa->razon_social);
	$data->city = "";
	$data->country = $CFG->country;
	$data->rut = trim((string) $datacompany->datos_empresa->rut);
	$data->contrato = trim((string) $datacompany->datos_empresa->contrato);
	$data->razonsocial = trim((string) $datacompany->datos_empresa->razon_social);

	$companyid = $DB->insert_record('company', $data);

	//$eventother = array('companyid' => $companyid);

	//$event = \block_comp_company_admin\event\company_created::create(array(
	//	'context' => \context_system::instance(),
	//	'other' => $eventother
	//));
	//$event->trigger();
	return $companyid;
}

/**
 * Delete all the attempts belonging to a user in a particular quiz.
 *
 * @param object $quizid int
 * @param object $userid int
 */
function quiz_delete_user_attempts_user($quizid, $userid)
{
	global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    question_engine::delete_questions_usage_by_activities(new qubaids_for_quiz_user($quizid, $userid));
    $params = [
        'quiz' => $quizid,
        'userid' => $userid,
    ];
    $DB->delete_records('quiz_attempts', $params);
    $DB->delete_records('quiz_grades', $params);
}

/**
 * Returns the full list of attempts a user has made.
 *
 * @param int $scormid the id of the scorm.
 * @param int $userid the id of the user.
 *
 * @return array array of attemptids
 */
function scorm_get_all_attempts_user($scormid, $userid) {
    global $DB;
    $attemptids = array();
    $sql = "SELECT DISTINCT attempt FROM {scorm_scoes_track} WHERE userid = ? AND scormid = ? ORDER BY attempt";
    $attempts = $DB->get_records_sql($sql, array($userid, $scormid));
    foreach ($attempts as $attempt) {
        $attemptids[] = $attempt->attempt;
    }
    return $attemptids;
}

function clear_attemps_course_user($userid, $courseid) {
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
            quiz_delete_user_attempts_user($cm->instance, $userid);
        }
    }

    //limpiar registros de local cron
    $DB->delete_records("mutual_log_local_cron", array('userid' => $userid, 'courseid' => $courseid));
}
