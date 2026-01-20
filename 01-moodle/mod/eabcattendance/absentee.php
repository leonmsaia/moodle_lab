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
 * Eabcattendance course summary report.
 *
 * @package    mod_eabcattendance
 * @copyright  2017 onwards Dan Marsden http://danmarsden.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/mod/eabcattendance/lib.php');
require_once($CFG->dirroot.'/mod/eabcattendance/locallib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/coursecatlib.php');

$category = optional_param('category', 0, PARAM_INT);
$eabcattendancecm = optional_param('id', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$sort = optional_param('tsort', 'timesent', PARAM_ALPHA);

if (!empty($category)) {
    $context = context_coursecat::instance($category);
    $coursecat = coursecat::get($category);
    $courses = $coursecat->get_courses(array('recursive' => true, 'idonly' => true));
    $PAGE->set_category_by_id($category);
    require_login();
} else if (!empty($eabcattendancecm)) {
    $cm             = get_coursemodule_from_id('eabcattendance', $eabcattendancecm, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $att            = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);
    $courses = array($course->id);
    $context = context_module::instance($cm->id);
    require_login($course, false, $cm);
} else {
    admin_externalpage_setup('managemodules');
    $context = context_system::instance();
    $courses = array(); // Show all courses.
}
// Check permissions.
require_capability('mod/eabcattendance:viewreports', $context);

$exportfilename = 'eabcattendance-absentee.csv';

$PAGE->set_url('/mod/eabcattendance/absentee.php', array('category' => $category, 'id' => $eabcattendancecm));

$PAGE->set_heading($SITE->fullname);

$table = new flexible_table('eabcattendanceabsentee');
$table->define_baseurl($PAGE->url);

if (!$table->is_downloading($download, $exportfilename)) {
    if (!empty($eabcattendancecm)) {
        $pageparams = new mod_eabcattendance_sessions_page_params();
        $att = new mod_eabcattendance_structure($att, $cm, $course, $context, $pageparams);
        $output = $PAGE->get_renderer('mod_eabcattendance');
        $tabs = new eabcattendance_tabs($att, eabcattendance_tabs::TAB_ABSENTEE);
        echo $output->header();
        echo $output->heading(get_string('eabcattendanceforthecourse', 'eabcattendance').' :: ' .format_string($course->fullname));
        echo $output->render($tabs);
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('absenteereport', 'mod_eabcattendance'));
        if (empty($category)) {
            // Only show tabs if displaying via the admin page.
            $tabmenu = eabcattendance_print_settings_tabs('absentee');
            echo $tabmenu;
        }
    }

}

$table->define_columns(array('coursename', 'aname', 'userid', 'numtakensessions', 'percent', 'timesent'));
$table->define_headers(array(get_string('course'),
    get_string('pluginname', 'eabcattendance'),
    get_string('user'),
    get_string('takensessions', 'eabcattendance'),
    get_string('averageeabcattendance', 'eabcattendance'),
    get_string('triggered', 'eabcattendance')));
$table->sortable(true);
$table->set_attribute('cellspacing', '0');
$table->set_attribute('class', 'generaltable generalbox');
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->setup();

// Work out direction of sort required.
$sortcolumns = $table->get_sort_columns();
// Now do sorting if specified.

$orderby = ' ORDER BY percent ASC';
if (!empty($sort)) {
    $direction = ' DESC';
    if (!empty($sortcolumns[$sort]) && $sortcolumns[$sort] == SORT_ASC) {
        $direction = ' ASC';
    }
    $orderby = " ORDER BY $sort $direction";

}

$records = eabcattendance_get_users_to_notify($courses, $orderby);
foreach ($records as $record) {
    if (!$table->is_downloading($download, $exportfilename)) {
        $url = new moodle_url('/mod/eabcattendance/index.php', array('id' => $record->courseid));
        $name = html_writer::link($url, $record->coursename);
    } else {
        $name = $record->coursename;
    }
    $url = new moodle_url('/mod/eabcattendance/view.php', array('studentid' => $record->userid,
                                                                'id' => $record->cmid, 'view' => EABCATT_VIEW_ALL));
    $eabcattendancename = html_writer::link($url, $record->aname);

    $username = html_writer::link($url, fullname($record));
    $percent = round($record->percent * 100)."%";
    $timesent = "-";
    if (!empty($record->timesent)) {
        $timesent = userdate($record->timesent);
    }

    $table->add_data(array($name, $eabcattendancename, $username, $record->numtakensessions, $percent, $timesent));
}
$table->finish_output();

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}