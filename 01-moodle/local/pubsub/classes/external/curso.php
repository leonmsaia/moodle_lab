<?php

namespace local_pubsub\external;

use dml_exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use moodle_exception;

require_once($CFG->libdir . '/externallib.php');

defined('MOODLE_INTERNAL') || die;

class curso extends external_api
{
	/**
	 * @return external_function_parameters
	 */
	public static function upsert_course_parameters()
	{
		return new external_function_parameters(
			array(
				'ID' => new external_value(PARAM_RAW, 'guid del curso'),
				'codigosuseso' => new external_value(PARAM_BOOL, 'buscar por codigo suseso', false)
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
	public static function upsert_course($id, $codigosuseso)
	{
		// @codingStandardsIgnoreLine
		/** @var \moodle_database $DB */
		global $DB;
		/** @var external_api $api */
        $api = new class extends external_api {};
        $params = $api::validate_parameters(
			self::upsert_course_parameters(),
			array(
				'ID' => $id,
				'codigosuseso' => $codigosuseso
			)
		);
                
		$endpoint = get_config('local_pubsub', 'endpointcursos') . $params["ID"];
		$response = \local_pubsub\metodos_comunes::request($endpoint);
		$dataresponse = array('response' =>  json_encode($response));
		\local_pubsub\metodos_comunes::save_event_response_endpointcrearcursos(\context_system::instance(), $dataresponse);
		if ($response["status"] > 299) {
			throw new moodle_exception("error request:" . $response["status"] . "endpint: " . $endpoint);
		}
		$response = json_decode($response["data"], true);
		$modalidad = $response['TipoModalidad'];
		$modalidad_distancia = $response['ModalidadDistancia'];
		
		//veo que tipo de modalidad trae y si esta activa en configuracion
		$modalidad_active = \local_pubsub\metodos_comunes::verify_coursetype_active($modalidad, $modalidad_distancia);
		//si el tipo de curso esta activo proceso la solicitud
		if ($modalidad_active == true) {
            $nombre_curso = $response["ProductoCurso"];
            if (empty($nombre_curso)) {
                $nombre_curso = $params["ID"];
            }
            $nombre_corto = $response["CodigoCurso"];
            if (empty($nombre_corto)) {
                throw new moodle_exception("debe indicar un CodigoCurso, el cual será nombre corto del curso");
            }

            $get_course = $DB->get_record('course', ['shortname' => $nombre_corto]);

            $summary = [
                "Descripcion: " . $response['Descripcion'],
                "Horas de cursado: " . $response['Horas'],
                "Objetivo: " . $response['Objetivo']
            ];

            if (empty($get_course)) {
                //si el curso no existe lo creo en base a la copia de seguridad
                
                $id_categoria = get_config('local_pubsub', 'coursecategory');

                //SE verifica si existe en curso_back 
                $courseback = $DB->get_record_sql('SELECT * FROM {curso_back} WHERE productoid = "' . $response["ProductoId"] . '" ORDER BY id DESC LIMIT 1');
                if ($courseback) {
                    $courserecord = $DB->get_record('course', ['id' => $courseback->id_curso_moodle]);
                } else {
                    $courserecord = '';
                }

                if (!empty($courserecord)) {
                    $data = new \stdClass();
                    $data->id = $courserecord->id;
                    $data->shortname = $nombre_corto;
                    $data->fullname = $nombre_curso;
                    $data->summary = implode("<br/>", $summary);

                    update_course($data);
                    $courseid = $courserecord->id;
                    $dataresponse = array('courseid' => $courseid);

                    \local_pubsub\metodos_comunes::save_event_update_course(\context_system::instance(), $dataresponse);
                } else {
                    // solo se usa para migrar los cursos viejos a la nueva tabla curso back
                    if (!empty($params['codigosuseso']) && !empty($response['CodigoSUSESO'])) {
                        throw new \moodle_exception('No existe curso con idnumber' . $response['CodigoSUSESO']);
                    }
                    $curso_id = \local_pubsub\metodos_comunes::crear_curso($nombre_curso, $nombre_corto, $id_categoria, $summary, $modalidad, $modalidad_distancia);

                    $courseid = $curso_id;
                    $dataresponse = array('courseid' => $courseid);
                    \local_pubsub\metodos_comunes::save_event_create_course(\context_system::instance(), $dataresponse);
                }
            } else {
                //si el curso ya existe pero no esta registrado en cuso_back
                $courseid = $get_course->id;
                
                $datos = new \stdClass();
                $datos->id = $courseid;
                $datos->fullname = $nombre_curso;
                $datos->summary = implode("<br/>", $summary);
                update_course($datos);
            }

            // Se registran los datos tal como llega del BAck en mdl_curso_back
            \local_pubsub\back\curso::insert_update_curso_back($response, $courseid);
        } else {
            $dataresponse = array(
                'msg' => 'Tipo de curso no configurado en local_pubsub',
                'response' => $response
            );
            \local_pubsub\metodos_comunes::save_event_create_course(\context_system::instance(), $dataresponse);
            $courseid = null;
        }

        return [
			"moodlecourseid" => $courseid
		];
	}

	/**
	 * @return external_single_structure
	 */
	public static function upsert_course_returns()
	{
		return new external_single_structure(
			array(
				'moodlecourseid' => new external_value(PARAM_RAW, 'id del curso creado o actualizado'),
			)
		);
	}
}
