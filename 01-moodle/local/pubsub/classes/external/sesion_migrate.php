<?php

namespace local_pubsub\external;

use DateTime;
use DateTimeZone;
use dml_exception;
use Exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use local_pubsub\metodos_comunes;
use local_pubsub\utils;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');

class sesion_migrate extends external_api
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

		$response = \local_pubsub\metodos_comunes::request($endpoint);
		if ($response["status"] > 299) {
			throw new moodle_exception("error request:" . $response["status"] . "endpoint: " . $endpoint);
		}
		$response = json_decode($response["data"], true);
		$dataresponse = array('response' =>  json_encode($response));
		\local_pubsub\metodos_comunes::save_event_response_endpointsession(\context_system::instance(), $dataresponse);
		

		$attendanceverify = $DB->get_record('eabcattendance_sessions', array('guid' => $params["Id"]));
		$sesionid = null;
		

		if (empty($attendanceverify)) {
			$timestart = utils::date_to_timestamp($response['InicioCapacitacion']);
            $timestart = self::fix_invierno_2025($timestart);
			$timeend = utils::date_to_timestamp($response['TerminoCapacitacion']);
            $timeend = self::fix_invierno_2025($timeend);
			$timesecond = $timeend - $timestart;
			// Crear o actualizar el curso
			//$course = external_api::call_external_function("local_pubsub_upsert_course", ["ID" => $params["IdCurso"]]);

            $sql = "SELECT 
                c.*
            FROM {curso_back} cb
            JOIN {course} c 
                 ON c.id = cb.id_curso_moodle
            WHERE cb.productoid = :productoid";

            $sql_params = ['productoid' => $idcurso];
            $course = $DB->get_record_sql($sql, $sql_params, MUST_EXIST);
            if( !$course ) {
                throw new moodle_exception("Curso no encontrado en Moodle para el ID externo: " . $idcurso);
            }

			$get_attendances = $DB->get_records('eabcattendance', array('course' => $course->id));
			if (empty($get_attendances)) {
				//guardar evento
				$other = array(
					'error' => get_string('coursenotattendance', 'local_pubsub'),
					'guid' => $params["Id"],
				);
				metodos_comunes::save_event_sessions(\context_course::instance($course->id), $other, $course->id);
				echo get_string('coursenotattendance', 'local_pubsub');

				throw new moodle_exception("coursenotattendance", "local_pubsub");
			}

			foreach ($get_attendances as $get_attendance) {

				$cm = get_coursemodule_from_instance('eabcattendance', $get_attendance->id, 0, false, MUST_EXIST);
				//$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
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
					
					//si llega con estatus aprobado guardar
					$sesionid = metodos_comunes::create_or_update_session($attendanceid, $cm, $course, $timestart, $timesecond, $groups, $params["Id"]);
					\local_pubsub\back\sesion::inser_update_sesion_back($response, $sesionid);

				} else {
					//guardar evento
					$other = array(
						'error' => get_string('activitynoconfigure', 'local_pubsub'),
						'guid' => $params["Id"],
					);
					metodos_comunes::save_event_sessions(\context_course::instance($course->id), $other, $course->id);
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
    public static function update_sesion($id, $idcurso, $idevento, $action)
    {
        global $DB;

        // 🔹 Validar parámetros (sin clase anónima innecesaria)
        $params = \external_api::validate_parameters(
            self::create_sesion_parameters(),
            [
                'Id' => $id,
                'IdCurso' => $idcurso,
                'IdEvento' => $idevento,
                'Action' => $action
            ]
        );

        // 🔹 Cargar configuraciones una sola vez (reduce consultas)
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

        // 🔹 Tiempos
        $timestart = self::fix_invierno_2025(utils::date_to_timestamp($response['InicioCapacitacion']));
        $timeend   = self::fix_invierno_2025(utils::date_to_timestamp($response['TerminoCapacitacion']));
        $timesecond = $timeend - $timestart;

        // 🔹 Buscar curso en Moodle
        $course = $DB->get_record_sql("
            SELECT c.*
            FROM {curso_back} cb
            JOIN {course} c ON c.shortname = cb.codigocurso and c.id = cb.id_curso_moodle
            WHERE cb.productoid = :productoid
        ", ['productoid' => $idcurso]);

        if (!$course) {
            throw new \moodle_exception("Curso no encontrado en Moodle para el ID externo: " . $idcurso);
        }

        $coursecontext = \context_course::instance($course->id);

        // 🔹 Nombre de grupo
        $guidevento = explode("-", $idevento);
        $name_group = date("d-m-Y H:i", $timestart) . " " . $guidevento[0];

        // 🔹 Buscar grupo existente por nombre
        $groupid = $DB->get_field('groups', 'id', [
            'courseid' => $course->id,
            'name' => $name_group
        ]);

        // 🔹 Intentar recuperar grupo desde la tabla pivote si no existe
        if (!$groupid) {
            $group_from_pivot = $DB->get_record('eabcattendance_course_groups', [
                'uuid' => $params['IdEvento'],
                'curso' => $course->id
            ]);

            if (empty($group_from_pivot)) {
                // Crear grupo si no existe en pivote ni en grupos
                $groupid = \local_pubsub\metodos_comunes::create_group(['createname' => $name_group], $course);
                if (empty($groupid)) {
                    throw new \moodle_exception("Error al crear grupo");
                }
            } else {
                $groupid = $group_from_pivot->grupo;
                // Validar que el grupo aún exista
                if (!$DB->record_exists('groups', ['id' => $groupid])) {
                    $groupid = \local_pubsub\metodos_comunes::create_group(['createname' => $name_group], $course);
                    if (empty($groupid)) {
                        throw new \moodle_exception("Error al crear grupo");
                    }
                }
            }
        }

        // 🔹 Actualizar nombre si ha cambiado
        if ($groupid) {
            $get_grupo = $DB->get_record('groups', ['id' => $groupid]);
            if ($get_grupo && $get_grupo->name !== $name_group) {
                $record = (object)[
                    'id' => $get_grupo->id,
                    'courseid' => $course->id,
                    'name' => $name_group
                ];
                groups_update_group($record);
            }
        }

        // 🔹 Asegurar existencia en la tabla pivote
        if ($groupid) {
            \local_pubsub\metodos_comunes::eabcattendance_course_groups($groupid, $course->id, $params['IdEvento']);
        }else{
            throw new \moodle_exception("Error al obtener el ID de grupo en el curso: " . $course->id);
        }

        // 🔹 Buscar la relación grupo-curso-uuid
        $groups = $DB->get_record('eabcattendance_course_groups', [
            'curso' => $course->id,
            'uuid' => $params['IdEvento']
        ]);

        if (empty($groups) || !$DB->record_exists('groups', ['id' => $groups->grupo])) {
            throw new \moodle_exception("No se pudo encontrar o crear una referencia de grupo válida para el evento UUID: " . $params['IdEvento']);
        }

        $groupid = $groups->grupo;

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
                \local_pubsub\metodos_comunes::enrol_user($course, $user->id, $groups->grupo, $config->rolwscreateactivity);
                role_assign($config->rolwscreateactivity, $user->id, $coursecontext->id);
            } else {
                $other = [
                    'error' => 'Relator no registrado en Moodle',
                    'guid' => "Guid de sesión: " . $params['Id']
                ];
                \local_pubsub\metodos_comunes::save_event_sessions(\context_course::instance($course->id), $other, $course->id);
            }
        }



        // 🔹 Buscar o crear sesión de asistencia
        $get_session = $DB->get_record('eabcattendance_sessions', ['guid' => $params['Id']]);

        if ($get_session) {
            $attendance = $DB->get_record('eabcattendance', ['id' => $get_session->eabcattendanceid]);
            $cm = get_coursemodule_from_instance('eabcattendance', $attendance->id, 0, false, MUST_EXIST);
        } else {
            // Buscar actividad de asistencia
            $get_attendances_activities = $DB->get_records('eabcattendance', ['course' => $course->id]);

            if (empty($get_attendances_activities)) {
                $other = [
                    'error' => get_string('coursenotattendance', 'local_pubsub'),
                    'guid' => $params['Id']
                ];
                \local_pubsub\metodos_comunes::save_event_sessions(\context_course::instance($course->id), $other, $course->id);
                throw new \moodle_exception("coursenotattendance", "local_pubsub");
            }

            // Tomar el primero disponible
            $attendance = reset($get_attendances_activities);
            $cm = get_coursemodule_from_instance('eabcattendance', $attendance->id, 0, false, MUST_EXIST);
        }

        // 🔹 Crear o actualizar sesión
        $sesionid = \local_pubsub\metodos_comunes::create_or_update_session(
            $attendance,
            $cm,
            $course,
            $timestart,
            $timesecond,
            $groups,
            $params['Id']
        );

        $participantes = \local_pubsub\metodos_comunes::get_participantes_sesion($params['Id']);

        global $CFG;
        require_once $CFG->libdir.'/enrollib.php';
        $enrolplugin = enrol_get_plugin('manual');


        // ============================================
        // 🔹 OPTIMIZACIÓN: Cargar todos los usuarios de una sola vez
        // ============================================

        // Normalizar identificadores y preparar mapeo
        $usernames = [];
        foreach ($participantes as $p) {
            $rut = strtolower(trim($p['ParticipanteIdentificador']));
            if (!empty($rut)) {
                $usernames[$rut] = $p;
            }
        }

        // Cargar todos los usuarios existentes en una sola consulta
        $existing_users = !empty($usernames)
            ? $DB->get_records_list('user', 'username', array_keys($usernames))
            : [];

        // Cargar todos los usuarios enrolados para evitar múltiples consultas
        $enrolled_users = get_enrolled_users($coursecontext, '', 0, 'u.id, u.username');
        $enrolled_map = array_column($enrolled_users, 'id', 'username');

        $enrolinstances = $DB->get_records('enrol', [
            'courseid'      => $course->id,
            'status'        =>  ENROL_INSTANCE_ENABLED,
            'enrol'         => 'manual'
        ], 'sortorder,id');

        if(empty($enrolinstances)){
            throw new \Exception('No existe la instancia de matriculación manual.');
        }
        $enrolinstances = reset($enrolinstances);



        foreach ($usernames as $rut => $participante) {

            $user = $existing_users[$rut] ?? null;

            if ($user) {
                // Usuario existente
                if (!isset($enrolled_map[$rut])) {
                    $enrolplugin->enrol_user($enrolinstances, $user->id, 5);
                }
                groups_add_member($groupid, $user->id);
            }else{
                // Creamos el usuario si no existe
                $enrol_passport = ($participante['ParticipanteTipoDocumento'] == 100);

                try {
                    $transaction = $DB->start_delegated_transaction();

                    // Obtener sesión (para registrar el participante correctamente)
                    $get_session = $DB->get_record('eabcattendance_sessions', ['id' => $sesionid]);

                    // Crear usuario y enrolarlo
                    $newuserid = \local_pubsub\metodos_comunes::register_participants($rut, $participante, $course, $get_session, $enrol_passport, false);


                    if(!empty($newuserid)){
                        groups_add_member($groupid, $newuserid);
                    }else{
                        throw new \Exception('Usuario vacío');
                    }

                    $transaction->allow_commit();
                } catch (Exception $e) {
                    $transaction->rollback($e);
                }

            }
        }

        // Si está suspendida la sesión, hacemos el call al ws de suspender sesiones
        if ($response["Estado"] == $config->suspendedstatus) {

            \external_api::call_external_function(
                "format_eabctiles_suspendactivity",
                [
                    "groupid" => $groupid,
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
            'moodlesesionid' => $sesionid
        ];
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
	public static function update_sesion_back($id, $idcurso, $idevento, $action)
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
		$response = \local_pubsub\metodos_comunes::request($endpoint);
		$dataresponse = array('response' =>  json_encode($response));
		\local_pubsub\metodos_comunes::save_event_response_endpointsession(\context_system::instance(), $dataresponse);

		if ($response["status"] > 299) {
			throw new moodle_exception("error request:" . $response["status"] . " Endpoint: " . $endpoint);
		}
		$response = json_decode($response["data"], true);
		$attendanceid = null;

		$timestart = utils::date_to_timestamp($response['InicioCapacitacion']);
        $timestart = self::fix_invierno_2025($timestart);
		$timeend = utils::date_to_timestamp($response['TerminoCapacitacion']);
        $timeend = self::fix_invierno_2025($timeend);
		$timesecond = $timeend - $timestart;

		// Crear o actualizar el curso para la sesion
		//$course = external_api::call_external_function("local_pubsub_upsert_course", ["ID" => $params["IdCurso"]]);

        $sql = "SELECT 
            c.*
        FROM {curso_back} cb
        JOIN {course} c 
             ON c.id = cb.id_curso_moodle
        WHERE cb.productoid = :productoid";

        $sql_params = ['productoid' => $idcurso];
        $course = $DB->get_record_sql($sql, $sql_params);
        if( !$course ) {
            throw new moodle_exception("Curso no encontrado en Moodle para el ID externo: " . $idcurso);
        }

		
		$role = get_config("eabcattendance", "rolwscreateactivity");
		$roleid = (!empty($role)) ? $role : 3;
		//$course = $DB->get_record('course', array('id' => $course->id));

		$coursecontext = \context_course::instance($course->id);

		$guidevento = explode("-", $idevento);
		$name_group = date("d-m-Y H:i", $timestart) . " " . $guidevento[0];

		// Primero, buscar si el grupo ya existe por nombre en el curso.
		$groupid = $DB->get_field('groups', 'id', ['courseid' => $course->id, 'name' => $name_group]);
		$group_from_pivot = null;

		if (!$groupid) {
			// Si no se encuentra por nombre, se busca en la tabla pivote.
			$groups_records = $DB->get_records('eabcattendance_course_groups', array('uuid' => $params["IdEvento"], 'curso' => $course->id));
			$group_from_pivot = end($groups_records);

			if (empty($group_from_pivot)) {
				// Si tampoco está en la tabla pivote, se crea el grupo.
				$creategropup = array("createname" => $name_group);
				$groupid = metodos_comunes::create_group($creategropup, $course);
				if (empty($groupid)) {
					throw new moodle_exception("Error al crear grupo");
				}
			} else {
				$groupid = $group_from_pivot->grupo;
				// Se encontró una referencia en la tabla pivote, ahora hay que validar que el grupo realmente exista.
				if (!$DB->record_exists('groups', array('id' => $groupid))) {
					// El grupo no existe en la tabla de grupos, así que lo creamos.
					$creategropup = array("createname" => $name_group);
					$groupid = metodos_comunes::create_group($creategropup, $course);
					if (empty($groupid)) {
						throw new moodle_exception("Error al crear grupo");
					}
				}
			}
		}

		// Si encontramos el grupo (especialmente desde la tabla pivote), verificamos si el nombre necesita actualizarse.
		if ($groupid) {
			$get_grupo = $DB->get_record("groups", array("id" => $groupid));
			if ($get_grupo && $get_grupo->name !== $name_group) {
				$record = new \stdClass();
				$record->id = $get_grupo->id;
				$record->courseid = $course->id;
				$record->name = $name_group;
				groups_update_group($record);
			}
		}

		// Asegurarse de que la asociación exista en la tabla pivote.
		if ($groupid && !$DB->record_exists('eabcattendance_course_groups', array('grupo' => $groupid, 'uuid' => $params["IdEvento"]))) {
			metodos_comunes::eabcattendance_course_groups($groupid, $course->id, $params["IdEvento"]);
		}

		// crear relator
        /**
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
**/
		$groups = $DB->get_record('eabcattendance_course_groups', array('curso' => $course->id, 'uuid' => $params["IdEvento"]));

		// Obtener sesion
		$get_session = $DB->get_record('eabcattendance_sessions', array('guid' => $params['Id']));

        if ($get_session) {
            $attendance = $DB->get_record('eabcattendance', ['id' => $get_session->eabcattendanceid]);
            $cm = get_coursemodule_from_instance('eabcattendance', $attendance->id, 0, false, MUST_EXIST);
        } else {
            // if session does not exist, find an attendance activity
            $get_attendances_activities = $DB->get_records('eabcattendance', array('course' => $course->id));
            if (empty($get_attendances_activities)) {
                // same error as create_sesion
				$other = array(
					'error' => get_string('coursenotattendance', 'local_pubsub'),
					'guid' => $params["Id"],
				);
				metodos_comunes::save_event_sessions(\context_course::instance($course->id), $other, $course->id);
				throw new moodle_exception("coursenotattendance", "local_pubsub");
            }
            // just pick the first one
            $attendance = reset($get_attendances_activities);
            $cm = get_coursemodule_from_instance('eabcattendance', $attendance->id, 0, false, MUST_EXIST);
        }

        $sesionid = metodos_comunes::create_or_update_session($attendance, $cm, $course, $timestart, $timesecond, $groups, $params['Id']);

		//Actualiza tabla sesion_back con los datos de la session
		\local_pubsub\back\sesion::inser_update_sesion_back($response, $sesionid);

		return [
			"moodlesesionid" => $sesionid
		];
	}

    /**
     * Función que aplica el ajuste de invierno (+3600) para el año 2025.
     *
     * @param int $timestampBase El timestamp que generó 'utils' (asumiendo UTC-3).
     * @return int El timestamp UTC corregido.
     */
    public static function fix_invierno_2025($timestampBase) {

        // Inicio Invierno (6 Abr 2025 00:00 UTC-3)
        $inicioInviernoTS = 1743994800;

        // Inicio Verano (7 Sep 2025 00:00 UTC-3)
        $inicioVeranoTS = 1757290800;

        // Comprobamos si el timestamp cae DENTRO del período de invierno
        if ($timestampBase >= $inicioInviernoTS && $timestampBase < $inicioVeranoTS) {
            return $timestampBase + 3600;
        }

        return $timestampBase;
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

