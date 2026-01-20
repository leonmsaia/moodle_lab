<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


include_once('../../config.php');

/**
 * @var core_renderer $OUTPUT
 * @var moodle_page $PAGE
 */
global $OUTPUT, $CFG, $PAGE;

require_once($CFG->libdir . '/adminlib.php');
require_once('lib.php');

require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->libdir . '/tcpdf/tcpdf.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/grade/constants.php');


require_login();
$PAGE->set_context(context_system::instance());

$PAGE->set_pagelayout('embedded');

$courseid          = required_param('courseid', PARAM_INT);
$years_certificate = get_config('completion_attendance', 'config_years_certificate');
$years_certificate = (!empty($years_certificate)) ? $years_certificate : 3;



$coursenamecertificate = get_string('coursenamecertificate', 'local_download_cert', '');
$datefinish = get_string('datefinishcertificate', 'local_download_cert', '');
$grade = get_string('grade', 'local_download_cert', '');
$status = get_string('status', 'local_download_cert', '');
$companyname = get_string('companyname', 'local_download_cert', '');
$status = get_string('status', 'local_download_cert', '');
$expirationdate = get_string('expirationdate', 'local_download_cert', '');
$horas = "";
$course = $DB->get_record('course', array('id' => $courseid));
$completionbool = false;
$completion = '';
try {
    /*
                    se considera completado si aprobo el curso nota de usuario mayor a 75 en base a la nota del curso
                    y si su asistencia es 100%(configurable)
    */
    $curso_aprobado = \local_resumencursos\utils\summary_utils::completado_aprobado($course, $USER);
    $completion = \local_resumencursos\utils\summary_utils::get_course_completion($course, $USER);
    //si completo el curso si la nota del usuario es mayor que la solicitada para pasar el curso

    if ($curso_aprobado == true) {
        if (\local_mutual\front\utils::is_course_elearning($course->id) == true) {
            if ($completion) {
                $completionbool = true;
            } else {
                $completionbool = false;
            }
        } else if ((\local_mutual\front\utils::is_course_streaming($course->id) == true) || (\local_mutual\front\utils::is_course_presencial($course->id) == true)) {
            $last_sesion = \local_resumencursos\utils\summary_utils::get_last_session_user($USER->id, $course->id);
            //si completo el curso con el curterio presencial o streming 
            //si tiene asistencia completada segun el calificador 
            //si tiene sesiones calificadas y traigo la ultima
            if (!empty($last_sesion)) {
                $completionbool = true;
            } else {
                $completionbool = false;
            }
        } else {
            if ($completion) {
                $completionbool = true;
            } else {
                $completionbool = false;
            }
        }
    } else {
        $completionbool = false;
    }

    if ($completionbool == false) {
        redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid, '');
    }
    $stringbody = new stdClass();
    $stringbody->name = \local_mutual\back\utils::get_fullname_apellido_materno($USER->id);

    $stringbody->rut = $USER->username;
    $context = context_course::instance($courseid);
    if (is_enrolled($context, $USER->id, '', true)) {

        $sql = "SELECT ue.*, YEAR(FROM_UNIXTIME(ue.timecreated)) as yearenrol,
        ue.timestart as timestartenrol,
        ue.timecreated as timecreatedenrol
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = :courseid)
                      JOIN {user} u ON u.id = ue.userid
                     WHERE ue.userid = :userid AND u.deleted = 0";
        $sqlenrol = $DB->get_record_sql($sql, array('userid' => $USER->id, 'courseid' => $courseid));

        $extrafield =  $DB->get_record('eabcattendance_extrafields', array('userid' => $USER->id));
        //        $localdownload_certcompletion =  $DB->get_record('mutual_log_local_download_cert', array('userid' => $USER->id, 'courseid' => $courseid));
        //$completion = $DB->get_record('course_completions', array('userid' => $USER->id, 'course' => $courseid));

        $stringbody->year = $sqlenrol->yearenrol;
        $coursenamecertificate = get_string('coursenamecertificate', 'local_download_cert', $course->fullname);

        //capturo la nota del curso despues de finalizarlo
        $gradeitemparamscourse = [
            'itemtype' => 'course',
            'courseid' => $courseid,
        ];
        $grade_course = \grade_item::fetch($gradeitemparamscourse);
        $grades_user = \grade_grade::fetch_users_grades($grade_course, array($USER->id), false);
        $finalgradeuser = $grades_user[key($grades_user)]->finalgrade;

        if (!empty($grades_user)) {
            $grade = get_string('grade', 'local_download_cert', number_format($finalgradeuser, 2, '.', ''));
            if (floatval($finalgradeuser) >= floatval($grade_course->gradepass)) {
                $status = get_string('status', 'local_download_cert', get_string('aprobado', 'local_download_cert'));
            } else {
                $status = get_string('status', 'local_download_cert', get_string('reprobado', 'local_download_cert'));
            }
        }

        if (!empty($extrafield)) {
            $companyname = get_string('companyname', 'local_download_cert', $extrafield->empresarut);
        }

        //$sqlyear = $DB->get_record('download_cert_expiration', array('courseid' => $courseid));
        $year = $years_certificate;
        require_once($CFG->dirroot . '/lib/moodlelib.php');
        $formatexpirationdate = "";
        $curso_aprobado = \local_resumencursos\utils\summary_utils::completado_aprobado($course, $USER);
        //si completo el curso si la nota del usuario es mayor que la solicitada para pasar el curso
        if ($curso_aprobado == true) {
            $curso_back_bool = false;
            $cursos_back = $DB->get_records('curso_back', array('id_curso_moodle' => $course->id));
            //si tengo registro en curso_back para este curso
            if (!empty($cursos_back)) {
                $cursos_back =  end($cursos_back);
                //si tengo registro en $cursos_back->vigenciadocumentos el cual me da los años de vencimiento
                if (!empty($cursos_back->vigenciadocumentos)) {
                    $curso_back_bool = true;
                }
            }

            if (\local_mutual\front\utils::is_course_elearning($course->id) == true) {
                $date_expire = '+36 month +30 days';
                if ($curso_back_bool) {
                    $date_expire = '+' . $cursos_back->vigenciadocumentos . ' year +30 days';
                }
                if(!empty($sqlenrol->timestartenrol)){
                    $formatexpirationdate = userdate(strtotime(date("Y-m-d", $sqlenrol->timestartenrol) . " " . $date_expire), '%m/%Y');
                } else {
                    if(!empty($sqlenrol->timecreatedenrol)){
                        $formatexpirationdate = userdate(strtotime(date("Y-m-d", $sqlenrol->timecreatedenrol) . " " . $date_expire), '%m/%Y');
                    }
                }
            } else if ((\local_mutual\front\utils::is_course_streaming($course->id) == true) || (\local_mutual\front\utils::is_course_presencial($course->id) == true)) {
                $date_expire = '+36 month +30 days';
                if ($curso_back_bool) {
                    $date_expire = '+' . $cursos_back->vigenciadocumentos . ' year +30 days';
                }
                $last_sesion = \local_resumencursos\utils\summary_utils::get_last_session_user($USER->id, $course->id);
                //si completo el curso con el curterio presencial o streming 
                //si tiene asistencia completada segun el calificador 
                if (!empty($last_sesion)) {
                    $formatexpirationdate = userdate(strtotime(date("Y-m-d", $last_sesion->sessdate) . " " . $date_expire), '%d/%m/%Y');
                } else {
                    $formatexpirationdate = '';
                }
            } else {
                // por default tomo criterio de elearning
                $date_expire = '+36 month +30 days';
                if($curso_back_bool) {
                    $date_expire = '+' . $cursos_back->vigenciadocumentos . ' year +30 days';
                }
                if(!empty($sqlenrol->timestartenrol)){
                    $formatexpirationdate = userdate(strtotime(date("Y-m-d", $sqlenrol->timestartenrol) . " " . $date_expire), '%m/%Y');
                } else {
                    if(!empty($sqlenrol->timecreatedenrol)){
                        $formatexpirationdate = userdate(strtotime(date("Y-m-d", $sqlenrol->timecreatedenrol) . " " . $date_expire), '%m/%Y');
                    }
                }
            }

        } else {
            $formatexpirationdate = "";
        }
        //$formatexpirationdate = date('d/m/Y', strtotime('+' . $year . ' year', $course->enddate));
        //$expirationdate = get_string('expirationdate', 'local_download_cert', $formatexpirationdate);
    } else {
        $stringbody->year = "No matriculado";
    }

    $cursoback = $DB->get_record('curso_back', array('id_curso_moodle' => $courseid));
    if (!empty($cursoback)) {
        $horas = $cursoback->horas;
    }

    //guardo en base de datos la primera ves el codigo y la fecha de emision(primera consulta del)
    //certificado del curso
    $codecertificate = '';
    $get_cert = $DB->get_record('download_cert_code', array('courseid' => $courseid, 'userid' => $USER->id));
    if(empty($get_cert)){
        $datacertdb = new stdClass();
        $datacertdb->courseid = $courseid;
        $datacertdb->userid = $USER->id;
        $datacertdb->code_certificate = md5($USER->id . $courseid);
        $datacertdb->timecreated = time();
        $get_certid = $DB->insert_record('download_cert_code', $datacertdb);
        $get_cert = $DB->get_record('download_cert_code', array('id' => $get_certid));
        $codecertificate = $get_cert->code_certificate;
        $datacertdb->enrol = 'Create code';
        $datacertdb->status = 'enrol';
        /* $event = \local_download_cert\event\enrol_unenrol::create(
            array(
                'context' => $context,
                'other' => array('enrol' =>  json_encode($datacertdb),)
            )
        );
        $event->trigger(); */

    } else {
        $codecertificate = $get_cert->code_certificate;
    }
    $com = \local_resumencursos\utils\summary_utils::get_course_completion($course, $USER);
    //echo "<pre>" . print_r($com, true)."</pre>";exit;
    if (\local_mutual\front\utils::is_course_elearning($course->id) == true) {
        //si termino el curso
        if (!empty($com)) {
            if (isset($com->timecompleted) && !empty($com->timecompleted)) {
                if ($com->timecompleted != "0") {
                    $fecha_final =  userdate($com->timecompleted, get_string('strftimedate', 'langconfig'));
                } else {
                    $fecha_final = '';
                }
            } else {
            }
        }
    } else if ((\local_mutual\front\utils::is_course_streaming($course->id) == true) || (\local_mutual\front\utils::is_course_presencial($course->id) == true)) {
        $last_sesion = \local_resumencursos\utils\summary_utils::get_last_session_user($USER->id, $course->id);
        //si completo el curso con el curterio presencial o streming 
        //si tiene asistencia completada segun el calificador 
        //si tiene sesiones calificadas y traigo la ultima
        if (!empty($last_sesion->sessdate)) {
            //$fecha_final =  date('d \D\E M \D\E Y', strtotime(date("Y-m-d", $last_sesion)));
            $fecha_final =  userdate(strtotime(date("Y-m-d", $last_sesion->sessdate)), get_string('strftimedate', 'langconfig'));
        } else {
            $fecha_final = "";
        }
    } else {
        if (!empty($com)) {
            if (isset($com->timecompleted) && !empty($com->timecompleted)) {
                if ($com->timecompleted != "0") {
                    $fecha_final =  userdate($com->timecompleted, get_string('strftimedate', 'langconfig'));
                } else {
                    $fecha_final = '';
                }
            } else {
            }
        }
    }

    if(!empty($data->timestartenrol)){
        return userdate(strtotime(date("Y-m-d", $data->timestartenrol) . " +36 month +30 days"), '%d/%m/%Y');
    } else {
        if(!empty($data->timecreatedenrol)){
            return userdate(strtotime(date("Y-m-d", $data->timecreatedenrol) . " +36 month +30 days"), '%m/%Y');
        }
    }
    
    $datefinish = ($fecha_final) ? $fecha_final : userdate($com->timecompleted, get_string('strftimedate', 'langconfig'));
    $clearbr = true;
    if(strlen($course->fullname) > 82){
        $clearbr = false;
    }
    $data = [
        'bodypdf' => get_string('textbodypdfcertificate', 'local_download_cert', $stringbody),
        'course' => $course,
        'coursename' => strtoupper($course->fullname),
        'coursenamecertificate' => $coursenamecertificate,
        'datefinish' => $datefinish,
        'companyname' => $companyname,
        'grade' => $grade,
        'status' => $status,
        'expirationdate' => $formatexpirationdate,
        'background' => $OUTPUT->image_url('borde-certificado', 'local_download_cert'),
        'username' => strtoupper(\local_mutual\back\utils::get_fullname_apellido_materno($USER->id)),
        'duration' => $horas,
        'codigo' => $codecertificate,
        'empresa' => strtoupper(\local_pubsub\utils::get_company_name($USER->id)),
        'clearbr' => $clearbr
    ];


    $htmlbody = $OUTPUT->render_from_template('local_download_cert/bodypdfcertificate', $data);
    //echo $htmlbody;exit;

    $pdf = new local_download_cert\download_certificate('L');

    // set document information
    $pdf->SetCreator(PDF_CREATOR);

    $tagvs = array(
        'div' => array(
            0 => array('h' => 0, 'n' => 0),
            1 => array('h' => 0, 'n' => 0),
            2 => array('h' => 0, 'n' => 0),
            3 => array('h' => 0, 'n' => 0),
            4 => array('h' => 0, 'n' => 0),
            5 => array('h' => 0, 'n' => 0),
            6 => array('h' => 0, 'r' => 0)
        ),
        'p' => array(
            0 => array('h' => 0, 'n' => 0),
            1 => array('h' => 0, 'n' => 0),
            2 => array('h' => 0, 'n' => 0),
            3 => array('h' => 0, 'n' => 0),
            4 => array('h' => 0, 'n' => 0),
            5 => array('h' => 0, 'n' => 0),
            6 => array('h' => 0, 'r' => 0)
        )

    );

    $pdf->setHtmlVSpace($tagvs);

    // set header and footer fonts
    $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
    $pdf->SetMargins(15, PDF_MARGIN_TOP, 0);
    $pdf->SetHeaderMargin(1);
    $pdf->SetTopMargin(0);
    $pdf->SetLeftMargin(20);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 8);

    // set font
    $pdf->SetFont('helvetica', '', 10);

    // add a page
    $pdf->AddPage();

    //$img_file = 'pix/borde-certificado.jpg';
    //$pdf->Image($img_file, 8, 8, 120, 300, '', '', '', true, 300, '', false, false );

    // get the current page break margin
    $bMargin = $pdf->getBreakMargin();
    // get current auto-page-break mode
    $auto_page_break = $pdf->getAutoPageBreak();
    // disable auto-page-break
    $pdf->SetAutoPageBreak(false, 0);
    // set bacground image
    $img_file = 'pix/modelo-diploma.jpg';
    $pdf->Image($img_file, 0, 0, 298, 210, '', '', '', false, 300, '', false, false, 0);
    //$pdf->Image($img_file, 0, 0, 200, 300, '', '', '', false, 300, '', false, false, 0);
    //$pdf->ImageSVG($file='pix/modelo-diploma.svg', $x=8, $y=8, $w=64, $h=192, $link='', $align='', $palign='', $border=1, $fitonpage=false);
    // restore auto-page-break status
    $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
    // set the starting point for the page content
    $pdf->setPageMark();

    // output the HTML content
    $pdf->writeHTML($htmlbody, true, 0, true);

    // reset pointer to the last page
    $pdf->lastPage();

    //    echo $htmlbody;
    $pdf->Output('Cuestinario.pdf');
} catch (coding_exception $e) {
    throw new moodle_exception('errormsg', 'local_download_cert', '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception('errormsg', 'local_download_cert', '', $e->getMessage(), $e->debuginfo);
}
