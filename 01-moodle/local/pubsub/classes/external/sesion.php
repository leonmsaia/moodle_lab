<?php

namespace local_pubsub\external;

use dml_exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use local_pubsub\metodos_comunes;
use local_pubsub\utils;
use Exception;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');

class sesion extends external_api
{

	/**
	 * @return external_function_parameters
	 */
	public static function create_sesion_parameters()
	{
		return new external_function_parameters(
			array(
				'Id' => new external_value(PARAM_RAW, 'guid de la sesion'),
				'IdCurso' => new external_value(PARAM_RAW, 'guid del curso'),
				'IdEvento' => new external_value(PARAM_RAW, 'guid del evento'),
				'Action' => new external_value(PARAM_RAW, 'Accion (Actualizacion o Alta)')
			)
		);
	}

	/**
	 * @param $id
	 * @param $idcurso
	 * @param $idevento
	 * @param $action
	 * @return array
	 * @throws dml_exception
	 * @throws invalid_parameter_exception
	 * @throws moodle_exception
	 */
	public static function create_sesion_new($id, $idcurso, $idevento, $action)
	{
		global $DB;
		/** @var external_api $api */
        $api = new class extends external_api {};
        $params = $api::validate_parameters(
			self::create_sesion_parameters(),
			array(
				'Id' => $id,
				'IdCurso' => $idcurso,
				'IdEvento' => $idevento,
				'Action' => $action
			)
		);

		$endpoint = get_config('local_pubsub', 'endpointsession') . $params["Id"];
		$statusapproved = get_config('local_pubsub', 'approvedstatus');
		$statussuspended = get_config('local_pubsub', 'suspendedstatus');

		$response = metodos_comunes::request($endpoint);
		if ($response["status"] > 299) {
			throw new moodle_exception("error request:" . $response["status"] . "endpoint: " . $endpoint);
		}
		$response = json_decode($response["data"], true);
		$dataresponse = array('response' =>  json_encode($response));
		metodos_comunes::save_event_response_endpointsession(\context_system::instance(), $dataresponse);

		/* ej datos
         array (
            'AgenciaMutual' => 'SD000011',
            'Auditorio' => '',
            'CantidadParticipantes' => '',
            'CapacitacionPortal' => 'False',
            'CodigoCurso' => 'S-000000000000145',
            'Estado' => 100000000,
            'Id' => '8be41d1e-f856-ea11-a811-000d3a4f62e7',
            'IdEjecutivo' => '',
            'IdEvento' => '',
            'IdRelator' => '',
            'InicioCapacitacion' => '2020-02-24T08:30:00Z',
            'MotivoSuspension' => '',
            'TerminoCapacitacion' => '2020-02-24T18:30:00Z',
            )
         */

		$attendanceverify = $DB->get_record('eabcattendance_sessions', array('guid' => $params["Id"]));
		$sesionid = null;

		//valido que el guid no este guardado
		if (!(($response["Estado"] == $statusapproved) || ($response["Estado"] == $statussuspended))) {			
			// SI el estado recibido no corresponde con Aprobado o suspendido, se devuelve un mensaje 
			return [
				"moodlesesionid" => "El estado de la sesion no corresponde a Aprobado o Suspendido, Codigo recibido: " . $response["Estado"]
			];

		}

		if (empty($attendanceverify)) {
			$timestart = utils::date_to_timestamp($response['InicioCapacitacion']);
			$timeend = utils::date_to_timestamp($response['TerminoCapacitacion']);
			$timesecond = $timeend - $timestart;
			// Crear o actualizar el curso
			$course = external_api::call_external_function("local_pubsub_upsert_course", ["ID" => $params["IdCurso"]]);

			$get_attendances = $DB->get_records('eabcattendance', array('course' => $course["data"]["moodlecourseid"]));
			if (empty($get_attendances)) {
				//guardar evento
				$other = array(
					'error' => get_string('coursenotattendance', 'local_pubsub'),
					'guid' => $params["Id"],
				);
				metodos_comunes::save_event_sessions(\context_course::instance($course["data"]["moodlecourseid"]), $other, $course["data"]["moodlecourseid"]);
				echo get_string('coursenotattendance', 'local_pubsub');

				throw new moodle_exception("coursenotattendance", "local_pubsub");
			}

			foreach ($get_attendances as $get_attendance) {

				$cm = get_coursemodule_from_instance('eabcattendance', $get_attendance->id, 0, false, MUST_EXIST);
				$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
				$attendanceid = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);
				// Check permissions.
				// Validate group.
				$groupmode = (int)groups_get_activity_groupmode($cm);
                $guidevento = explode("-", $idevento);
                $groupname = date("d-m-Y H:i", $timestart) . " " . $guidevento[0];

                // Primero, buscar si el grupo ya existe por nombre en el curso.
                $groupid = $DB->get_field('groups', 'id', ['courseid' => $course->id, 'name' => $groupname]);

                if (!$groupid) {
                    // Si no se encuentra por nombre, se busca en la tabla pivote.
                    $groups = $DB->get_record('eabcattendance_course_groups', array('curso' => $course->id, 'uuid' => $params["IdEvento"]));
                    if (empty($groups)) {
                        // Si tampoco está en la tabla pivote, se crea el grupo.
                        $creategropup = array("createname" => $groupname);
                        $groupid = metodos_comunes::create_group($creategropup, $course);
                    } else {
                        // Se encontró una referencia en la tabla pivote, ahora hay que validar que el grupo realmente exista.
                        $groupid = $groups->grupo;
                        if (!$DB->record_exists('groups', array('id' => $groupid))) {
                            // El grupo no existe en la tabla de grupos, así que lo creamos.
                            $creategropup = array("createname" => $groupname);
                            $groupid = metodos_comunes::create_group($creategropup, $course);
                        }
                    }
                }

                // Asegurarse de que la asociación exista en la tabla pivote.
                if ($groupid && !$DB->record_exists('eabcattendance_course_groups', array('grupo' => $groupid, 'uuid' => $params["IdEvento"]))) {
                    metodos_comunes::eabcattendance_course_groups($groupid, $course->id, $params["IdEvento"]);
                }

                $groups = $DB->get_record('eabcattendance_course_groups', array('curso' => $course->id, 'uuid' => $params["IdEvento"]));
				if ($groupmode === SEPARATEGROUPS || ($groupmode === VISIBLEGROUPS && $groups > 0)) {

					// Determine valid groups.
					if ($response["Estado"] == $statusapproved) {
						//si llega con estatus aprobado guardar
                        $sesionid = metodos_comunes::create_or_update_session($attendanceid, $cm, $course, $timestart, $timesecond, $groups, $params["Id"]);
                        \local_pubsub\back\sesion::inser_update_sesion_back($response, $sesionid);
					}
					if ($response["Estado"] == $statussuspended) {
						//suspender
						external_api::call_external_function("format_eabctiles_suspendactivity", ["groupid" => $groups->grupo , "courseid" => $course->id, "motivo" => $response['MotivoSuspension'] ,"textother" => "suspendido desde back"]);
						//guardar evento
						metodos_comunes::save_event_response_suspend_session(\context_course::instance($course->id), $response);
                        $sesionid = metodos_comunes::create_or_update_session($attendanceid, $cm, $course, $timestart, $timesecond, $groups, $params["Id"]);
						\local_pubsub\back\sesion::inser_update_sesion_back($response, $sesionid);
					}
				} else {
					//guardar evento
					$other = array(
						'error' => get_string('activitynoconfigure', 'local_pubsub'),
						'guid' => $params["Id"],
					);
					metodos_comunes::save_event_sessions(\context_course::instance($course["data"]["moodlecourseid"]), $other, $course["data"]["moodlecourseid"]);
					echo get_string('activitynoconfigure', 'local_pubsub');
				}
				// creo la sesion en el primer eabcattendance que tenga el curso
				continue;
			}
		}

