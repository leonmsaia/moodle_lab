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
 * Eabcattendance report
 *
 * @package    mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$pageparams = new mod_eabcattendance_report_page_params();

$id                     = required_param('id', PARAM_INT);
$from                   = optional_param('from', null, PARAM_ACTION);
$pageparams->view       = optional_param('view', null, PARAM_INT);
$pageparams->curdate    = optional_param('curdate', null, PARAM_INT);
$pageparams->group      = optional_param('group', null, PARAM_INT);
$pageparams->sort       = optional_param('sort', EABCATT_SORT_DEFAULT, PARAM_INT);
$pageparams->page       = optional_param('page', 1, PARAM_INT);
$pageparams->perpage    = get_config('eabcattendance', 'resultsperpage');

$cm             = get_coursemodule_from_id('eabcattendance', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$attrecord = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/eabcattendance:viewreports', $context);

$pageparams->init($cm);
$pageparams->showextrauserdetails = optional_param('showextrauserdetails', $attrecord->showextrauserdetails, PARAM_INT);
$pageparams->showsessiondetails = optional_param('showsessiondetails', $attrecord->showsessiondetails, PARAM_INT);
$pageparams->sessiondetailspos = optional_param('sessiondetailspos', $attrecord->sessiondetailspos, PARAM_TEXT);

$att = new mod_eabcattendance_structure($attrecord, $cm, $course, $context, $pageparams);

$PAGE->set_url($att->url_report());
$PAGE->set_pagelayout('report');
$PAGE->set_title($course->shortname. ": ".$att->name.' - '.get_string('report', 'eabcattendance'));
$PAGE->set_heading($course->fullname);
$PAGE->force_settings_menu(true);
$PAGE->set_cacheable(true);
$PAGE->navbar->add(get_string('report', 'eabcattendance'));

$output = $PAGE->get_renderer('mod_eabcattendance');
$tabs = new eabcattendance_tabs($att, eabcattendance_tabs::TAB_REPORT);
$filtercontrols = new eabcattendance_filter_controls($att, true);
$reportdata = new eabcattendance_report_data($att);

// Trigger a report viewed event.
$event = \mod_eabcattendance\event\report_viewed::create(array(
    'objectid' => $att->id,
    'context' => $PAGE->context,
    'other' => array()
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('eabcattendance', $attrecord);
$event->trigger();

$title = get_string('eabcattendanceforthecourse', 'eabcattendance').' :: ' .format_string($course->fullname);
$header = new mod_eabcattendance_header($att, $title);

// Output starts here.
echo $output->header();
echo $output->render($header);
echo $output->render($tabs);
echo $output->render($filtercontrols);
echo $output->render($reportdata);
echo $output->footer();

