<?php

    include_once('../../config.php');
    require_once($CFG->dirroot . '/user/profile/lib.php');
    require_once($CFG->libdir . '/pdflib.php');
    require_once($CFG->libdir . '/tcpdf/tcpdf.php');
    require_once($CFG->libdir . '/enrollib.php');
    require_once($CFG->libdir . '/grade/grade_item.php');
    require_once($CFG->libdir . '/grade/grade_grade.php');
    require_once($CFG->libdir . '/grade/constants.php');

    global $PAGE, $DB;

    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/local/download_cert/validator_download.php'));
    $PAGE->set_title('Descarga de diplomas');
    $PAGE->set_heading('Descarga de diplomas');


    $diplomacodigo     = required_param('codigo', PARAM_RAW);
    
    $sql = "SELECT * FROM {download_cert_code} WHERE code_certificate = :code ";
    $diploma = $DB->get_record_sql($sql, array('code' => $diplomacodigo));

    $diplomacourseid   = $diploma->courseid;
    $diplomauserid     = $diploma->userid;

    $coursenamecertificate = get_string('coursenamecertificate', 'local_download_cert', '');
    $datefinish = get_string('datefinishcertificate', 'local_download_cert', '');
    $grade = get_string('grade', 'local_download_cert', '');
    $status = get_string('status', 'local_download_cert', '');
    $companyname = get_string('companyname', 'local_download_cert', '');
    $status = get_string('status', 'local_download_cert', '');
    $expirationdate = get_string('expirationdate', 'local_download_cert', '');
    $horas = "";

    $user   = $DB->get_record('user', array('id' => $diplomauserid));
    $course = $DB->get_record('course', array('id' => $diplomacourseid));

    $fecha_final = '';
    $formatexpirationdate = "";

    $stringbody = new stdClass();
    $stringbody->name = \local_mutual\back\utils::get_fullname_apellido_materno($diploma->userid);
    $stringbody->rut = $user->username;

    $clearbr = true;
    if(strlen($course->fullname) > 82){
        $clearbr = false;
    }

    $coursenamecertificate = get_string('coursenamecertificate', 'local_download_cert', $course->fullname);

    $completion = $DB->get_record('course_completions', array('userid' => $user->id, 'course' => $course->id));

    if($completion){
        $fecha_final =  userdate($completion->timecompleted, get_string('strftimedate', 'langconfig'));
        $formatexpirationdate = userdate(strtotime(date("Y-m-d", $completion->timecompleted) . " +36 month"), '%d/%m/%Y');
    }

    $extrafield =  $DB->get_record('eabcattendance_extrafields', array('userid' => $diplomauserid));

    if (!empty($extrafield)) {
        $companyname = get_string('companyname', 'local_download_cert', $extrafield->empresarut);
    }

    $gradeitemparamscourse = [
        'itemtype' => 'course',
        'courseid' => $course->id,
    ];
    $grade_course = \grade_item::fetch($gradeitemparamscourse);
    $grades_user = \grade_grade::fetch_users_grades($grade_course, array($user->id), false);
    
    if (!empty($grades_user)) {
        $finalgradeuser = $grades_user[key($grades_user)]->finalgrade;
        $grade = get_string('grade', 'local_download_cert', number_format($finalgradeuser, 2, '.', ''));
        if (floatval($finalgradeuser) >= floatval($grade_course->gradepass)) {
            $status = get_string('status', 'local_download_cert', get_string('aprobado', 'local_download_cert'));
        } else {
            $status = get_string('status', 'local_download_cert', get_string('reprobado', 'local_download_cert'));
        }
    }

    $cursoback = $DB->get_record('curso_back', array('id_curso_moodle' => $course->id));
    if (!empty($cursoback)) {
        $horas = $cursoback->horas;
    }

    if (\local_mutual\front\utils::is_course_elearning($course->id)) {
        //si termino el curso
        if (!empty($completion)) {
            if (!empty($completion->timecompleted)) {
                if ($completion->timecompleted != "0") {
                    $fecha_final =  userdate($completion->timecompleted, get_string('strftimedate', 'langconfig'));
                }
            } 
        }
    } else if ((\local_mutual\front\utils::is_course_streaming($course->id)) || (\local_mutual\front\utils::is_course_presencial($course->id))) {
        $last_sesion = \local_resumencursos\utils\summary_utils::get_last_session_user($user->id, $course->id);
        if (!empty($last_sesion)) {
            $fecha_final =  userdate(strtotime(date("Y-m-d", $last_sesion->sessdate)), get_string('strftimedate', 'langconfig'));
        }
    } else {
        if (!empty($completion)) {
            if (!empty($completion->timecompleted)) {
                if ($completion->timecompleted != "0") {
                    $fecha_final =  userdate($completion->timecompleted, get_string('strftimedate', 'langconfig'));
                }
            }
        }
    }

    $data = [
        'bodypdf' => get_string('textbodypdfcertificate', 'local_download_cert', $stringbody),
        'course' => $course,
        'coursename' => $course->fullname,
        'coursenamecertificate' => $coursenamecertificate,
        'datefinish' => $fecha_final,
        'companyname' => $companyname,
        'grade' => $grade,
        'status' => $status,
        'expirationdate' => $formatexpirationdate,
        'background' => $OUTPUT->image_url('borde-certificado', 'local_download_cert'),
        'username' => $stringbody->name,
        'duration' => $horas,
        'codigo' => $diplomacodigo,
        'empresa' => \local_pubsub\utils::get_company_name($diploma->userid),
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

    $nombre_diploma = 'Diploma-'.$stringbody->name.'.pdf';

    $pdf->Output($nombre_diploma);