		return [
			"moodlesesionid" => $sesionid
		];
	}
    public static function create_sesion($id, $idcurso, $idevento, $action)
	{
		global $DB;
		/** @var external_api $api */
        $api = new class extends external_api {};
        $params = $api::validate_parameters(
			self::create_sesion_parameters(),
			array(
				'Id' => $id,
				'IdCurso' => $idcurso,
				'IdEvento' => $idevento,
				'Action' => $action
			)
		);

		$endpoint = get_config('local_pubsub', 'endpointsession') . $params["Id"];
		$statusapproved = get_config('local_pubsub', 'approvedstatus');
		$statussuspended = get_config('local_pubsub', 'suspendedstatus');

		$response = metodos_comunes::request($endpoint);
		if ($response["status"] > 299) {
			throw new moodle_exception("error request:" . $response["status"] . "endpoint: " . $endpoint);
		}
		$response = json_decode($response["data"], true);
		$dataresponse = array('response' =>  json_encode($response));
		metodos_comunes::save_event_response_endpointsession(\context_system::instance(), $dataresponse);

		/* ej datos
         array (
            'AgenciaMutual' => 'SD000011',
            'Auditorio' => '',
            'CantidadParticipantes' => '',
            'CapacitacionPortal' => 'False',
            'CodigoCurso' => 'S-000000000000145',
            'Estado' => 100000000,
            'Id' => '8be41d1e-f856-ea11-a811-000d3a4f62e7',
            'IdEjecutivo' => '',
            'IdEvento' => '',
            'IdRelator' => '',
            'InicioCapacitacion' => '2020-02-24T08:30:00Z',
            'MotivoSuspension' => '',
            'TerminoCapacitacion' => '2020-02-24T18:30:00Z',
            )
         */

		$attendanceverify = $DB->get_record('eabcattendance_sessions', array('guid' => $params["Id"]));
		$sesionid = null;
		//valido que el guid no este guardado
		if (!(($response["Estado"] == $statusapproved) || ($response["Estado"] == $statussuspended))) {
			// SI el estado recibido no corresponde con Aprobado o suspendido, se devuelve un mensaje
			return [
				"moodlesesionid" => "El estado de la sesion no corresponde a Aprobado o Suspendido, Codigo recibido: " . $response["Estado"]
			];

		}

		if (empty($attendanceverify)) {
			$timestart = utils::date_to_timestamp($response['InicioCapacitacion']);
			$timeend = utils::date_to_timestamp($response['TerminoCapacitacion']);
			$timesecond = $timeend - $timestart;
			// Crear o actualizar el curso
			$course = external_api::call_external_function("local_pubsub_upsert_course", ["ID" => $params["IdCurso"]]);

			$get_attendances = $DB->get_records('eabcattendance', array('course' => $course["data"]["moodlecourseid"]));
			if (empty($get_attendances)) {
				//guardar evento
				$other = array(
					'error' => get_string('coursenotattendance', 'local_pubsub'),
					'guid' => $params["Id"],
				);
				metodos_comunes::save_event_sessions(\context_course::instance($course["data"]["moodlecourseid"]), $other, $course["data"]["moodlecourseid"]);
				echo get_string('coursenotattendance', 'local_pubsub');

				throw new moodle_exception("coursenotattendance", "local_pubsub");
			}

			foreach ($get_attendances as $get_attendance) {

				$cm = get_coursemodule_from_instance('eabcattendance', $get_attendance->id, 0, false, MUST_EXIST);
				$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
				$attendanceid = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);
				// Check permissions.
				// Validate group.
				$groupmode = (int)groups_get_activity_groupmode($cm);
				//valido si el guid del grupo ya existe en mooodle asociado a un grupo
				$groups = $DB->get_record('eabcattendance_course_groups', array('curso' => $course->id, 'uuid' => $params["IdEvento"]));

				if (!empty($groups)) {
					// Se encontró una referencia en la tabla pivote, ahora hay que validar que el grupo realmente exista.
					if (!$DB->record_exists('groups', array('id' => $groups->grupo))) {
						// El grupo no existe, así que se anula para que se cree uno nuevo.
						$groups = null;
					}
				}

				if (empty($groups)) {
					$guidevento = explode("-", $idevento);
					//si no existe el guid asocuado a un curso creo el grupo
					$creategropup = array("createname" => date("d-m-Y H:i", $timestart) . " " . $guidevento[0]);
					$groupid = metodos_comunes::create_group($creategropup, $course);
					metodos_comunes::eabcattendance_course_groups($groupid, $course->id, $params["IdEvento"]);
					$groups = $DB->get_record('eabcattendance_course_groups', array('curso' => $course->id, 'grupo' => $groupid, 'uuid' => $params["IdEvento"]));
				}
				if ($groupmode === SEPARATEGROUPS || ($groupmode === VISIBLEGROUPS && $groups > 0)) {
					$msg = new \stdClass();
					$msg->guid = $params["Id"];
					// Determine valid groups.

					if ($response["Estado"] == $statusapproved) {
						//si llega con estatus aprobado guardar
						try {
							$sesionid = metodos_comunes::create_session($attendanceid, $cm, $course, $timestart, $timesecond, $groups, $msg, $course->id);
						} catch (\dml_write_exception $e) {
							$existingsession = $DB->get_record('eabcattendance_sessions', ['guid' => $params["Id"]]);
							$sesionid = $existingsession->id ?? null;
						}
						\local_pubsub\back\sesion::inser_update_sesion_back($response, $sesionid);
					}
					if ($response["Estado"] == $statussuspended) {
						//suspender
						external_api::call_external_function("format_eabctiles_suspendactivity", ["groupid" => $groups->grupo , "courseid" => $course->id, "motivo" => $response['MotivoSuspension'] ,"textother" => "suspendido desde back"]);
						//guardar evento
						metodos_comunes::save_event_response_suspend_session(\context_course::instance($course->id), $response);
						$session = $DB->get_record('eabcattendance_sessions', array('guid' => $response['Id']));
						\local_pubsub\back\sesion::inser_update_sesion_back($response, $session->id);
						$sesionid = $session->id;
					}
				} else {
					//guardar evento
					$other = array(
						'error' => get_string('activitynoconfigure', 'local_pubsub'),
						'guid' => $params["Id"],
					);
					metodos_comunes::save_event_sessions(\context_course::instance($course["data"]["moodlecourseid"]), $other, $course["data"]["moodlecourseid"]);
					echo get_string('activitynoconfigure', 'local_pubsub');
				}
				// creo la sesion en el primer eabcattendance que tenga el curso
				continue;
			}
		}else{
            $sesionid = $attendanceverify->id;
        }

		return [
			"moodlesesionid" => $sesionid
		];
	}

	/**
	 * @return external_single_structure
	 */
	public static function create_sesion_returns()
	{
		return new external_single_structure(
			array(
				'moodlesesionid' => new external_value(PARAM_RAW, 'id sesion moodle'),
			)
		);
	}

	/** UPDATE SESION */

	/**
	 * @return external_function_parameters
	 */
	public static function update_sesion_parameters()
	{
		return new external_function_parameters(
			array(
				'Id' => new external_value(PARAM_RAW, 'guid de la sesion'),
				'IdCurso' => new external_value(PARAM_RAW, 'guid del curso'),
				'IdEvento' => new external_value(PARAM_RAW, 'guid del evento'),
				'Action' => new external_value(PARAM_RAW, 'Accion (Actualizacion o Alta)')
			)
		);
	}

	/**
	 * @param $id
	 * @param $idcurso
	 * @param $idevento
	 * @param $action
	 * @return array
	 * @throws dml_exception
	 * @throws invalid_parameter_exception
	 * @throws moodle_exception
	 */
    public static function update_sesion_new($id, $idcurso, $idevento, $action)
    {
        global $DB;

        // 🔹 Validar parámetros
        $params = \external_api::validate_parameters(
            self::create_sesion_parameters(),
            [
                'Id' => $id,
                'IdCurso' => $idcurso,
                'IdEvento' => $idevento,
                'Action' => $action
            ]
        );

        // 🔹 Cargar configuración una sola vez
        $config = (object)[
            'approvedstatus'   => get_config('local_pubsub', 'approvedstatus'),
            'suspendedstatus'  => get_config('local_pubsub', 'suspendedstatus'),
            'endpointsession'  => get_config('local_pubsub', 'endpointsession'),
            'rolwscreateactivity' => get_config('eabcattendance', 'rolwscreateactivity') ?: 3
        ];

        // 🔹 Consultar endpoint remoto
        $endpoint = $config->endpointsession . $params['Id'];
        $response = \local_pubsub\metodos_comunes::request($endpoint);
        \local_pubsub\metodos_comunes::save_event_response_endpointsession(
            \context_system::instance(),
            ['response' => json_encode($response)]
        );

        if ($response['status'] > 299) {
            throw new \moodle_exception("error request: {$response['status']} Endpoint: $endpoint");
        }

        $response = json_decode($response['data'], true);

        // 🔹 Validar estado
        if (!(($response['Estado'] == $config->approvedstatus) || ($response['Estado'] == $config->suspendedstatus))) {
            return [
                "moodlesesionid" => "El estado de la sesión no corresponde a Aprobado o Suspendido. Código recibido: {$response['Estado']}"
            ];
        }

        // 🔹 Fechas y duración (sin fix_invierno)
        $timestart = utils::date_to_timestamp($response['InicioCapacitacion']);
        $timeend   = utils::date_to_timestamp($response['TerminoCapacitacion']);
        $timesecond = $timeend - $timestart;

        // 🔹 Crear o actualizar el curso desde WS (mantener comportamiento original)
        $course = \external_api::call_external_function("local_pubsub_upsert_course", ["ID" => $params["IdCurso"]]);
        if ($course["error"]) {
            throw new \moodle_exception($course["error"]);
        }

        $course = $DB->get_record('course', ['id' => $course["data"]["moodlecourseid"]]);
        $coursecontext = \context_course::instance($course->id);

        // 🔹 Grupo (manteniendo lógica de búsqueda y actualización mejorada)
        $guidevento = explode("-", $idevento);
        $name_group = date("d-m-Y H:i", $timestart) . " " . $guidevento[0];

        $group = $DB->get_record('eabcattendance_course_groups', [
            'uuid' => $params['IdEvento'],
            'curso' => $course->id
        ]);

        if ($group) {
            $get_grupo = $DB->get_record('groups', ['id' => $group->grupo]);
        } else {
            $get_grupo = null;
        }

        if ($get_grupo) {
            if ($get_grupo->name !== $name_group) {
                $record = (object)[
                    'id' => $get_grupo->id,
                    'courseid' => $course->id,
                    'name' => $name_group
                ];
                groups_update_group($record);
            }
            $groupid = $get_grupo->id;
        } else {
            $groupid = \local_pubsub\metodos_comunes::create_group(['createname' => $name_group], $course);

            if (empty($groupid)) {
                throw new \moodle_exception("Error al crear grupo");
            }
            \local_pubsub\metodos_comunes::eabcattendance_course_groups($groupid, $course->id, $params["IdEvento"]);
        }

        // 🔹 Crear relator (mantener del original)
        if (!empty($response['IdRelator'])) {
            $guion = substr($response['IdRelator'], -2, 1);
            if ($guion !== "-") {
                $rut = substr($response['IdRelator'], 0, -1);
                $dv = substr($response['IdRelator'], -1, 1);
                $response['IdRelator'] = sprintf("%s-%s", $rut, $dv);
            }

            $user = $DB->get_record('user', ['username' => $response['IdRelator']]);
            if ($user) {
                \local_pubsub\metodos_comunes::enrol_user($course, $user->id, $groupid, $config->rolwscreateactivity);
                role_assign($config->rolwscreateactivity, $user->id, $coursecontext->id);
            } else {
                $other = [
                    'error' => 'Relator no registrado en Moodle',
                    'guid' => "Guid de sesión: " . $params['Id']
                ];
                \local_pubsub\metodos_comunes::save_event_sessions(\context_course::instance($course->id), $other, $course->id);
            }
        }

        // 🔹 Obtener sesión y grupo existentes
        $get_session = $DB->get_record('eabcattendance_sessions', ['guid' => $params['Id']]);
        $groups = $DB->get_record('eabcattendance_course_groups', [
            'curso' => $course->id,
            'uuid' => $params['IdEvento']
        ]);

        // Si devuelve null el servicio, es porque no está encontrando la sesión o el attendance
        $sesionid = null;
        if ($response["Estado"] == $config->approvedstatus) {
            //si llega con estatus aprobado guardar
            if (empty($get_session)) {
                $session = external_api::call_external_function("local_pubsub_create_sesion", $params);
                $sesionid = $session["data"]["moodlesesionid"];
            } else {
                $attendance = $DB->get_record('eabcattendance', ['id' => $get_session->eabcattendanceid]);
                if(empty($attendance)){
                    $get_attendances_instances = $DB->get_records('eabcattendance', ['course' => $course["data"]["moodlecourseid"]]);
                    if (!empty($get_attendances_instances)) {
                        $attendance = reset($get_attendances_instances);
                    }else{
                        throw new Exception('Attendance no existe.');
                    }
                }

                $cm = get_coursemodule_from_instance('eabcattendance', $attendance->id, 0, false, MUST_EXIST);
                $sesionid = metodos_comunes::create_or_update_session($attendance, $cm, $course, $timestart, $timesecond, $groups, $params["Id"]);
            }
        }elseif ($response["Estado"] == $config->suspendedstatus) {
            if($get_session){
                $sesionid = $get_session->id;
            }

            \external_api::call_external_function(
                "format_eabctiles_suspendactivity",
                [
                    "groupid" => $groups->grupo,
                    "courseid" => $course->id,
                    "motivo" => $response['MotivoSuspension'],
                    "textother" => "suspendido desde back"
                ]
            );

            \local_pubsub\metodos_comunes::save_event_response_suspend_session(
                \context_course::instance($course->id),
                $response
            );
        }

        // 🔹 Actualizar tabla sesion_back
        \local_pubsub\back\sesion::inser_update_sesion_back($response, $sesionid);

        return [
            "moodlesesionid" => $sesionid
        ];
    }

    public static function update_sesion($id, $idcurso, $idevento, $action)
	{
		global $DB;
		/** @var external_api $api */
        $api = new class extends external_api {};
        $params = $api::validate_parameters(
			self::create_sesion_parameters(),
			array(
				'Id' => $id,
				'IdCurso' => $idcurso,
				'IdEvento' => $idevento,
				'Action' => $action
			)
		);
		$statusapproved = get_config('local_pubsub', 'approvedstatus');
		$statussuspended = get_config('local_pubsub', 'suspendedstatus');

		$endpoint = get_config('local_pubsub', 'endpointsession') . $params["Id"];
		$response = metodos_comunes::request($endpoint);
		$dataresponse = array('response' =>  json_encode($response));
		metodos_comunes::save_event_response_endpointsession(\context_system::instance(), $dataresponse);

		if ($response["status"] > 299) {
			throw new moodle_exception("error request:" . $response["status"] . " Endpoint: " . $endpoint);
		}
		$response = json_decode($response["data"], true);
		$attendanceid = null;

		if (!(($response["Estado"] == $statusapproved) || ($response["Estado"] == $statussuspended))) {
			// SI el estado recibido no corresponde con Aprobado o suspendido, se devuelve un mensaje
			return [
				"moodlesesionid" => "El estado de la sesion no corresponde a Aprobado o Suspendido, Codigo recibido: " . $response["Estado"]
			];
		}

		$timestart = utils::date_to_timestamp($response['InicioCapacitacion']);
		$timeend = utils::date_to_timestamp($response['TerminoCapacitacion']);
		$timesecond = $timeend - $timestart;

		// Crear o actualizar el curso para la sesion
		$course = external_api::call_external_function("local_pubsub_upsert_course", ["ID" => $params["IdCurso"]]);

		if ($course["error"]) {
			throw new \moodle_exception($course);
		}
		$role = get_config("eabcattendance", "rolwscreateactivity");
		$roleid = (!empty($role)) ? $role : 3;
		$course = $DB->get_record('course', array('id' => $course["data"]["moodlecourseid"]));

		$coursecontext = \context_course::instance($course->id);

		$guidevento = explode("-", $idevento);
		$name_group = date("d-m-Y H:i", $timestart) . " " . $guidevento[0];

		$groups = $DB->get_records('eabcattendance_course_groups', array('uuid' => $params["IdEvento"], 'curso' => $course->id ));
		$group = end($groups);
		$get_grupo = null;

		if ($group) {
			$get_grupo = $DB->get_record("groups", array("id" => $group->grupo));
		}

		if($get_grupo){
			if ($get_grupo->name !== $name_group){
				// Si cambió la fecha actualizo el nombre del grupo
				$record = new \stdClass();
				$record->id = $get_grupo->id;
				$record->courseid = $course->id;
				$record->name = $name_group;
				groups_update_group($record);
			}
			$groupid = $get_grupo->id;
		}else {
			//si no existe el guid asocuado a un curso creo el grupo
			$creategropup = array("createname" => $name_group);
			$groupid = metodos_comunes::create_group($creategropup, $course);
			if (empty($groupid)) {
				throw new moodle_exception("Error al crear grupo");
			}
			metodos_comunes::eabcattendance_course_groups($groupid, $course->id, $params["IdEvento"]);
			$group = $DB->get_record('eabcattendance_course_groups', array('curso' => $course->id, 'grupo' => $groupid, 'uuid' => $params["IdEvento"]));
		}

		// crear relator
		if (!empty($response['IdRelator'])) {
            $guion = substr($response['IdRelator'], -2, 1);
            if($guion !== "-") {
                $rut = substr($response['IdRelator'],0, -1);
                $dv = substr($response['IdRelator'], -1, 1);
                $response['IdRelator'] = sprintf("%s-%s", $rut, $dv);
            }
			$user = $DB->get_record('user', array('username' => $response['IdRelator']));
			if ($user) {
				metodos_comunes::enrol_user($course, $user->id, $groupid, $roleid);
				role_assign($roleid, $user->id, $coursecontext->id);
			} else {
				$other = array(
					'error' => 'Relator no registrado en moodle',
					'guid' => "Guid de sesion: " . $params["Id"],
				);
				metodos_comunes::save_event_sessions(\context_course::instance($course->id), $other, $course->id);
			}
		}

		// Obtener sesion
		$get_attendances = $DB->get_record('eabcattendance_sessions', array('guid' => $params['Id']));

		if ($response["Estado"] == $statusapproved) {
			//si llega con estatus aprobado guardar
			if (empty($get_attendances)) {
				$session = external_api::call_external_function("local_pubsub_create_sesion", $params);
				$attendanceid = $session["data"]["moodlesesionid"];
			} else {
				$dataobject = new stdClass();
				$dataobject->id = $get_attendances->id;
				$dataobject->sessdate = $timestart;
				$dataobject->duration = $timesecond;
				$DB->update_record('eabcattendance_sessions', $dataobject);
				$attendanceid = $get_attendances->id;
			}
        }
        if ($response["Estado"] == $statussuspended) {
			//suspender
			external_api::call_external_function("format_eabctiles_suspendactivity", ["groupid" => $group->grupo , "courseid" => $course->id, "motivo" => $response['MotivoSuspension'] ,"textother" => "suspendido desde back"]);
			//guardar evento
			metodos_comunes::save_event_response_suspend_session(\context_course::instance($course->id), $response);
		}

		//Actualiza tabla sesion_back con los datos de la session
		\local_pubsub\back\sesion::inser_update_sesion_back($response, $get_attendances->id);

		return [
			"moodlesesionid" => $attendanceid
		];
	}

	/**
	 * @return external_single_structure
	 */
	public static function update_sesion_returns()
	{
		return new external_single_structure(
			array(
				'moodlesesionid' => new external_value(PARAM_RAW, 'mje sesion actualizada'),
			)
		);
	}
}

