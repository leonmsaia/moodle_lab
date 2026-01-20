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
 * Prints eabcattendance info for particular user
 *
 * @package    mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$pageparams = new mod_eabcattendance_view_page_params();

$id                     = required_param('id', PARAM_INT);
$pageparams->studentid  = optional_param('studentid', null, PARAM_INT);
$pageparams->mode       = optional_param('mode', mod_eabcattendance_view_page_params::MODE_THIS_COURSE, PARAM_INT);
$pageparams->view       = optional_param('view', null, PARAM_INT);
$pageparams->curdate    = optional_param('curdate', null, PARAM_INT);

$cm             = get_coursemodule_from_id('eabcattendance', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$eabcattendance    = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/eabcattendance:view', $context);

$pageparams->init($cm);
$att = new mod_eabcattendance_structure($eabcattendance, $cm, $course, $context, $pageparams);

// Not specified studentid for displaying eabcattendance?
// Redirect to appropriate page if can.
if (!$pageparams->studentid) {
    $capabilities = array(
        'mod/eabcattendance:manageeabcattendances',
        'mod/eabcattendance:takeeabcattendances',
        'mod/eabcattendance:changeeabcattendances'
    );
    if (has_any_capability($capabilities, $context)) {
        redirect($att->url_manage());
    } else if (has_capability('mod/eabcattendance:viewreports', $context)) {
        redirect($att->url_report());
    }
}

$PAGE->set_url($att->url_view());
$PAGE->set_title($course->shortname. ": ".$att->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_cacheable(true);
$PAGE->navbar->add(get_string('eabcattendancereport', 'eabcattendance'));

$output = $PAGE->get_renderer('mod_eabcattendance');

if (isset($pageparams->studentid) && $USER->id != $pageparams->studentid) {
    // Only users with proper permissions should be able to see any user's individual report.
    require_capability('mod/eabcattendance:viewreports', $context);
    $userid = $pageparams->studentid;
} else {
    // A valid request to see another users report has not been sent, show the user's own.
    $userid = $USER->id;
}

$userdata = new eabcattendance_user_data($att, $userid);
$header = new mod_eabcattendance_header($att);

echo $output->header();

echo $output->render($header);
echo $output->render($userdata);

echo $output->footer();
