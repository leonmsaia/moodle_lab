<?php

namespace local_mutualnotifications;

class utils
{

    public $days;

    public function __construct()
    {
        global $CFG;
        $this->days = $CFG->local_mutualnotifications_available_days;
    }

    public function getDays()
    {
        return $this->days;
    }

    public function setDays($days)
    {
        $this->days = $days;
    }

    public static function course_welcome($userid, $courseid, $enrol_passport = false, $password_generate = "")
    {
        global $DB, $USER, $PAGE, $CFG;

        $userto = $DB->get_record('user', array('id' => $userid));
        $course = $DB->get_record('course', array('id' => $courseid));

        $log_notification = $DB->get_record('mutual_log_notifications', array('notification' => 'enrolment' . $course->id, 'userid' => $userto->id, 'courseid' => $course->id));

        if (empty($log_notification)) {
            $enrolment_course = self::get_enrol_date($courseid, $userto->id);
            $date_from_enrolment_course = $enrolment_course->dateroltime;
            $end_course_date = (($CFG->local_mutualnotifications_available_days * DAYSECS) + $date_from_enrolment_course);
            $from_date = date("d-m-Y", $date_from_enrolment_course);
            $to_date = date("d-m-Y", $end_course_date);

            $current_date = date_create();
            $today = date_timestamp_get($current_date);
            $message_lang = new \stdClass();
            $message_lang->user = $userto->firstname . ' ' . $userto->lastname;
            $message_lang->username = $userto->username;
            $message_lang->urlsite = $CFG->wwwroot;
            $message_lang->course = $course->fullname;
            $message_lang->imglogo = $CFG->wwwroot . '/local/mutualnotifications/pix/logo_mutual.jpg';
            $message_lang->imgfirma = $CFG->wwwroot . '/local/mutualnotifications/pix/firma_mutual.jpg';
            $message_lang->fromdate = $from_date;
            $message_lang->todate = $to_date;
            $message_lang->hora = '';
            $message_lang->direccion = '';
            if (!empty($password_generate)) {
                $message_lang->password = $password_generate;
            } else {
                $message_lang->password = $userto->username;
                if ($enrol_passport) {
                    $message_lang->password = $userto->username . '-';
                }
            }



            if (\local_mutual\front\utils::is_course_elearning($course->id)) {
                $message_lang->tipo_curso = 'Elearning';
                $messagehtml = get_string('messagehtmlenrolment', 'local_mutualnotifications', $message_lang);
            } else {
                $sesion = \local_mutual\front\utils::get_first_session_user($course->id, $userto->id);
                if (!empty($sesion)) {
                    $message_lang->todate = $sesion->finicio;
                    $message_lang->hora = $sesion->hora;
                    $message_lang->direccion = $sesion->direccion;
                }

                if (\local_mutual\front\utils::is_course_presencial($course->id)) {
                    $message_lang->tipo_curso = 'Presencial';
                    $string = 'messagehtmlpresencial';
                } else { // Si no es Elearning ni Presencial es Streaming
                    $message_lang->tipo_curso = 'Streaming';
                    $string = 'messagehtmlstreaming';
                }
                $messagehtml = get_string($string, 'local_mutualnotifications', $message_lang);
            }

            $subject = get_string('subjectenrolment', 'local_mutualnotifications', $message_lang);

            $PAGE->set_context(\context_system::instance());

            $from = \core_user::get_noreply_user();
            $messageid = self::send_message($from, $userto, $subject, $messagehtml, $courseid);
            if ($messageid) {
                try {
                    $transaction = $DB->start_delegated_transaction();
                    self::save_notification('enrolment', $course, $userto, $today, $DB);
                    $transaction->allow_commit();
                } catch (Exception $e) {
                    error_log($e);
                    $transaction->rollback($e);
                }
            }
        }
    }

