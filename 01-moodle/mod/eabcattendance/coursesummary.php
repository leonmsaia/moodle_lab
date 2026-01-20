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
$download = optional_param('download', '', PARAM_ALPHA);
$sort = optional_param('tsort', '', PARAM_ALPHA);
$fromcourse = optional_param('fromcourse', 0, PARAM_INT);

$admin = false;
if (empty($fromcourse)) {
    $admin = true;
    admin_externalpage_setup('managemodules');
} else {
    require_login($fromcourse);
}

if (empty($category)) {
    $context = context_system::instance();
    $courses = array(); // Show all courses.
} else {
    $context = context_coursecat::instance($category);
    $coursecat = coursecat::get($category);
    $courses = $coursecat->get_courses(array('recursive' => true, 'idonly' => true));
}
// Check permissions.
require_capability('mod/eabcattendance:viewsummaryreports', $context);

$exportfilename = 'eabcattendancecoursesummary.csv';

$PAGE->set_url('/mod/eabcattendance/coursesummary.php', array('category' => $category));

$PAGE->set_heading($SITE->fullname);

$table = new flexible_table('eabcattendancecoursesummary');
$table->define_baseurl($PAGE->url);

if (!$table->is_downloading($download, $exportfilename)) {
    echo $OUTPUT->header();
    $heading = get_string('coursesummary', 'mod_eabcattendance');
    if (!empty($category)) {
        $heading .= " (".$coursecat->name.")";
    }
    echo $OUTPUT->heading($heading);
    if ($admin) {
        // Only show tabs if displaying via the admin page.
        $tabmenu = eabcattendance_print_settings_tabs('coursesummary');
        echo $tabmenu;
    }
    $url = new moodle_url('/mod/eabcattendance/coursesummary.php', array('category' => $category, 'fromcourse' => $fromcourse));

    if ($admin) {
        $options = coursecat::make_categories_list('mod/eabcattendance:viewsummaryreports');
        echo $OUTPUT->single_select($url, 'category', $options, $category);
    }

}

$table->define_columns(array('course', 'percentage'));
$table->define_headers(array(get_string('course'),
    get_string('averageeabcattendance', 'eabcattendance')));
$table->sortable(true);
$table->no_sorting('course');
$table->set_attribute('cellspacing', '0');
$table->set_attribute('class', 'generaltable generalbox');
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->setup();

// Work out direction of sort required.
$sortcolumns = $table->get_sort_columns();
// Now do sorting if specified.

$orderby = ' ORDER BY percentage ASC';
if (!empty($sort)) {
    $direction = ' DESC';
    if (!empty($sortcolumns[$sort]) && $sortcolumns[$sort] == SORT_ASC) {
        $direction = ' ASC';
    }
    $orderby = " ORDER BY $sort $direction";

}

$records = eabcattendance_course_users_points($courses, $orderby);
foreach ($records as $record) {
    if (!$table->is_downloading($download, $exportfilename)) {
        $url = new moodle_url('/mod/eabcattendance/index.php', array('id' => $record->courseid));
        $name = html_writer::link($url, $record->coursename);
    } else {
        $name = $record->coursename;
    }
    $table->add_data(array($name, round($record->percentage * 100)."%"));
}
$table->finish_output();

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}