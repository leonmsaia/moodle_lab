<?php
namespace local_resumencursos;
use moodle_url;
require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->libdir . '/tcpdf/tcpdf.php');

//class download_certificate extends \TCPDF {
class download_certificate extends \assignfeedback_editpdf\pdf {
    public function Header() {
        $image_file = 'pix/logocertificate.png';
        $this->Image($image_file,  15, 7, 47, '', 'PNG', true, 'C', true, 100, '',false,false);
    }
    public function Footer() {
    }
}