    public static function course_welcome_streaming_presencial($userid, $course, $sesion, $enrol_passport = false, $password_generate = "")
    {
        global $DB, $PAGE, $CFG;
        $sesion_back = $DB->get_record('sesion_back', array('id_sesion_moodle' => $sesion->id));
        $fechacapacitacion = explode("T", $sesion_back->iniciocapacitacion);
        $horaex = explode(":", $fechacapacitacion[1]);

        $userto = $DB->get_record('user', array('id' => $userid));
        $current_date = date_create();
        $today = date_timestamp_get($current_date);
        $message_lang = new \stdClass();
        $message_lang->user = $userto->firstname . ' ' . $userto->lastname;
        $message_lang->username = $userto->username;
        $message_lang->urlsite = $CFG->wwwroot;
        $message_lang->course = $course->fullname;
        $message_lang->imglogo = $CFG->wwwroot . '/local/mutualnotifications/pix/logo_mutual.jpg';
        $message_lang->imgfirma = $CFG->wwwroot . '/local/mutualnotifications/pix/firma_mutual.jpg';
        $message_lang->todate = date("d-m-Y", strtotime($fechacapacitacion[0]));
        $message_lang->hora = $horaex[0] . ":" . $horaex[1];
        $message_lang->direccion = $sesion->direction;

        if (!empty($password_generate)) {
            $message_lang->password = $password_generate;
        } else {
            $message_lang->password = $userto->username;
            if ($enrol_passport) {
                $message_lang->password = $userto->username . '-';
            }
        }

        if (\local_mutual\front\utils::is_course_presencial($course->id)) {
            $message_lang->tipo_curso = 'Presencial';
            $string = 'messagehtmlpresencial';
        } else { // Si no es Presencial es Streaming
            $message_lang->tipo_curso = 'Streaming';
            $string = 'messagehtmlstreaming';
        }
        $messagehtml = get_string($string, 'local_mutualnotifications', $message_lang);
        $subject = get_string('subjectenrolment', 'local_mutualnotifications', $message_lang);
        $PAGE->set_context(\context_system::instance());

        $from = \core_user::get_noreply_user();
        $messageid = self::send_message($from, $userto, $subject, $messagehtml, $course->id);


        if ($messageid) {
            try {
                $transaction = $DB->start_delegated_transaction();
                self::save_notification('enrolment', $course, $userto, $today, $DB);
                $transaction->allow_commit();
            } catch (Exception $e) {
                error_log($e);
                $transaction->rollback($e);
            }
        }
    }

    /**
     * Observer
     * Permite enviar una notificación de inscripción a un curso
     * @param type $event
     */

    //SE COMENTA LA FUNCION POR TEMAS DE ENVIO ANTES DE CALCULAR LA SESION
    public static function enrole_observer($event)
    {
        global $DB, $USER, $PAGE, $CFG;
        $data = $event->get_data(); //relateduserid, courseid
        $userto = $DB->get_record('user', array('id' => $data['relateduserid'])); //core_user::get_user($userid);
        $course = $DB->get_record('course', array('id' => $data['courseid']));
        /* $enrolment = get_config('local_mutualnotifications', 'enrolment' . $course->id);
        // Se quita esta validación y se deja ejecutando para cualquier enrol
        if ($enrolment) { */
        /* 
            $log_notification = $DB->get_record('mutual_log_notifications', array('notification' => 'enrolment' . $course->id, 'userid' => $userto->id, 'courseid' => $course->id));

            if (empty($log_notification)) {
                $enrolment_course = self::get_enrol_date($data['courseid'], $userto->id);
                $date_from_enrolment_course = $enrolment_course->dateroltime;
                $end_course_date = (($CFG->local_mutualnotifications_available_days * DAYSECS) + $date_from_enrolment_course);
                $from_date = date("d-m-Y", $date_from_enrolment_course);
                $to_date = date("d-m-Y", $end_course_date);

                $current_date = date_create();
                $today = date_timestamp_get($current_date);
                $message_lang = new \stdClass();
                $message_lang->user = $userto->firstname . ' ' . $userto->lastname;
                $message_lang->username = $userto->username;
                $message_lang->urlsite = $CFG->wwwroot;
                $message_lang->course = $course->fullname;
                $message_lang->imglogo = $CFG->wwwroot . '/local/mutualnotifications/pix/logo_mutual.jpg';
                $message_lang->imgfirma = $CFG->wwwroot . '/local/mutualnotifications/pix/firma_mutual.jpg';
                $message_lang->fromdate = $from_date;
                $message_lang->todate = $to_date;
                $message_lang->hora         = '';
                $message_lang->direccion    = '';

                if (\local_mutual\front\utils::is_course_elearning($course->id)) {
                    $message_lang->tipo_curso = 'Elearning';
                    $messagehtml = get_string('messagehtmlenrolment', 'local_mutualnotifications', $message_lang);
                }else{
                    $sesion = \local_mutual\front\utils::get_first_session_user($course->id,$userto->id);
                    if (!empty($sesion)){
                        $message_lang->todate    = $sesion->finicio;
                        $message_lang->hora      = $sesion->hora;
                        $message_lang->direccion = $sesion->direccion;
                    }

                    if (\local_mutual\front\utils::is_course_presencial($course->id) ){
                        $message_lang->tipo_curso = 'Presencial';
                        $string = 'messagehtmlpresencial';
                    }
                    else{ // Si no es Elearning ni Presencial es Streaming
                        $message_lang->tipo_curso = 'Streaming';
                        $string = 'messagehtmlstreaming';
                    }
                    $messagehtml = get_string($string, 'local_mutualnotifications', $message_lang);
                }
                
                $subject = get_string('subjectenrolment', 'local_mutualnotifications', $message_lang);

                $PAGE->set_context(\context_system::instance());
                //error_log($subject);
                //error_log($messagehtml);
                $from = \core_user::get_noreply_user();
                $messageid = self::send_message($from, $userto, $subject, $messagehtml, $data['courseid']);
                if ($messageid) {
                    try {
                        $transaction = $DB->start_delegated_transaction();
                        self::save_notification('enrolment', $course, $userto, $today, $DB);
                        $transaction->allow_commit();
                    } catch (Exception $e) {
                        error_log($e);
                        $transaction->rollback($e);
                    }
                }                 
            }       */
    }


