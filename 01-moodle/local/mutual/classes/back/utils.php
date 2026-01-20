<?php

namespace local_mutual\back;

use curl;
use dml_exception;
use SimpleXMLElement;

defined('MOODLE_INTERNAL') || die();

global $CFG;
include_once($CFG->libdir . '/filelib.php');

class utils
{

    public static function get_certificado_from_back($iddocumento)
    {
        $token = get_config('local_pubsub', 'tokenapi');
        $key = get_config('local_pubsub', 'subscriptionkey');
        $endpoint = get_config('local_pubsub', 'endpointcertificado');


        $request = new curl();
        $request->setHeader(array(
            'Authorization: ' . $token,
            'Ocp-Apim-Subscription-Key: ' . $key
        ));

        $response = $request->get($endpoint . $iddocumento);
        $responseobject = json_decode($response);

        if ($request->info['http_code'] !== 200) {
            if (!empty($responseobject->message)) {
                return [
                    'error' => true,
                    'data' => $responseobject->message
                ];
            } else {
                return [
                    'error' => true,
                    'data' => $response
                ];
            }
        }

        return [
            'error' => false,
            'data' => $responseobject
        ];
    }

    /**
     * @param $params
     * @throws dml_exception
     */
    public static function upsert_certificado_data($params)
    {
        global $DB;
        $certificado = $DB->get_record('certificados_back', array(
            'idinscripcion' => $params['idinscripcion'],
            'iddocumento' => $params['iddocumento']
        ));
        if (!empty($certificado)) {
            $params['id'] = $certificado->id;
            $params['timemodified'] = time();
            $DB->update_record('certificados_back', (object)$params);
        } else {
            $params['timecreated'] = time();
            $DB->insert_record('certificados_back', (object)$params);
        }

        try {
            $event = \local_pubsub\event\certificado_saved::create(array(
                'context' => \context_system::instance(),
                'other' => array(
                    'data' => $params
                )
            ));
            $event->trigger();
        } catch (\coding_exception $e) {
            error_log(serialize($e));
        }
    }

    /**
     * @param $userid
     * @param $courseid
     * @param null $sessionid
     * @return array|false[]
     * @throws dml_exception
     */
    public static function get_certificado_url($userid, $courseid, $sessionid = null): array
    {
        global $DB;
        if (empty($sessionid)) {
            $elearning_back = $DB->get_records('inscripcion_elearning_back', array(
                'id_user_moodle' => $userid,
                'id_curso_moodle' => $courseid
            ));
            if (!empty($elearning_back)) {
                $inscripcion = end($elearning_back);
                $certificado = $DB->get_records(
                    'certificados_back',
                    array('idinscripcion' => $inscripcion->participanteidregistroparticip, 'tipodocumento' => 1)
                );
                $certificado = end($certificado);
            }
        } else {
            $user = $DB->get_record('user', array('id' => $userid));
            $elearning_back = $DB->get_records('inscripciones_back', [
                "id_sesion_moodle" => $sessionid,
                "participanteidentificador" => $user->username
            ]);
            if (!empty($elearning_back)) {
                $inscripcion = end($elearning_back);
                $certificado = $DB->get_records(
                    'certificados_back',
                    array('idinscripcion' => $inscripcion->idinscripcion, 'tipodocumento' => 1)
                );
                $certificado = end($certificado);
            }
        }
        if (!empty($certificado)) {
            $request = self::get_certificado_from_back($certificado->iddocumento);
            if ($request['error']) {
                return array('exist' => false);
            } else {
                return array('exist' => true, 'data' => $request['data']);
            }
        }

        return array('exist' => false);
    }


    /**
     * @param $rut
     * @param $tipo
     * @return SimpleXMLElement|string
     * @throws dml_exception
     */
    public static function get_personas_nominativo($rut, $tipo)
    {
        global $CFG;
        $soap_request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cl="' . get_config('local_mutual', 'xml_request') . '">
                            <soapenv:Header/>
                            <soapenv:Body>
                                <cl:obtenerInformacionPersona>
                                    <solicitud>
                                        <identificador>' . $rut . '</identificador>
                                        <tipo> ' . $tipo . '</tipo>
                                    </solicitud>
                                </cl:obtenerInformacionPersona>
                            </soapenv:Body>
                        </soapenv:Envelope>';

        $headers = array(
            "Content-type: text/xml",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "Content-length: " . strlen($soap_request),
        );
        $url = get_config('local_mutual', 'buscar_persona_service');
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
        $clean_xml = str_ireplace(['S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/"', 'S:Envelope', ' xmlnsns0="http//cl.mutual.ws/"', 's:Body', '<>', '</>', 'ns0:', ':'], '', $output);
        $clean_xml = simplexml_load_string($clean_xml);
        return $clean_xml;
    }

    public static function get_fullname_apellido_materno($userid){
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        $user  = $DB->get_record('user',array('id' => $userid));
        $apellidomaterno = '';
        if (empty($user)){
            $fullname = 'Id usuario no encontrado';
        }else{
           $info_data = profile_user_record($userid);
            $fullname = ltrim(rtrim($user->firstname)). " ".ltrim(rtrim($user->lastname));
            if (!empty($info_data->apellidom)){
                $apellidomaterno = $info_data->apellidom;
            }else{
                $userapellido  = $DB->get_record('eabcattendance_extrafields',array('userid' => $userid));
                if (!empty($userapellido->apellidomaterno)){
                    $apellidomaterno = $userapellido->apellidomaterno;
                    $createuser["apellidomaterno"] = $userapellido->apellidomaterno;
                    \local_pubsub\metodos_comunes::saveApellidoMaterno($createuser, $userid);
                }else{
                    $createuser["apellidomaterno"] = "";
                    \local_pubsub\metodos_comunes::saveApellidoMaterno($createuser, $userid);
                    $data = profile_user_record($userid);
                    $apellidomaterno = $data->apellidom;
                }
            }
            $fullname = $fullname.' '.ltrim(rtrim($apellidomaterno));
        }

        return $fullname;
    }
}
