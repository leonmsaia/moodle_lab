<?php

namespace local_resumencursos\table;

require_once("$CFG->libdir/tablelib.php");

use stdClass;
use core_plugin_manager;
use html_writer;
use moodle_url;
use local_resumencursos\utils\summary_utils;

class custom_table extends \table_sql
{
    var $pagesize    = 50;

    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
        $columns = array(
            'courseid',
            'horas',
            'modalidad',
            'final_date',
            'grade',
            'disponibilidad',
        );
        $this->define_columns($columns);
        $headers = array(
            get_string('nombrecurso', 'local_resumencursos'),
            get_string('time', 'local_resumencursos'),
            get_string('modalidad', 'local_resumencursos'),
            get_string('caducidad', 'local_resumencursos'),
            get_string('calificacion', 'local_resumencursos'),
            get_string('disponibilidad', 'local_resumencursos'),
        );
        $this->define_headers($headers);
    }

    function other_cols($colname, $data)
    {
        /** @var \moodle_database $DB */
        global $DB, $CFG, $USER, $OUTPUT;
        $course = $DB->get_record('course', array('id' => $data->courseid));

        $isstreaming = \local_mutual\front\utils::is_course_streaming($course->id);
        $ispresencial = \local_mutual\front\utils::is_course_presencial($course->id);
        $certificado_back = ["exist" => false];
        if ($isstreaming || $ispresencial) {
            $last_sesion = \local_resumencursos\utils\summary_utils::get_last_session_user($USER->id, $course->id);
            if (!empty($last_sesion)) {
                $certificado_back = \local_mutual\back\utils::get_certificado_url($USER->id, $course->id, $last_sesion->id);
            }
        } else {
            $certificado_back = \local_mutual\back\utils::get_certificado_url($USER->id, $course->id);
        }
        if ($colname == 'courseid') {
            $url = new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $course->id));
            return html_writer::link($url, $course->fullname, array());
        }
        if ($colname == 'disponibilidad') {
            $puede_descargar = \local_resumencursos\utils\summary_utils::completado_aprobado($course, $USER);
            $allowdownloadwithgrade = \local_resumencursos\utils\summary_utils::allow_download_with_grade();
            //si completo el curso si la nota del usuario es mayor que la solicitada para pasar el curso
            if ($puede_descargar) {
                if (\local_mutual\front\utils::is_course_elearning($course->id) == true) {
                    //si es ilerning y completo los feedback
                    if (get_config('local_resumencursos', 'active_diploma') == 1) {
                        if (empty($data->final_date) && !$allowdownloadwithgrade) {
                            return "";
                        } else {
                            //si tiene la configuracion activa
                            //y tiene course completion
                            if ($certificado_back['exist']) {
                                $url = $certificado_back['data']->UrlArchivo;
                            } else {
                                $url = new moodle_url(
                                    $CFG->wwwroot . '/local/download_cert/donwloadcertificate.php',
                                    array('courseid' => $course->id)
                                );
                            }
                            $img = html_writer::img($OUTPUT->image_url("diploma", "local_resumencursos"), get_string('diploma', 'local_resumencursos'), array('tilte' => get_string('diploma', 'local_resumencursos'), 'style' => 'width: 30px'));
                            return html_writer::link($url, $img, array("target" => "_blank"));
                        }
                    } else {
                        return '';
                    }
                } else if ((\local_mutual\front\utils::is_course_streaming($course->id) == true) || (\local_mutual\front\utils::is_course_presencial($course->id) == true)) {
                    //si un curso es streaming o presencial
                    $last_sesion = \local_resumencursos\utils\summary_utils::get_last_session_user($USER->id, $course->id);
                    $completion_attendance = \local_download_cert\download_cert_utils::completion_attendance($course->id);
                    //si completo el curso con el curterio presencial o streming 
                    //si tiene asistencia completada segun el calificador 
                    //si tiene sesiones calificadas y traigo la ultima
                    //si tiene nota 100 en asistencia
                    //cambio 2025-10-13
                    //if (!empty($last_sesion) && !empty($last_sesion->sessdate) && $completion_attendance ==  true) {
                    if ($completion_attendance ==  true) {
                        if (get_config('local_resumencursos', 'active_diploma') == 1) {
                            //si tiene la configuracion activa
                            if ($certificado_back['exist']) {
                                $url = $certificado_back['data']->UrlArchivo;
                            } else {
                                $url = new moodle_url(
                                    $CFG->wwwroot . '/local/download_cert/donwloadcertificate.php',
                                    array('courseid' => $course->id)
                                );
                            }
                            $img = html_writer::img($OUTPUT->image_url("diploma", "local_resumencursos"), get_string('diploma', 'local_resumencursos'), array('tilte' => get_string('diploma', 'local_resumencursos'), 'style' => 'width: 30px'));
                            return html_writer::link($url, $img, array("target" => "_blank"));
                        } else {
                            return '';
                        }
                    } else {
                        return '';
                    }
                } else {
                    // si no esta en curso back tomamos elearning por default
                    if (get_config('local_resumencursos', 'active_diploma') == 1) {
                        if (empty($data->final_date) && !$allowdownloadwithgrade) {
                            return "";
                        } else {
                            //si tiene la configuracion activa
                            //y tiene course completion
                            if ($certificado_back['exist']) {
                                $url = $certificado_back['data']->UrlArchivo;
                            } else {
                                $url = new moodle_url(
                                    $CFG->wwwroot . '/local/download_cert/donwloadcertificate.php',
                                    array('courseid' => $course->id)
                                );
                            }
                            $img = html_writer::img($OUTPUT->image_url("diploma", "local_resumencursos"), get_string('diploma', 'local_resumencursos'), array('tilte' => get_string('diploma', 'local_resumencursos'), 'style' => 'width: 30px'));
                            return html_writer::link($url, $img, array("target" => "_blank"));
                        }
                    } else {
                        return '';
                    }
                }
            } else {
                return '';
            }
        }

        if ($colname == 'final_date') {
            $curso_aprobado = \local_resumencursos\utils\summary_utils::completado_aprobado($course, $USER);
            //si completo el curso si la nota del usuario es mayor que la solicitada para pasar el curso
            if ($curso_aprobado == true) {
                $curso_back_bool = false;
                $cursos_back = $DB->get_records('curso_back', array('id_curso_moodle' => $course->id));
                //si tengo registro en curso_back para este curso
                if(!empty($cursos_back)){
                    $cursos_back =  end($cursos_back);
                    //si tengo registro en $cursos_back->vigenciadocumentos el cual me da los años de vencimiento
                    if(!empty($cursos_back->vigenciadocumentos)){
                        $curso_back_bool = true;
                    }
                }

                //si es curso streaming o presencial
                 if ((\local_mutual\front\utils::is_course_streaming($course->id) == true) || (\local_mutual\front\utils::is_course_presencial($course->id) == true)) {
                    $last_sesion = \local_resumencursos\utils\summary_utils::get_last_session_user($USER->id, $course->id);
                    //si completo el curso con el curterio presencial o streming 
                    //si tiene asistencia completada segun el calificador 
                    //si tiene sesiones calificadas y traigo la ultima
                    if (!empty($last_sesion) && !empty($last_sesion->sessdate)) {
                        $date_expire = '+36 month +30 days';
                        if ($curso_back_bool) {
                            $date_expire = '+' . $cursos_back->vigenciadocumentos . ' year +30 days';
                        }
                        return userdate(strtotime(date("Y-m-d", $last_sesion->sessdate) . " " . $date_expire), '%m/%Y');
                    } else {
                        return '';
                    }
                } else {
                    // por default tomo criterio de elearning
                    $date_expire = '+36 month +30 days';
                    if($curso_back_bool) {
                        $date_expire = '+' . $cursos_back->vigenciadocumentos . ' year +30 days';
                    }
                    if(!empty($data->timestartenrol)){
                        return userdate(strtotime(date("Y-m-d", $data->timestartenrol) . " " . $date_expire), '%m/%Y');
                    } else {
                        if(!empty($data->timecreatedenrol)){
                            return userdate(strtotime(date("Y-m-d", $data->timecreatedenrol) . " " . $date_expire), '%m/%Y');
                        }
                    }
                } 
                
                return '';
            } else {
                return '';
            }
        }
        if ($colname == 'modalidad') {
            return summary_utils::get_modalidad($data);
        }
    }

    /**
     * Get the html for the download buttons
     *
     * Usually only use internally
     */
    public function download_buttons()
    {
        return '';
    }
}