    /**
     * Permite enviar a los usuarios una notificación por correo de avance de curso desde la fecha de inicio
     * @param type $course
     * @param type $userfrom
     * @param type $percent_str
     * @param type $percentfrom
     * @param type $percentto
     * @param type $PAGE
     * @param type $today
     * @param type $DB
     */
    public function course_advance_from_start($course, $userfrom, $percent_str, $percentfrom, $percentto, $PAGE, $today, $DB)
    {

        $start_course_date = $course->startdate;
        $end_course_date = $course->enddate;

        //Intervalo de tiempo del curso
        $days_interval_course = $this->interval($start_course_date, $end_course_date);
        if ($days_interval_course) {
            //Dias transcurridos desde el dia de inicio del curso
            $days_passed = $this->interval($start_course_date, $today);
            //porcentajes de avance
            $days_percentfrom = $days_interval_course * $percentfrom / 100;
            $days_percentto = $days_interval_course * $percentto / 100;

            $users = get_enrolled_users(\context_course::instance($course->id));
            foreach ($users as $user) {
                $course_completion = $DB->get_record('course_completions', array('userid' => $user->id, 'course' => $course->id));
                if ($course_completion) {
                    if ($course_completion)
                        continue;
                }
                //si esta habilitado
                $percent_active = get_config('local_mutualnotifications', $percent_str . $course->id);
                if ($percent_active) {
                    if ($days_passed >= $days_percentfrom && $days_passed < $days_percentto) {
                        $log_notification = $DB->get_record('mutual_log_notifications', array('notification' => $percent_str . $course->id, 'userid' => $user->id, 'courseid' => $course->id));
                        if ($log_notification || !empty($log_notification))
                            continue;
                        else {
                            $PAGE->set_context(\context_system::instance());

                            $message_lang = new \stdClass();
                            $message_lang->user = $user->firstname;
                            $message_lang->course = $course->fullname;
                            $message_lang->percent = $percentfrom;

                            $messagehtml = get_string('messagehtml', 'local_mutualnotifications', $message_lang);
                            $subject = get_string('subject', 'local_mutualnotifications', $message_lang);
                            //error_log($messagehtml);

                            $from = \core_user::get_noreply_user();
                            $messageid = self::send_message($from, $user, $subject, $messagehtml, $course->id);

                            if ($messageid) {
                                self::save_notification($percent_str, $course, $user, $today, $DB);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Permite enviar a los usuarios una notificación por correo de avance de curso desde la fecha de matriculación
     * @param type $course
     * @param type $userfrom
     * @param type $percent_str
     * @param type $percentfrom
     * @param type $percentto
     * @param type $PAGE
     * @param type $today
     * @param type $DB
     */
    public function course_advance_from_enrole($course, $userfrom, $percent_str, $percentfrom, $percentto, $PAGE, $today, $DB)
    {
        global $CFG, $DB;
        $users = $DB->get_records_sql("SELECT
        u.*
        FROM {course}          AS course
        JOIN {enrol}           AS en     ON en.courseid = course.id
        JOIN {user_enrolments} AS ue     ON ue.enrolid  = en.id
        JOIN {user}            AS u  ON ue.userid   = u.id
        where ue.timecreated > 1704091414 and course.id = " . $course->id);

        foreach ($users as $user) {
            $enrolment_course = $this->get_enrol_date($course->id, $user->id);
            $date_from_enrolment_course = $enrolment_course->dateroltime;

            $days_passed = $this->interval($date_from_enrolment_course, $today);
            if ($this->finished($days_passed) || $this->completed($user, $course, $DB)) {
                $log_notification = $DB->get_record('mutual_log_notifications', array('notification' => $percent_str . $course->id, 'userid' => $user->id, 'courseid' => $course->id));
                if ($log_notification || !empty($log_notification)) {
                    $DB->delete_records('mutual_log_notifications', array('id' => $log_notification->id));
                    continue;
                }
                continue;
            }
            $days_percentfrom = $this->getDays() * $percentfrom / 100;
            $days_percentto = $this->getDays() * $percentto / 100;
            $percent_active = get_config('local_mutualnotifications', $percent_str . $course->id);

            if ($percent_active) {

                if ($days_passed >= $days_percentfrom && $days_passed < $days_percentto) {
                    $log_notification = $DB->get_record('mutual_log_notifications', array('notification' => $percent_str . $course->id, 'userid' => $user->id, 'courseid' => $course->id));

                    if ($log_notification || !empty($log_notification))
                        continue;
                    else {
                        $PAGE->set_context(\context_system::instance());

                        $message_lang = new \stdClass();
                        $message_lang->user = $user->firstname;
                        $message_lang->course = $course->fullname;
                        $message_lang->percent = $percentfrom;
                        $message_lang->last_days = ($percentfrom == 50) ? 15 : 3;
                        $message_lang->days = $this->getDays();
                        $message_lang->urlsite = $CFG->wwwroot;
                        $message_lang->imglogo = $CFG->wwwroot . '/local/mutualnotifications/pix/logo_mutual.jpg';
                        $message_lang->imgfirma = $CFG->wwwroot . '/local/mutualnotifications/pix/firma_mutual.jpg';

                        $messagehtml = get_string('messagehtml_advancefromenrole', 'local_mutualnotifications', $message_lang);
                        $subject = get_string('subject_advancefromenrole', 'local_mutualnotifications', $message_lang);

                        $from = \core_user::get_noreply_user();
                        $messageid = self::send_message($from, $user, $subject, $messagehtml, $course->id);

                        if ($messageid) {
                            self::save_notification($percent_str, $course, $user, $today, $DB);
                        }
                    }
                }
            }

        }
    }

    /**
     * Tarea programada
     * Permite enviar una notificación de curso completado *** DESACTIVADA 13/01/2021 ***
     * @param type $event
     */
    public static function coursecompleted($last_run_time)
    {

        global $DB, $USER, $PAGE, $CFG;


        $days_availables = $CFG->local_mutualnotifications_available_days;
        $current_date = date_create();
        $today = date_timestamp_get($current_date);

        $lasts_completed = self::get_lasts_completed_since_last_run_time($last_run_time);
        if ($lasts_completed) {

            foreach ($lasts_completed as $last) {
                $date_from_enrolment_course = self::get_enrol_date($last->course, $last->userid);
                $days_passed = self::interval($date_from_enrolment_course->dateroltime, $today);

                if ($days_passed <= $days_availables) {
                    $completion_date = $last->timecompleted;
                    if ($completion_date) {
                        $log_notification = $DB->get_record('mutual_log_notifications', array('notification' => 'coursecompletion' . $last->course, 'userid' => $last->userid, 'courseid' => $last->course));
                        if ($log_notification || !empty($log_notification))
                            continue;
                        else {
                            $course = $DB->get_record('course', ['id' => $last->course]);
                            $user = $DB->get_record('user', ['id' => $last->userid]);

                            $message_lang = new \stdClass();
                            $message_lang->user = ucfirst(fullname($user));
                            $message_lang->course = $course->fullname;
                            $message_lang->imglogo = $CFG->wwwroot . '/local/mutualnotifications/pix/logo_mutual.jpg';
                            $message_lang->imgfirma = $CFG->wwwroot . '/local/mutualnotifications/pix/firma_mutual.jpg';

                            $PAGE->set_context(\context_system::instance());

                            if (\local_mutual\front\utils::is_course_elearning($course->id)) {
                                $message_lang->tipo_curso = 'Elearning';
                            } else {
                                if (\local_mutual\front\utils::is_course_presencial($course->id)) {
                                    $message_lang->tipo_curso = 'Presencial';
                                } else { // Si no es Elearning ni Presencial es Streaming
                                    $message_lang->tipo_curso = 'Streaming';
                                }
                            }
                            $messagehtml = get_string('messagehtmlcoursecompletion', 'local_mutualnotifications', $message_lang);
                            $subject = get_string('subjectcoursecompletion', 'local_mutualnotifications', $message_lang);

                            /* @todo: ver que hacer con estos mensajes */
                            $from = \core_user::get_noreply_user();
                            $messageid = self::send_message($from, $user, $subject, $messagehtml, $course->id);

                            if ($messageid) {
                                self::save_notification('coursecompletion', $course, $user, $today, $DB);
                            }

                        }
                    }
                }
            }
        }

    }

    /**
     * Observer que permite eliminar un registro de la tabla mutual_log_notifications cuando se da de baja a un usuario de un curso
     * @global type $DB
     * @global type $USER
     * @global type $PAGE
     * @param type $event
     */
    public static function user_unenrolled($event)
    {
        global $DB;
        $data = $event->get_data(); //relateduserid, courseid
        $user = $DB->get_record('user', array('id' => $data['relateduserid'])); //core_user::get_user($userid);
        $course = $DB->get_record('course', array('id' => $data['courseid']));
        $enrolment = get_config('local_mutualnotifications', 'enrolment' . $course->id);
        if ($enrolment) {
            self::delete_log_notification('enrolment', $course, $user, $DB);
            self::delete_log_notification('fiftypercentfromenrolment', $course, $user, $DB);
            self::delete_log_notification('seventyfivepercentfromenrolment', $course, $user, $DB);
            self::delete_log_notification('coursecompletion', $course, $user, $DB);
        }

        $course_completion = $DB->get_record('course_completions', array('userid' => $user->id, 'course' => $course->id));
        if ($course_completion) {
            $DB->delete_records('course_completions', array('id' => $course_completion->id));
        }

        $courseid = $course->id;
        $userid = $user->id;
        $modinfo = get_fast_modinfo($courseid);
        $cms = $modinfo->get_cms();
        foreach ($cms as $cm) {
            error_log('CM: ' . $cm->id . ' user: ' . $userid);
            $log_cmc = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cm->id, 'userid' => $userid));
            if ($log_cmc) {
                error_log('Borrando de course_modules_completion' . $log_cmc->id);
                $DB->delete_records('course_modules_completion', array('id' => $log_cmc->id));
            }
        }
        $attemp = self::get_attemp_quiz($courseid, $userid, $DB);
        $log_qatt = $DB->get_record('quiz_attempts', array('id' => $attemp));
        if ($log_qatt) {
            error_log('Borrando de quiz_attempts' . $log_qatt->id);
            $DB->delete_records('quiz_attempts', array('id' => $log_qatt->id));
        }
    }

    /**
     * Permite enviar una notificaci{on por email a un usuario
     * @param type $userfrom
     * @param type $userto
     * @param type $subject
     * @param type $messagehtml
     * @param type $courseid
     * @return type
     */
    public static function send_message($userfrom, $userto, $subject, $messagehtml, $courseid)
    {
        $message = new \core\message\message();
        $message->component = 'moodle';
        $message->name = 'instantmessage';
        $message->userfrom = $userfrom;
        $message->userto = $userto;
        $message->subject = $subject ?? '(Sin asunto)';
        //$message->fullmessage = strip_tags($messagehtml ?? '(Sin contenido)');
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = $messagehtml ?? '<p>(Sin contenido)</p>';
        $message->smallmessage = $subject ?? '(Mensaje)';
        $message->courseid = $courseid;
        $messageid = \message_send($message);
        return $messageid;
    }

    /**
     * Permite guardar un registro en la tabla mutual_log_notifications
     * @param type $str
     * @param type $course
     * @param type $userto
     * @param type $today
     * @param \local_mutualnotifications\type $DB
     */
    public static function save_notification($str, $course, $user, $today, $DB)
    {
        $notification = new \stdClass();
        $notification->notification = $str . $course->id;
        $notification->userid = $user->id;
        $notification->courseid = $course->id;
        $notification->timemodified = $today;
        $DB->insert_record('mutual_log_notifications', $notification);
    }

    /**
     * Permite eliminar un registro de la tabla mutual_log_notifications cuando un usuario es dado de baja
     * @param type $str
     * @param type $course
     * @param type $user
     * @param \local_mutualnotifications\type $DB
     */
    public static function delete_log_notification($str, $course, $user, $DB)
    {
        $log_notification = $DB->get_record('mutual_log_notifications', array('notification' => $str . $course->id, 'userid' => $user->id, 'courseid' => $course->id));
        if ($log_notification) {
            $DB->delete_records('mutual_log_notifications', array('id' => $log_notification->id));
        }
    }

    /**
     * Permite obtener el número de dias entre dos fechas
     * @param type $fecha_inicial
     * @param type $fecha_final
     * @return int
     */
    public static function interval($fecha_inicial, $fecha_final)
    {
        if ($fecha_inicial > $fecha_final) {
            return 0;
        }
        $dias = ($fecha_final - $fecha_inicial) / 86400; //86400 0 1 día
        $days = floor($dias);
        return $days;
    }

    /**
     * Obtener la fecha de enrolamiento
     * @param type $courseid
     * @param type $userid
     */
    public static function get_enrol_date($courseid, $userid)
    {
        global $DB;
        $sql = '
                SELECT
                    ue.timecreated as dateroltime
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
        $result = $DB->get_records_sql($sql, array($courseid, $userid));
        return $result[key($result)];
    }

    /**
     * Permite validar si un usuario ha completado el curso
     * @param type $user
     * @param type $course
     * @param type $DB
     * @return int
     */
    public function completed($user, $course, $DB)
    {
        $course_completion = $DB->get_record('course_completions', array('userid' => $user->id, 'course' => $course->id));
        if ($course_completion) {
            if ($course_completion->timecompleted)
                return 1;
        }
        return 0;
    }

    /**
     * Permite validar si ya han pasado los n dias disponibles del curso
     * @param type $days_passed
     * @return int
     */
    public function finished($days_passed)
    {
        if ($days_passed >= $this->getDays())
            return 1;
        return 0;
    }

    /**
     * Obtiene el intento de un quiz
     * @param type $courseid
     * @param type $userid
     * @param \local_mutualnotifications\type $DB
     * @return type
     */
    public static function get_attemp_quiz($courseid, $userid, $DB)
    {
        $sql = 'SELECT qa.id as attemp
            FROM {quiz} as q, {quiz_attempts} as qa, {course} as c, {user} as u
            WHERE c.id= ?
            AND u.id = ?
            AND qa.quiz = q.id
            AND c.id = q.course
            AND qa.userid = u.id';

        $result = $DB->get_records_sql($sql, array($courseid, $userid));
        return $result[key($result)]->attemp;
    }

    /**
     * Obtiene los registros con timecompleted mayores que la última fecha de ejecución
     * de la tarea programada Notificación de curso completado
     * @global \local_mutualnotifications\type $DB
     * @param type $last_run_time última ejecución de la tarea
     * @return array de obj
     */
    public static function get_lasts_completed_since_last_run_time($last_run_time)
    {
        global $DB;
        $sql = 'SELECT id, userid, course, timecompleted FROM {course_completions} WHERE timecompleted >= ?';
        $result = $DB->get_records_sql($sql, array($last_run_time));
        return $result;
    }
}
