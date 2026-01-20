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
 * Adding eabcattendance sessions
 *
 * @package    mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/add_form.php');
require_once(dirname(__FILE__) . '/update_form.php');
require_once(dirname(__FILE__) . '/duration_form.php');

use mod_eabcattendance\metodos_comunes;
use mod_eabcattendance\download_nomina_pdf;

$pageparams = new mod_eabcattendance_sessions_page_params();

$id = required_param('id', PARAM_INT);
$pageparams->action = required_param('action', PARAM_INT);

if (optional_param('deletehiddensessions', false, PARAM_TEXT)) {
    $pageparams->action = mod_eabcattendance_sessions_page_params::ACTION_DELETE_HIDDEN;
}

if (empty($pageparams->action)) {
    // The form on manage.php can submit with the "choose" option - this should be fixed in the long term,
    // but in the meantime show a useful error and redirect when it occurs.
    $url = new moodle_url('/mod/eabcattendance/view.php', array('id' => $id));
    redirect($url, get_string('invalidaction', 'mod_eabcattendance'), 2);
}

$cm = get_coursemodule_from_id('eabcattendance', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$att = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/eabcattendance:manageeabcattendances', $context);

$att = new mod_eabcattendance_structure($att, $cm, $course, $context, $pageparams);

$PAGE->set_url($att->url_sessions(array('action' => $pageparams->action)));
$PAGE->set_title($course->shortname . ": " . $att->name);
$PAGE->set_heading($course->fullname);
$PAGE->force_settings_menu(true);
$PAGE->set_cacheable(true);
$PAGE->navbar->add($att->name);

//echo print_r($att->course, true);exit;
$currenttab = eabcattendance_tabs::TAB_ADD;
$formparams = array('course' => $course, 'cm' => $cm, 'modcontext' => $context, 'att' => $att);
switch ($att->pageparams->action) {
    case mod_eabcattendance_sessions_page_params::DOWNLOAD_NOMINA:

        require_once("$CFG->libdir/excellib.class.php");

        $sessionid = required_param('sessionid', PARAM_INT);
        $abierto = get_config('local_pubsub', 'approvedstatus');
        $url = $att->url_sessions(array('action' => mod_eabcattendance_sessions_page_params::DOWNLOAD_NOMINA, 'sessionid' => $sessionid));

        $datas = metodos_comunes::get_data_download($sessionid, $abierto, $att->course, 'csv');

        array_pop($datas['headerbody']);
        array_pop($datas['bodytable']);

        $filename = get_string('downloadnominaexcel', 'eabcattendance').'_'.(time());

        $downloadfilename = clean_filename($filename);
        /// Creating a workbook
        $workbook = new MoodleExcelWorkbook("-");
        /// Sending HTTP headers
        $workbook->send($downloadfilename);
        /// Adding the worksheet
        $myxls = $workbook->add_worksheet($filename);

        $row = 0;
        foreach ($datas["head"] as $items) {
            $col=0;
            foreach ($items as $key => $item) {
                $myxls->write_string($row,$col++,$item);
            }
            $row++;
        }
        //espacio
        $row++;
        //header table
        $col=0;
        foreach ($datas["headerbody"] as $headerbody) {
            $myxls->write_string($row,$col++,$headerbody);
        }
        $row++;
        //content table
        
        foreach ($datas["bodytable"] as $tablebodies) {
            $col=0;
            foreach ($tablebodies as $tablebodie) {
                $myxls->write_string($row,$col++,$tablebodie);
            }
            $row++;
        }

        $workbook->close();
        exit;

        $currenttab = eabcattendance_tabs::TAB_DOWNLOAD_NOMINA;
        break;
    case mod_eabcattendance_sessions_page_params::DOWNLOAD_NOMINA_PDF:
        $sessionid = required_param('sessionid', PARAM_INT);
        
        $url = $att->url_sessions(array('action' => mod_eabcattendance_sessions_page_params::DOWNLOAD_NOMINA_PDF, 'sessionid' => $sessionid));
        
        $abierto = get_config('local_pubsub', 'approvedstatus');
        $datas = metodos_comunes::get_data_download($sessionid, $abierto, $att->course);

        
        metodos_comunes::pdf_attendance($datas);
        
        exit;
        $currenttab = eabcattendance_tabs::TAB_DOWNLOAD_NOMINA_PDF;
        break;
}

$output = $PAGE->get_renderer('mod_eabcattendance');
$tabs = new eabcattendance_tabs($att, $currenttab);
echo $output->header();
echo $output->heading(get_string('eabcattendanceforthecourse', 'eabcattendance') . ' :: ' . format_string($course->fullname));
echo $output->render($tabs);

//if(!empty($mform)){
//    $mform->display();
//}

echo $OUTPUT->footer();
