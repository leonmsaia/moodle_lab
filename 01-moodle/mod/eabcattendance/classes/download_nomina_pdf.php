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

/**
 * Event observers supported by this module
 *
 * @package    mod_eabcattendance
 * @copyright  2017 Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_eabcattendance;

use moodle_url;
require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->libdir . '/tcpdf/tcpdf.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers supported by this module
 *
 * @package    mod_eabcattendance
 * @copyright  2017 Dan Marsden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class download_nomina_pdf extends \TCPDF {
    public function Header() {
        $image_file = 'pix/logocertificate.png';
        $this->Image($image_file,  15, 7, 47, '', 'PNG', true, 'C', true, 100, '',false,false);
    }
    
    public function Footer(){
//        parent::footer();
//        $this->SetFont('helvetica', '', 8);
//        $this->writeHTML('<div style="background-color:#06498A;"><div style="text-align:center"; padding: 15px>CAD Perú | Comprometidos con el desarrollo organizacional.</div></div>', false, true, false, true); 
    }
}
