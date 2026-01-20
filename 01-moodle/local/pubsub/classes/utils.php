<?php

namespace local_pubsub;

global $CFG;

use Exception;

require_once($CFG->dirroot . '/user/profile/lib.php');

class utils {
    /* Funcion para agregar las horas de diferencia (3 o 4) dependiendo del horario actual Chile
     * str: fecha recibida     
     * fecha_ini: obtiene el primer sabado del mes de abril de cada año (Comienzo horario invierno)
     * fecha_fin: se calculan 5 meses a partir de la fecha_ini (Duración de horario invierno)
     * Si la fecha recibida (str) está dentro del rango, 
     * se suman 4horas a la fecha formateada, sino (horario verano) se suman 3 horas
     * @param $str
     * @return false|float|int
     */
    public static function date_to_timestamp($str){
        $fecha_ini = date('Y-m-d', strtotime('first saturday of april '.date('Y')));
        $fecha_fin = date('Y-m-d', strtotime("+5 months", strtotime($fecha_ini)));
        $hrs = (($str >= $fecha_ini) && ($str <= $fecha_fin)) ? 4 : 3;        

        return strtotime($str) + (60 * 60 * $hrs);
    }

    public static function date_utc($timestamp = null){                
        $timezone = "UTC";
        date_default_timezone_set($timezone);  
        
        if (!$timestamp){
            $timestamp = time()+date("Z");
        }
        
        return gmdate("Y-m-d H:i",$timestamp);
    }

    public static function get_company_name($user) {
        /** @var \moodle_database $DB */
        global $DB;
        if(!is_object($user)) {
            $user = $DB->get_record('user', array('id' => $user));
        }
        $company_user = $DB->get_records('company_users', array('userid' => $user->id));
        if(!empty($company_user)) {
            $company = $DB->get_record('company', array('id' => end($company_user)->companyid)); 
            if(!empty($company)) {
                return $company->name;
            }
        } 
        
        $extrafield = profile_user_record($user->id);
        if(!empty($extrafield->empresarazonsocial)) {
            return $extrafield->empresarazonsocial; 
        }

        $datosEmpresa = self::get_empresa_nominativo($user->username);
        
        if (isset($datosEmpresa->razon_social)){
            $array_aditional_files = array(
                "empresarut"        => (string) $datosEmpresa->rut,
                "empresarazonsocial"=> (string) $datosEmpresa->razon_social,
                "empresacontrato"   => (string) $datosEmpresa->contrato
            );
            profile_save_custom_fields($user->id, $array_aditional_files);

            return $datosEmpresa->razon_social;
        }

        return 'MUTUAL DE SEGURIDAD C.CH.C.';
    }

    /**
     * Funcion para guardar en un .txt el log en el llamado desde cualquien función
     * que se desee debuguear, recibe cualquier cantidad de argumentos de cualquier tipo
     * Recomendable pasar como primer parametro un string que identifique en que funcion 
     * se está haciendo el llamado de la misma.
     */
    public static function save_arguments_log(){
        global $CFG;
        $param  = 0;
        $salida = '';
        $logFile = fopen($CFG->dataroot."/pubsub/logs/log_mutual.txt", 'a'); 
        foreach(func_get_args() as $arg){
            $salida .= " Param".++$param."=> ".print_r($arg, true)." |";
        }
        $now = \DateTime::createFromFormat('U.u', microtime(true));
        fwrite($logFile, "\n".$now->format("d/m/Y H:i:s.u")." -->". $salida);
        fclose($logFile);
    }

