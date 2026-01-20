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

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->libdir . '/tcpdf/tcpdf.php');
require_once($CFG->dirroot . '/mod/assign/feedback/editpdf/classes/pdf.php');

$id_curso = optional_param('id_curso', -1, PARAM_INT);

require_login();
$PAGE->set_context(context_system::instance());

$PAGE->set_pagelayout('embedded');

try {
    if(!$SESSION->filtersummary){
        redirect($CFG->wwwroot . '/local/resumencursos/view.php', 'Sesión vencida');
    }
    $datastr = new stdClass();
    $datastr->fullname = fullname($USER);
    $datastr->username = $USER->username;
    //
    if (!empty($SESSION->filtersummary->dateto) && ($SESSION->filtersummary->dateto != 0)) {
        if(is_array($SESSION->filtersummary->dateto)){
            $datastr->desde = $SESSION->filtersummary->dateto['day'] . '/' . $SESSION->filtersummary->dateto['month'] . "/" . $SESSION->filtersummary->dateto['year'];
        } else {
            $datastr->desde = date('d/m/Y', $SESSION->filtersummary->dateto);
        }
    } else {
        $datastr->desde = '1/1/2019';
    }
    if (!empty($SESSION->filtersummary->datefrom) && ($SESSION->filtersummary->datefrom != 0)) {
        if(is_array($SESSION->filtersummary->datefrom)){
            $datastr->hasta = $SESSION->filtersummary->datefrom['day'] . '/' . $SESSION->filtersummary->datefrom['month'] . "/" . $SESSION->filtersummary->datefrom['year'];
        } else {
            $datastr->hasta = date('d/m/Y', $SESSION->filtersummary->datefrom);
        }
    } else {
        $datastr->hasta = date('d/m/Y', time());
    }

    $fromform = new stdClass();
    $fromform->curso = $SESSION->filtersummary->curso;
    $fromform->hours = $SESSION->filtersummary->hours;
    $fromform->modalidadopresencial = isset($SESSION->filtersummary->modalidadopresencial) ? $SESSION->filtersummary->modalidadopresencial : false;
    $fromform->modalidadsemipresencial = isset($SESSION->filtersummary->modalidadsemipresencial) ? $SESSION->filtersummary->modalidadsemipresencial : false;
    $fromform->modalidaddistancia = isset($SESSION->filtersummary->modalidaddistancia) ? $SESSION->filtersummary->modalidaddistancia : false;
    $fromform->dateto = $SESSION->filtersummary->dateto;
    $fromform->datefrom = $SESSION->filtersummary->datefrom;

    $is_capability = false;
    $is_capability_str = "";
    if(!is_siteadmin()){
        $enrolled_courses = enrol_get_all_users_courses($USER->id);
        foreach($enrolled_courses as $enrolled_course){
            if (has_capability('local/resumencursos:access_course', \context_course::instance($enrolled_course->id))){
                $is_capability = true;
                $is_capability_str .= $enrolled_course->id.",";
            }
        }
    }
    $where = \local_resumencursos\utils\summary_utils::get_where($fromform, $is_capability, $is_capability_str);
    $select = \local_resumencursos\utils\summary_utils::get_select_table_sql($USER->id);
    $from = \local_resumencursos\utils\summary_utils::get_from_table_sql($USER->id, is_siteadmin());

    $datasql = $DB->get_records_sql("SELECT " . $select . " FROM " . $from . " WHERE 1=1 ".$where);
    $datasqlarray = array();
    foreach($datasql as $datasq){
        $get_course = $DB->get_record('course', array('id' => $datasq->courseid));
        $datasq->modalidad = \local_resumencursos\utils\summary_utils::get_modalidad($datasq);
        $datasq->fullnamecourse = $get_course->fullname;
        $curso_aprobado = \local_resumencursos\utils\summary_utils::completado_aprobado($get_course, $USER);
        //si completo el curso si la nota del usuario es mayor que la solicitada para pasar el curso
        if ($curso_aprobado == true) {
            /* if(!empty($datasq->timestartenrol)){
                $datasq->final_date = userdate(strtotime(date("Y-m-d", $datasq->timestartenrol) . " +36 month +30 days"), '%m/%Y');
            } else {
                if(!empty($datasq->timecreatedenrol)){
                    $datasq->final_date = userdate(strtotime(date("Y-m-d", $datasq->timecreatedenrol) . "  +36 month +30 days"), '%m/%Y');
                }
            } */
            if ((\local_mutual\front\utils::is_course_streaming($get_course->id) == true) || (\local_mutual\front\utils::is_course_presencial($get_course->id) == true)) {
                $last_sesion = \local_resumencursos\utils\summary_utils::get_last_session_user($USER->id, $get_course->id);
                //si completo el curso con el curterio presencial o streming 
                //si tiene asistencia completada segun el calificador 
                if (!empty($last_sesion->sessdate)) {
                    $datasq->final_date = userdate(strtotime(date("Y-m-d", $last_sesion->sessdate) . " +36 month"), '%d/%m/%Y');
                } else {
                    $datasq->final_date = '';
                }
            } else {
                if(!empty($datasq->timestartenrol)){
                    $datasq->final_date = userdate(strtotime(date("Y-m-d", $datasq->timestartenrol) . " +36 month +30 days"), '%m/%Y');
                } else {
                    if(!empty($datasq->timecreatedenrol)){
                        $datasq->final_date = userdate(strtotime(date("Y-m-d", $datasq->timecreatedenrol) . "  +36 month +30 days"), '%m/%Y');
                    }
                }
            }
        } else {
            $datasq->final_date = '';
        }
        $datasqlarray[] = (array)$datasq;
    }

    //$datasq->final_date = $dateformat[2].'-'.$dateformat[1].'-'.$dateformat[0];
    $data = [
        'bodypdf' => get_string('bodypdf', 'local_resumencursos', $datastr),
        'dataws' => $datasqlarray,
    ];

    $htmlbody = $OUTPUT->render_from_template('local_resumencursos/bodypdfcertificate', $data);

    $pdf = new local_resumencursos\download_certificate('L');

    // set document information
    $pdf->SetCreator(PDF_CREATOR);

    $tagvs = array(
        'h1' => array(
            0 => array('h' => '', 'n' => 2),
            1 => array('h' => 1.3, 'n' => 1)
        ),
        'div' => array(
            0 => array('h' => 0, 'n' => 0),
            1 => array('h' => 0, 'n' => 0),
            2 => array('h' => 0, 'n' => 0),
            3 => array('h' => 0, 'n' => 0),
            4 => array('h' => 0, 'n' => 0),
            5 => array('h' => 0, 'n' => 0),
            6 => array('h' => 0, 'r' => 0)
        ),
    );

    $pdf->setHtmlVSpace($tagvs);

    // set margins
    $pdf->SetMargins(10, PDF_MARGIN_TOP, 10);
    $pdf->SetTopMargin(40);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 8);
    // set font
    $pdf->SetFont('helvetica', '', 10);

    // add a page
    $pdf->AddPage();

    // get the current page break margin
    $bMargin = $pdf->getBreakMargin();
    // get current auto-page-break mode
    $auto_page_break = $pdf->getAutoPageBreak();
    $pdf->SetAutoPageBreak($auto_page_break, $bMargin);
    // set the starting point for the page content
    $pdf->setPageMark();

    // output the HTML content
    $pdf->writeHTML($htmlbody, true, 44, true, 44);
    $img = file_get_contents('./pix/firma_certificado.png');
    $pdf->Image('@' . $img, 150,'', 60, '', '', '', '', '', '', 'C');

    // reset pointer to the last page
    $pdf->lastPage();

    //    echo $htmlbody;
    $pdf->Output();
} catch (coding_exception $e) {
    throw new moodle_exception('errormsg', 'local_download_cert', '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception('errormsg', 'local_download_cert', '', $e->getMessage(), $e->debuginfo);
}
