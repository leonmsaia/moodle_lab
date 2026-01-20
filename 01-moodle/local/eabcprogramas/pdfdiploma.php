<?php

use local_eabcprogramas\utils;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/pdflib.php');
require_login();
$id = optional_param('id', 0, PARAM_INT);
$PAGE->set_context(context_system::instance());

global $OUTPUT, $CFG;
try {
    $programa_usuario = $DB->get_record('local_eabcprogramas_usuarios', ['id' => $id]);
    $programa_usuario->expiracion = date('d-m-Y', $programa_usuario->fecha_vencimiento);
    $diploma = $DB->get_record('local_diplomas', ['id' => $programa_usuario->diplomaid]);

    class diploma extends TCPDF
    {
        protected $imgback;
        public $pie;
        public $expiracion;
        public $codigo;
        public $logodiploma;

        public function setImgback($img)
        {
            $this->imgback = $img;
        }
        public function getImgback()
        {
            return $this->imgback;
        }
        //Page header
        public function Header()
        {
            global $CFG;
            $bMargin = $this->getBreakMargin();
            $auto_page_break = $this->AutoPageBreak;
            $this->SetAutoPageBreak(false, 0);
            $img_file = $this->imgback;
            $this->Image($img_file, 0, 0, 310, 190, '', '', '', false, 300, '', false, false, 0);
            $this->SetAutoPageBreak($auto_page_break, $bMargin);
            $this->setPageMark();
        }
        // Page footer
        public function Footer()
        {
            $this->SetY(-30);
            $this->SetFont('helvetica', 'I', 12);
            $this->Cell(0, 10, $this->pie, 0, false, 'C', 0, '', 0, false, 'T', 'M');
            $this->SetY(-15);
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 11, $this->expiracion, 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->Cell(0, 11, $this->codigo, 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
    }

    $img = $diploma->urlfile;

    // $imgtemp = $diploma->urltemp;
    $programa_usuario->img = $img;

    // create new PDF document
    $pdf = new diploma('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->setImgback($img);
    $pdf->pie = 'Este programa se compone de una serie de cursos con una duración total de: ' . $programa_usuario->horas . ' horas pedagógicas';
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('e-ABC');
    $pdf->SetTitle($programa_usuario->description);
    $pdf->SetSubject('diploma');
    $pdf->SetKeywords('programa, certificaco, diploma, cursos');

    $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    $pdf->SetMargins(30, PDF_MARGIN_TOP, 30);
    $pdf->SetHeaderMargin(1);
    $pdf->SetTopMargin(50);
    $pdf->SetFooterMargin(0);

    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    $pdf->AddPage();

    $pdf->SetFont('times', '', 25);

    // $html = $OUTPUT->render_from_template("local_eabcprogramas/diploma", array('program' => $programa_usuario));
    // $pdf->writeHTML($html, true, false, true, false, 'C');
    $pdf->Cell(0, 10, ' DIPLOMA PARA PROGRAMA', 0, false, 'C', 0, '', 0, false, 'M', 'M');

    $pdf->Ln(10);
    $pdf->SetFont('times', '', 20);
    $pdf->writeHTML('<p style="text-transform: uppercase;"><u>' . $programa_usuario->description . '</u></p>', true, false, true, false, 'C');

    $pdf->Ln(5);
    $pdf->SetFont('times', '', 18);
    $pdf->writeHTML('Se otorga el siguiente diploma a:', true, false, true, false, 'C');

    $pdf->Ln(2);
    $pdf->SetFont('times', '', 15);
    $pdf->writeHTML('<p style="border-bottom-width: 0.3em; font-size: 2em;">' . $programa_usuario->usuario . '</p>',  true, false, true, false, 'C');

    $pdf->Ln(5);
    $pdf->SetFont('times', '', 18);
    $pdf->writeHTML('Empresa:', true, false, true, false, 'C');

    $pdf->Ln(7);
    $pdf->SetFont('times', '', 10);
    $pdf->writeHTML('<p style="border-bottom-width: 0.3em; font-size: 2em;">' . $programa_usuario->empresa . '</p>',  true, false, true, false, 'C');

    // $pdf->Ln(40);
    // $pdf->SetFont('times', '', 12);
    // $pdf->writeHTML('<p>Este programa se compone de una serie de cursos con una duración total de: ' . $programa_usuario->horas . ' horas pedagógicas</p>',  true, false, true, false, 'C');

    $expiracion = 'FECHA EXPIRACIÓN: ' . $programa_usuario->expiracion;
    $pdf->expiracion = $expiracion;
    $codigo = 'Código: ' . $programa_usuario->codigo_diploma;
    $pdf->codigo = $codigo;
    // $pdf->Cell(0, 10, 'FECHA EXPIRACIÓN: ' . $programa_usuario->expiracion, 0, false, 'L', 0, '', 0, false, 'T', 'M');
    // $pdf->Cell(0, 10, 'Código: ' . $programa_usuario->codigo_diploma, 0, false, 'R', 0, '', 0, false, 'T', 'M');

    //Close and output PDF document
    $pdf->Output('Diploma_de_proograma_' . $programa_usuario->description . '_' . date('d-m-Y', time()) . '.pdf', 'I');
} catch (coding_exception $e) {
    throw new moodle_exception('errormsg', 'local_eabcprogramas', '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception('errormsg', 'local_eabcprogramas', '', $e->getMessage(), $e->debuginfo);
}
