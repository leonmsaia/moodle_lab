<?php

use local_eabcprogramas\utils;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/pdflib.php');
require_login();
$cursosid = optional_param('cursos', '', PARAM_TEXT);
$programaid = optional_param('programa', '', PARAM_INT);
$PAGE->set_context(context_system::instance());

$programa = $DB->get_record('local_eabcprogramas', ['id' => $programaid]);
global $OUTPUT;

try {
    class Cursos extends TCPDF
    {
        public $imgback;
        public function setImgback($img)
        {
            $this->imgback = $img;
        }
        //Page header
        public function Header()
        {
            global $CFG;
            $bMargin = $this->getBreakMargin();
            $auto_page_break = $this->AutoPageBreak;
            $this->SetAutoPageBreak(false, 0);
            $img_file = $this->imgback;
            $this->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
            $this->SetAutoPageBreak($auto_page_break, $bMargin);
            $this->setPageMark();
        }
        // Page footer
        public function Footer()
        {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 12);
        }
    }
    $pdf = new Cursos(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $certificado = $DB->get_record('local_certificados', ['status' => 1]);
    // $img = $CFG->wwwroot . '/local/eabcprogramas/pix/bgcursos.png';
    $img = $certificado->urlfile;
    $pdf->setImgback($img);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('e-ABC');
    $pdf->SetTitle($programa->description);
    $pdf->SetSubject('Lista de Cursos');
    $pdf->SetKeywords('programa, certificaco, diploma, cursos');

    $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    $pdf->SetMargins(30, PDF_MARGIN_TOP, 30);
    $pdf->SetHeaderMargin(1);
    $pdf->SetTopMargin(40);
    $pdf->SetFooterMargin(5);

    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    $pdf->SetFont('times', '', 12);

    $pdf->AddPage();

    $cursosid = explode(',', $cursosid);

    $courses = utils::get_courses_by_ids($cursosid);

    $table = $OUTPUT->render_from_template("local_eabcprogramas/lista_cursos", array('courses' => $courses, 'fecha' => date('d-m-Y', $programa->fromdate), 'programa' => $programa));
    $font_size = $pdf->pixelsToUnits('30');

    $pdf->WriteHTML($table, true, 0, true, 0);
    $pdf->lastPage();
    $pdf->Output('Cursos de ' . $programa->description . ' ' . date('d-m-Y', time()) . '.pdf', 'I');
} catch (coding_exception $e) {
    throw new moodle_exception('errormsg', 'local_eabcprogramas', '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception('errormsg', 'local_eabcprogramas', '', $e->getMessage(), $e->debuginfo);
}
