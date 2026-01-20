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
    $certificado = $DB->get_record('local_certificados', ['id' => $programa_usuario->certificadoid]);

    $cursos = $programa_usuario->cursos;
    $listcursos = explode(':', $cursos);
    $courses = [];
    if ($listcursos) {
        foreach ($listcursos as $key => $value) {
            $curso = explode(',', $value);
            if ($curso) {
                $elem = new stdClass();
                $elem->name = $curso[0];
                $elem->calificacion = $curso[1];
                $elem->asistencia = $curso[3];
                $elem->nota = utils::calc_nota($curso[1], $curso[2]);
                $elem->fincurso = date('d-m-Y', $curso[4]);

                $courses[] = $elem;
            }
        }
    }

    $programa_usuario->courses = $courses;
    $programa_usuario->fin_primercurso = date('d-m-Y', $programa_usuario->end_firstcourse);
    $programa_usuario->expiracion = date('d-m-Y', $programa_usuario->fecha_vencimiento);

    class Certificado extends TCPDF
    {
        public $imgback;
        public $expiracion;
        public $codigo;

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
            $this->SetY(-40);
            $this->SetFont('helvetica', 'I', 12);
            $this->Cell(0, 11, $this->expiracion, 0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->Cell(0, 11, $this->codigo, 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 12);
            $this->Cell(0, 10, 'Vigencia de la actividad: 3 años a contar de la fecha de finalización del primer curso ', 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }
    // create new PDF document
    $pdf = new Certificado(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $img = $certificado->urlfile;
    $pdf->setImgback($img);
    $pdf->expiracion = 'FECHA EXPIRACIÓN: ' . $programa_usuario->expiracion;
    $pdf->codigo = 'Código: ' . $programa_usuario->codigo_certificado;

    // $imgtemp = $certificado->urltemp;
    // $programa_usuario->img = $img;

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('e-ABC');
    $pdf->SetTitle($programa_usuario->description);
    $pdf->SetSubject('Certificado');
    $pdf->SetKeywords('programa, certificaco, diploma, cursos');

    $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));

    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    $pdf->SetMargins(20, PDF_MARGIN_TOP, 20);
    $pdf->SetHeaderMargin(1);
    $pdf->SetTopMargin(40);
    $pdf->SetFooterMargin(5);

    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    $pdf->SetFont('times', '', 12);

    $pdf->AddPage();

    // $txt = 'Musa Majluf A.
    // Subgerente Capacitación
    // Gerencia Gestión del conocimiento
    // Gerencia Corporativa SST';

    // Print a text
    $html = $OUTPUT->render_from_template("local_eabcprogramas/certificado", array('program' => $programa_usuario, 'courses' => $cursos));

    $pdf->writeHTML($html, true, false, true, false, '');
    // Multicell test
    // $pdf->MultiCell(160, 5, $txt, 0, 'C', 0, 1, '', '210', true);
    // $pdf->Ln(10);

    // $pdf->Cell(0, 30, 'FECHA EXPIRACIÓN: ' . $programa_usuario->expiracion, 0, false, 'L', 0, '', 0, false, 'M', 'M');
    // $pdf->Cell(0, 30, 'Código: ' . $programa_usuario->codigo_certificado, 0, false, 'R', 0, '', 0, false, 'M', 'M');


    //Close and output PDF document
    $pdf->Output('Certificado_de_proograma_' . $programa_usuario->description . '_' . date('d-m-Y', time()) . '.pdf', 'I');
} catch (coding_exception $e) {
    throw new moodle_exception('errormsg', 'local_eabcprogramas', '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception('errormsg', 'local_eabcprogramas', '', $e->getMessage(), $e->debuginfo);
}
