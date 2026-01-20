<?php

namespace local_pubsub\back;
use moodle_exception;
use local_pubsub\utils;

defined('MOODLE_INTERNAL') || die;

class inscripcion_elearning{

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
        $log->participanteproductid = $response['ParticipanteProductId'];
        $log->participanteidregistroparticip = $response['ParticipanteIdRegistroParticipante'];
        $log->created_at        = $today;

        $DB->insert_record('inscripcion_elearning_log', $log);

        /** CAMPOS PERSONALIZADOS */
        $data = \local_mutual\front\utils::create_object_user_custon_field($record);
        $custom_field = \local_mutual\front\utils::insert_custom_fields_user($iduser_moodle, $data);
        if($custom_field["error"] != ""){            
            $event = \local_pubsub\event\inscripcion_elearning::create(
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

    /**
     * Obtener la fecha de matriculacion
     * @param $courseid
     * @param $userid
     * @return mixed
     * @throws dml_exception
     */
    public static function get_enrol_date($courseid, $userid) {
        // @codingStandardsIgnoreLine
        /** @var \moodle_database $DB */
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
     * @param $fecha_inicial
     * @param $fecha_final
     * @return float|int
     */
    public static function interval($fecha_inicial, $fecha_final) {

        if (empty($fecha_inicial)) {
            return 0;
        }

        if ($fecha_inicial > $fecha_final) {
            return 0;
        }
        $dias = ($fecha_final - $fecha_inicial) / 86400; //86400 0 1 día
        $days = floor($dias);
        return $days;
    }


    public static function clear_attemps_course_user($userid, $courseid) {
        // @codingStandardsIgnoreLine
        /** @var \moodle_database $DB */
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

    }

    public static function quiz_delete_user_attempts_user($quizid, $userid) {
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

    /*
    * Funcion que se ejecuta en taras programadas para el cierre de capacitacion elearning
    */
    public static function finalizar_elearning($last_execute, $days, $participante_condicion){
        // @codingStandardsIgnoreLine
        /** @var \moodle_database $DB */

        global $DB, $CFG, $PAGE;
        $PAGE->set_context(\context_system::instance());
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->libdir . '/grade/grade_grade.php');
        $today = time();
        //$enrolmentback = "select id,id_user_moodle, id_curso_moodle, participanteidregistroparticip from {inscripcion_elearning_back} where timereported = 0";
        $record = new \stdClass();
        $condicion = ($participante_condicion == 'culminados') ? '!ISNULL(cc.timecompleted)' : 'ISNULL(cc.timecompleted)';

        $enrolmentback = "select ie.id as id, ie.id_user_moodle as id_user_moodle, ie.id_curso_moodle as id_curso_moodle, ie.participanteidregistroparticip as participanteidregistroparticip 
                            from {inscripcion_elearning_back} ie
                            join {course_completions} cc on cc.userid = ie.id_user_moodle and cc.course = ie.id_curso_moodle
                            where ie.timereported = 0
                            and ie.createdat > '2025-09-01'
                            and ".$condicion. " order by id desc";

        $enrollments = $DB->get_records_sql($enrolmentback);
        
        foreach ($enrollments as $enrol) {

            // Se verifica que el timereported siga en 0 y se bloquea con 1 para que no lo tome otro proceso
            $query_elearning_back = $DB->get_record("inscripcion_elearning_back", array("id" => $enrol->id));
            if ($query_elearning_back->timereported==0){
                $record->id             = $enrol->id;
                $record->timereported   = 1;
                $DB->update_record('inscripcion_elearning_back', $record);
            
                //si esta registrado en la tabla mdl_inscripcion_elearning_back es un usuario creado por ws solicitud de inscripcion
                $sql = 'select ue.timecreated, ue.timestart from {user_enrolments} ue join {enrol} e on e.id = ue.enrolid where e.courseid = :id_curso_moodle and ue.userid = :id_user_moodle and e.enrol = "manual" limit 1';
                $params['id_user_moodle']  = $enrol->id_user_moodle;
                $params['id_curso_moodle'] = $enrol->id_curso_moodle;
                $inscrito_elearning = $DB->get_record_sql($sql, $params);
                //$inscrito_elearning = $DB->get_record_sql('inscripcion_elearning_log', array('id_user_moodle' => $enrol->userid, 'id_curso_moodle' => $enrol->courseid));

                if ($inscrito_elearning) {

                    $sql = 'SELECT * from {course_completions} WHERE userid = :id_user_moodle AND course = :id_curso_moodle';

                    $course_completion = $DB->get_record_sql($sql, $params);

                    if (!empty($course_completion) && !empty($course_completion->timecompleted)) {
                        $today = $course_completion->timecompleted;
                    }

		            $timestart = $inscrito_elearning->timecreated; 
                    if (!empty($inscrito_elearning->timestart)) {
                        $days_passed_enrol = inscripcion_elearning::interval($inscrito_elearning->timestart, $today);
			            $timestart = $inscrito_elearning->timestart;
                    } else {
                        $days_passed_enrol = inscripcion_elearning::interval($inscrito_elearning->timecreated, $today);
                    }


                    $params['timecompleted']  = $last_execute;                    
                    $gradeitemparamscourse = [
                        'itemtype' => 'course',
                        'courseid' => $enrol->id_curso_moodle,
                    ];


                    $gradevalue = $CFG->gradevalue;
                    $grade_course   = \grade_item::fetch($gradeitemparamscourse);
                    $grades_user    = \grade_grade::fetch_users_grades($grade_course, array($enrol->id_user_moodle), false);

                    $finalgradeuser = ($grades_user) ? $grades_user[key($grades_user)]->finalgrade : '';

                    $gradepassed = false;

                    if(!empty($finalgradeuser)) {
                        if((int)$finalgradeuser > $grade_course->gradepass) {
                            $gradepassed = true; 
                            $gradeuserobj = $grades_user[key($grades_user)];
                            $gradedate = !empty($gradeuserobj->timemodified)? $gradeuserobj->timemodified: $gradeuserobj->timecreated;
                            $days_passed_grade = inscripcion_elearning::interval($timestart, $gradedate);
                        } 
                    }

                    if ($days_passed_enrol <= (intval($days)) || ($gradevalue && $gradepassed)) {
                        //si termino el curso
			            if ((isset($course_completion->timecompleted) && $inscrito_elearning->timecreated < $course_completion->timecompleted)
			                || ( $days_passed_enrol > (intval($days)) && $gradepassed && $days_passed_grade <= (intval($days)))) {

                            //valido si el usuario tiene finalgradeuser para evaluar aparobado o desaprobado
                            if (!empty($finalgradeuser)) {
                                $nota = self::calc_nota($finalgradeuser, $grade_course->gradepass);
                                $asistencia = 100;
                                if (floatval($finalgradeuser) >= floatval($grade_course->gradepass)) {
                                    //Aprobado - Tiene course completions y grade del usuario supera grade pass course                              
                                    $observacionInscripcion = "Terminó todo y aprobó";
                                    $resultado = 1;
                                } else {
                                    //si termino todas las actividades, termino el quiz pero reprobó. - tiene course completions y grade del usuario no supera grade pass del curso
                                    $observacionInscripcion = "Reprobado por nota baja";
                                    $resultado = 2;
                                }
                                self::enviar_cierre_course_back($enrol->participanteidregistroparticip, $nota, $finalgradeuser, $resultado, $today, $observacionInscripcion, $asistencia, $enrol->id_user_moodle, $enrol->id_curso_moodle);
                            } else {
                                self::rolback_timereported($enrol->id);
                                echo "No procesó. ID inscripcion_elearning_back: ".$enrol->id ." usuarioid: " . $enrol->id_user_moodle . " en cursoid: " . $enrol->id_curso_moodle . " por: sin grades_user <br>";
                            }
                        }else{
                            $send_attemp = self::sendReprobeAttemptUser($enrol);
                            if($send_attemp == false) {
                                self::rolback_timereported($enrol->id);
                                echo "No procesó. ID inscripcion_elearning_back: ".$enrol->id ." usuarioid: " . $enrol->id_user_moodle . " en cursoid: " . $enrol->id_curso_moodle . " por: no tiene timecompleted O timecreated es menor a timecompleted <br>";
                            } else {
                                if (!empty($finalgradeuser)) {
                                    if (floatval($finalgradeuser) < floatval($grade_course->gradepass)) {
                                        $nota = self::calc_nota($finalgradeuser, $grade_course->gradepass);
                                        $asistencia = 100;
                                        //si termino todas las actividades, termino el quiz pero reprobó. - tiene course completions y grade del usuario no supera grade pass del curso
                                        $observacionInscripcion = "Reprobado por nota baja";
                                        $resultado = 2;
                                        self::enviar_cierre_course_back($enrol->participanteidregistroparticip, $nota, $finalgradeuser, $resultado, $today, $observacionInscripcion, $asistencia, $enrol->id_user_moodle, $enrol->id_curso_moodle);
                                    }
                                }
                            }
                        }
                    } else {
                        //validar si no nunca a entrado al curso tambien tiene registro en coursecompletion 
                        if (!$course_completion->timecompleted || !$course_completion) {
                            $grade_course   = \grade_item::fetch($gradeitemparamscourse);
                            $grades_user    = \grade_grade::fetch_users_grades($grade_course, array($enrol->id_user_moodle), false);
                            $finalgradeuser = $grades_user[key($grades_user)]->finalgrade;
                            $nota = self::calc_nota($finalgradeuser, $grade_course->gradepass);
                            $resultado = 2;
                            $asistencia = 0;
                            $observacionInscripcion = "Reprobado por inasistencia";
                            self::enviar_cierre_course_back($enrol->participanteidregistroparticip, $nota, $finalgradeuser, $resultado, $today, $observacionInscripcion, $asistencia, $enrol->id_user_moodle, $enrol->id_curso_moodle);
                        }else{
                            self::rolback_timereported($enrol->id);
                            echo "No procesó. ID inscripcion_elearning_back: ".$enrol->id ." usuarioid: " . $enrol->id_user_moodle . " en cursoid: " . $enrol->id_curso_moodle . " por: (!course_completion->timecompleted || !course_completion) <br>";
                        }
                    }
                }else{
                    self::rolback_timereported($enrol->id);
                    echo "No procesó. ID inscripcion_elearning_back: ".$enrol->id ." usuarioid: " . $enrol->id_user_moodle . " en cursoid: " . $enrol->id_curso_moodle . " por: No se encontró en la table user_enrolments <br>";                    
                }
            }
        }
    }

    public static function sendReprobeAttemptUser($enrol){

        global $DB, $CFG;

        // aceptar opcionalmente userid y courseid como argumentos
        $userid = !empty($enrol->id_user_moodle) ? (int)$enrol->id_user_moodle : null;
        $courseid = !empty($enrol->id_curso_moodle) ? (int)$enrol->id_curso_moodle : null;

        if (empty($userid) || empty($courseid)) {
            // no hay datos suficientes para evaluar
            return false;
        }

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $modinfo = get_fast_modinfo($courseid);
        $cms = $modinfo->get_instances_of('quiz');

        $totalquizzes = 0;   // quizzes visibles con límite de intentos (>0)
        $exhausted = 0;      // quizzes en los que el usuario ya agoto los intentos

        foreach ($cms as $cm) {
            if ($cm->modname !== 'quiz' || !$cm->visible) {
                continue;
            }

            $quiz = $DB->get_record('quiz', ['id' => $cm->instance], 'id,attempts');
            if (!$quiz) {
                continue;
            }

            $maxattempts = (int)$quiz->attempts; // 0 = ilimitado
            if ($maxattempts <= 0) {
                // no se cuentan quizzes con intentos ilimitados
                continue;
            }

            $totalquizzes++;

            // contar intentos reales del usuario (preview = 0)
            $attemptsdone = $DB->count_records_select(
                'quiz_attempts',
                'quiz = ? AND userid = ? AND preview = 0',
                [$quiz->id, $userid]
            );

            if ($attemptsdone >= $maxattempts) {
                $exhausted++;
            }
        }

        // Si la cantidad de quizzes con límite es igual a la cantidad de quizzes agotados,
        // entonces el usuario ya realizó todos sus intentos configurados.
        if ($totalquizzes > 0 && $exhausted === $totalquizzes) {
            return true;
        }

        return false;

    }

        /**
     * Calcular nota
     */
    public static function calc_nota($nota, $gradepass)
    {
        if ($nota < $gradepass) {
            return ((($nota - 1) * 3) / 74) + 1;
        } else {
            return (($nota - $gradepass) * 0.12) + 4;
        }
    }

    public static function enviar_cierre_course_back($inscrito_elearning, $finalgradeuser, $porcentaje, $resultado, $today, $observacionInscripcion, $asistencia, $userid_moodle, $cursoid_moodle, $task = true)
    {
        global $DB;

        $hoy = utils::date_utc();
        $finalgradeuser = round($finalgradeuser);
        $porcentaje     = round($porcentaje);
        $finalgradeuser = ($finalgradeuser < 1) ? 1 : $finalgradeuser;

        $data = [
            "IdRegistroParticipante" => $inscrito_elearning,
            "NotaEvaluacion" => floatval($finalgradeuser),
            "NotaEvaluacionPorcentaje" => floatval($porcentaje),
            "Asistencia" => $asistencia,
            "Resultado" => $resultado,
            "FechaTemrinoResultado" => $hoy,
            "Observacion" => $observacionInscripcion
        ];

        if($task == true){
            echo "Datos que se envían al servicio: <br>";
            var_dump($data);
        }

        // Se envia el resultado al Cierre capacitación e-learning Back
        $endpoint = get_config('local_pubsub', 'endpointcierreparticipantes');

        if (empty($endpoint)) {
            throw new moodle_exception("Debe configurar el endpoint de Cierre capacitación e-learning ");
        }
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, $endpoint);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
            'Authorization: ' . get_config('local_pubsub', 'tokenapi'),
            'Ocp-Apim-Subscription-Key: ' . get_config('local_pubsub', 'subscriptionkey'),
            'Content-Type:application/json'
        ));
        $response = curl_exec($cURLConnection);
        $httpcode = curl_getinfo($cURLConnection, CURLINFO_HTTP_CODE);
        curl_close($cURLConnection);
    
        if ($httpcode > 299) {
            $event = \local_pubsub\event\cierre_capacitacion_elearning::create(
                array(
                    'context' => \context_system::instance(),
                    'other' => array(
                        'error' => 'Error en respuesta de webservices back, response: ' . $response,
                        'IdRegistroParticipante' => $inscrito_elearning
                    ),
                )
            );
            $event->trigger();

            $error = "No proceso por: ".'Error en respuesta de webservices back, response: ' . $response. 'IdRegistroParticipante'. $inscrito_elearning;
            var_dump($error);
            // SI falla el cierre, se verifica que el timereported se haya marcado a 1, y se devuelve a 0
            $query_elearning_back = $DB->get_record("inscripcion_elearning_back", array("participanteidregistroparticip" => $inscrito_elearning));
            if ($query_elearning_back->timereported==1){
                self::rolback_timereported($query_elearning_back->id);                
            }
        } else {

            $datos_log = [
                'id_user_moodle'    => $userid_moodle,
                'id_curso_moodle'   => $cursoid_moodle,
                "id_registro_participante" => $inscrito_elearning,
                "nota_evaluacion"   => floatval($finalgradeuser),
                "nota_evaluacion_porcentaje" => floatval($porcentaje),
                "asistencia"        => $asistencia,
                "resultado"         => $resultado,
                "fecha_temrino_resultado" => $hoy,
                "observacion"       => $observacionInscripcion,
                "createdat"         => time()
            ];
            $DB->insert_record('cierre_elearning_back_log', $datos_log);
            $DB->set_field('inscripcion_elearning_back', 'timereported', time(), array('participanteidregistroparticip' => $inscrito_elearning));
            $event = \local_pubsub\event\cierre_capacitacion_elearning::create(
                array(
                    'context' => \context_system::instance(),
                    'other' => array(
                        'response' => 'Cierre de capacitación con nota: ' . $finalgradeuser . ' Resultado: ' . $resultado . 'Obervación: ' . $observacionInscripcion,
                        'IdRegistroParticipante' => $inscrito_elearning
                    ),
                )
            );
            $event->trigger();
        }

        return $response;
    }

    public static function rolback_timereported($id){
        global $DB;

        $record = new \stdClass();
        $record->id             = $id;
        $record->timereported   = 0;
        $DB->update_record('inscripcion_elearning_back', $record);
    }
}