    /**
     * Funcion para obtener los datos de la empresa del servicio de persona
     */
    public static function get_empresa_nominativo($rut){
        $dataempresa = new \stdClass();
        $datosNominativo = \local_mutual\back\utils::get_personas_nominativo($rut, 1);
        if ($datosNominativo->return->error == 0){
            foreach($datosNominativo->return->empresas as $empresa){
                if($empresa->activo == 1){ 
                    $dataempresa->rut           = (string) $empresa->rut."-".$empresa->dv;
                    $dataempresa->contrato      = (string) $empresa->contrato;
                    $dataempresa->razon_social  = (string) $empresa->razonSocial;
                    break;
                }
            }
        }
        return $dataempresa;
    }
    public static function update_sesion_migrate_logic($id, $idcurso, $idevento, $action) {
        global $CFG;
        require_once($CFG->libdir . '/externallib.php');


        global $DB;
        // 🔹 Cargar configuraciones una sola vez (reduce consultas)
        $config = (object)[
            'approvedstatus'   => get_config('local_pubsub', 'approvedstatus'),
            'suspendedstatus'  => get_config('local_pubsub', 'suspendedstatus'),
            'endpointsession'  => get_config('local_pubsub', 'endpointsession'),
            'rolwscreateactivity' => get_config('eabcattendance', 'rolwscreateactivity') ?: 3
        ];

        // 🔹 Consultar endpoint remoto
        $endpoint = $config->endpointsession . $id;
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

        // 🔹 Buscar curso en Moodle (ajustado igual que el WS: se añade la comprobación de shortname)
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
                'uuid' => $idevento,
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

        // 🔹 Asegurar existencia en la tabla pivote (cambiado para igualar comportamiento del WS)
        if ($groupid) {
            \local_pubsub\metodos_comunes::eabcattendance_course_groups($groupid, $course->id, $idevento);
        } else {
            throw new \moodle_exception("Error al obtener el ID de grupo en el curso: " . $course->id);
        }

        // 🔹 Buscar la relación grupo-curso-uuid
        $groups = $DB->get_record('eabcattendance_course_groups', [
            'curso' => $course->id,
            'uuid' => $idevento
        ]);

        if (empty($groups) || !$DB->record_exists('groups', ['id' => $groups->grupo])) {
            throw new \moodle_exception("No se pudo encontrar o crear una referencia de grupo válida para el evento UUID: " . $idevento);
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
                    'guid' => "Guid de sesión: " . $id
                ];
                \local_pubsub\metodos_comunes::save_event_sessions(\context_course::instance($course->id), $other, $course->id);
            }
        }



        // 🔹 Buscar o crear sesión de asistencia
        $get_session = $DB->get_record('eabcattendance_sessions', ['guid' => $id]);

        if ($get_session) {
            $attendance = $DB->get_record('eabcattendance', ['id' => $get_session->eabcattendanceid]);
            $cm = get_coursemodule_from_instance('eabcattendance', $attendance->id, 0, false, MUST_EXIST);
        } else {
            // Buscar actividad de asistencia
            $get_attendances_activities = $DB->get_records('eabcattendance', ['course' => $course->id]);

            if (empty($get_attendances_activities)) {
                $other = [
                    'error' => get_string('coursenotattendance', 'local_pubsub'),
                    'guid' => $id
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
            $id
        );

        $participantes = \local_pubsub\metodos_comunes::get_participantes_sesion($id);

        global $CFG;
        require_once $CFG->libdir.'/enrollib.php';
        $enrolplugin = enrol_get_plugin('manual');


        // ============================================
        // 🔹 OPTIMIZACIÓN: Cargar todos los usuarios de una sola vez
        // ============================================

        // Normalizar identificadores y preparar mapeo
        $usernames = [];
        foreach ($participantes as $p) {
            $rut = \core_user::clean_field(strtolower(trim($p['ParticipanteIdentificador'])), 'username');
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
    public static function update_sesion_migrate_logic_old($id, $idcurso, $idevento, $action) {
        global $CFG;
        require_once($CFG->libdir . '/externallib.php');

        global $DB;
        // 🔹 Cargar configuraciones una sola vez (reduce consultas)
        $config = (object)[
            'approvedstatus'   => get_config('local_pubsub', 'approvedstatus'),
            'suspendedstatus'  => get_config('local_pubsub', 'suspendedstatus'),
            'endpointsession'  => get_config('local_pubsub', 'endpointsession'),
            'rolwscreateactivity' => get_config('eabcattendance', 'rolwscreateactivity') ?: 3
        ];

        // 🔹 Consultar endpoint remoto
        $endpoint = $config->endpointsession . $id;
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
                'uuid' => $idevento,
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
        if ($groupid && !$DB->record_exists('eabcattendance_course_groups', [
                'grupo' => $groupid,
                'uuid' => $idevento
            ])) {
            \local_pubsub\metodos_comunes::eabcattendance_course_groups($groupid, $course->id, $idevento);
        }

        // 🔹 Buscar la relación grupo-curso-uuid
        $groups = $DB->get_record('eabcattendance_course_groups', [
            'curso' => $course->id,
            'uuid' => $idevento
        ]);

        if (empty($groups) || !$DB->record_exists('groups', ['id' => $groups->grupo])) {
            throw new \moodle_exception("No se pudo encontrar o crear una referencia de grupo válida para el evento UUID: " . $idevento);
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
                    'guid' => "Guid de sesión: " . $id
                ];
                \local_pubsub\metodos_comunes::save_event_sessions(\context_course::instance($course->id), $other, $course->id);
            }
        }



        // 🔹 Buscar o crear sesión de asistencia
        $get_session = $DB->get_record('eabcattendance_sessions', ['guid' => $id]);

        if ($get_session) {
            $attendance = $DB->get_record('eabcattendance', ['id' => $get_session->eabcattendanceid]);
            $cm = get_coursemodule_from_instance('eabcattendance', $attendance->id, 0, false, MUST_EXIST);
        } else {
            // Buscar actividad de asistencia
            $get_attendances_activities = $DB->get_records('eabcattendance', ['course' => $course->id]);

            if (empty($get_attendances_activities)) {
                $other = [
                    'error' => get_string('coursenotattendance', 'local_pubsub'),
                    'guid' => $id
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
            $id
        );

        $participantes = \local_pubsub\metodos_comunes::get_participantes_sesion($id);

        global $CFG;
        require_once $CFG->libdir.'/enrollib.php';
        $enrolplugin = enrol_get_plugin('manual');


        // ============================================
        // 🔹 OPTIMIZACIÓN: Cargar todos los usuarios de una sola vez
        // ============================================

        // Normalizar identificadores y preparar mapeo
        $usernames = [];
        foreach ($participantes as $p) {
            $rut = \core_user::clean_field(strtolower(trim($p['ParticipanteIdentificador'])), 'username');
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
}
