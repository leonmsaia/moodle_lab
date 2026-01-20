<?php

namespace local_cron\task;

require_once($CFG->dirroot . '/local/cron/lib.php');
class finalizarCapacitacionElearnging extends \core\task\scheduled_task
{

    public $days;

    public function __construct()
    {
        $days = (int)get_config('local_cron', 'days');
        if (empty($days)) {
            $days = 30;
        }
        $this->days = $days;
    }

    public function get_name()
    {
        return get_string('task_end_training', 'local_cron');
    }

    /**
	  $resultado = Aprobado es 1, Reprobado es 0
	  $motivo_resultado = Si finaliza con nota baja es 1, si no completó el curso, un 2, si está aprobado, un 0
     */

    public function execute()
    {
        /** @var \moodle_database $DB */
        global $DB;

        raise_memory_limit(MEMORY_EXTRA);

        $today = time();

        $enrollments = \local_cron\utils::get_cron_enrolments();

        foreach ($enrollments as $enrol) {
            $is_back_inscription = $DB->record_exists(
                'inscripcion_elearning_back',
                array(
                    'id_curso_moodle' => $enrol->courseid,
                    'id_user_moodle' => $enrol->userid
                )
            );

            if (!$is_back_inscription) {
                $user = $DB->get_record('user', array('id' => $enrol->userid));
                $course = $DB->get_record('course', array('id' => $enrol->courseid));

                $enroltime = $enrol->timecreated;

                // si existe tomamos el timestart del usuario
                if (!empty($enrol->timestart)) {
                    $enroltime = $enrol->timestart;
                }
                $status = \local_cron\utils::get_user_course_status($user, $course, $enroltime, $this->days, $enrol->timecreated);

                if (!empty($status)) {
                    if ($status->status == \local_cron\utils::STATUS_APROBADO) {
                        $get_xml_body = get_xml_body($user, $course, 1, 0, $today);
                    } elseif ($status->status == \local_cron\utils::STATUS_REPROBADO) {
                        $get_xml_body = get_xml_body($user, $course, 0, 1, $today);
                    } else {
                        // reprobado por inasistencia
                        $get_xml_body = get_xml_body($user, $course, 0, 2, $today);
                    }

                    var_dump($get_xml_body);
                    $encrypt_xml_body = encrypt_base64($get_xml_body);
                    //enviar request y capturar respuesta como un objeto con codigo y mensaje
                    $get_soap_request = get_soap_request($encrypt_xml_body);
                    $testing = get_config('local_cron', 'testing');
                    if (empty($testing)) {
                        // si no esta activado el testing enviar los datos y guardarlo en base de datos
                        if ($get_soap_request->soapenvBody->respElearningCapacitacionFinalizarExpResp->return->respuesta->codigo == "0") {
                            save_log(
                                $status->status,
                                $status->courseid,
                                $status->userid,
                                $status->date,
                                $status->grade,
                                $status->gradepass
                            );
                            echo "<br>curso: " . $course->id . " y usuario: " . $user->id . " " . $status->status;
                        } else {
                            var_dump($get_soap_request);
                        }
                    } else {
                        save_log(
                            $status->status,
                            $status->courseid,
                            $status->userid,
                            $status->date,
                            $status->grade,
                            $status->gradepass
                        );
                        mtrace(serialize($status));
                    }
                }
            }
        }
    }
}
