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
 * Page for migrating sessions by course and date range.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use tool_sessionmigrate\form\migrationbycourseanddate_form;
use tool_sessionmigrate\sessions;
use tool_sessionmigrate\conn35;

global $PAGE, $CFG, $DB, $USER;
require_admin();
$PAGE->set_context(context_system::instance());

$mform = new migrationbycourseanddate_form();

// Form processing and redirection.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/sessionmigrate/pages/migrationbycourseanddate.php'));
} else if ($data = $mform->get_data()) {
    $redirecturl = new moodle_url('/admin/tool/sessionmigrate/pages/migrationbycourseanddate.php', [
        'shortname' => $data->shortname,
        'startdate' => $data->startdate,
        'enddate' => $data->enddate
    ]);
    redirect($redirecturl);
}

// URL parameters.
$shortname = optional_param('shortname', '', PARAM_TEXT);
$startdate = optional_param('startdate', 0, PARAM_INT);
$enddate = optional_param('enddate', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHAEXT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$download = optional_param('download', 0, PARAM_BOOL);

$urlparams = [];
if ($shortname) {
    $urlparams['shortname'] = $shortname;
}
if ($startdate) {
    $urlparams['startdate'] = $startdate;
}
if ($enddate) {
    $urlparams['enddate'] = $enddate;
}

$PAGE->set_url(new moodle_url('/admin/tool/sessionmigrate/pages/migrationbycourseanddate.php', $urlparams));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('migrationbycourseanddate', 'tool_sessionmigrate'));
$PAGE->set_heading(get_string('migrationbycourseanddate', 'tool_sessionmigrate'));

/** @var \tool_sessionmigrate\output\renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sessionmigrate');

// Validar que todos los campos obligatorios estén presentes.
$has_required_params = ($shortname !== '' && $startdate && $enddate);

// Handle download request.
if ($download && $has_required_params) {
    require_once($CFG->libdir . '/csvlib.class.php');
    $connection = new conn35();
    $db35 = $connection->db;
    
    // Usamos la nueva función específica.
    $info = sessions::get_sessions_info_by_course_and_date_range($shortname, $startdate, $enddate, $db35);

    $csvarray = [];
    foreach ($info['courses'] as $course) {
        $csvarray[] = ['shortname' => $course];
    }

    $filename = 'affected_course_' . clean_filename($shortname) . '_' . userdate($startdate, '%Y%m%d') . '-' . userdate($enddate, '%Y%m%d') . '.csv';
    csv_export_writer::download_array($filename, $csvarray, ['shortname']);
    exit;
}

// Handle actions.
if ($action === 'migrate_sessions_by_course_and_date' && $has_required_params) {
    if ($confirm) {
        $logentry = new stdClass();
        $logentry->action = 'migrate_sessions_by_course_and_date';
        $logentry->targettype = 'course_daterange';
        $logentry->targetidentifier = "{$shortname}-{$startdate}-{$enddate}";
        $logentry->status = 'created';
        $logentry->userid = $USER->id;
        $logentry->timecreated = time();
        $logentry->timemodified = time();
        $logid = $DB->insert_record('tool_sessionmigrate_log', $logentry);

        // Execute the NEW CLI script.
        $command = 'php ' . $CFG->dirroot . '/admin/tool/sessionmigrate/cli/migrate_sessions_by_course_and_date.php' .
                   ' --shortname=' . escapeshellarg($shortname) .
                   ' --startdate=' . escapeshellarg($startdate) .
                   ' --enddate=' . escapeshellarg($enddate) .
                   ' --logid=' . $logid .
                   ' --userid=' . $USER->id;
        exec($command . ' > /dev/null 2>&1 &');

        redirect($PAGE->url, get_string('migratesessionsbycourseanddatestarted', 'tool_sessionmigrate'), \core\output\notification::NOTIFY_SUCCESS);
    } else {
        $connection = new conn35();
        $db35 = $connection->db;
        $info = sessions::get_sessions_info_by_course_and_date_range($shortname, $startdate, $enddate, $db35);

        echo $renderer->header();
        echo $renderer->secondary_navigation();
        $confirmurl = new moodle_url($PAGE->url, ['action' => 'migrate_sessions_by_course_and_date', 'confirm' => 1]);
        $cancelurl = new moodle_url($PAGE->url);

        $a = new stdClass();
        $a->count = $info['session_count'];
        $a->shortname = s($shortname);
        $a->startdate = userdate($startdate);
        $a->enddate = userdate($enddate);
        $a->courses = implode(', ', $info['courses']);
        
        echo $renderer->confirm(get_string('migratesessionsbycourseanddateconfirm', 'tool_sessionmigrate', $a), $confirmurl, $cancelurl);
        echo $renderer->footer();
        die();
    }
}

// Set form data.
if ($has_required_params) {
    $mform->set_data(['shortname' => $shortname, 'startdate' => $startdate, 'enddate' => $enddate]);
}

echo $renderer->header();
echo $renderer->secondary_navigation();

$mform->display();

if ($has_required_params) {
    $connection = new conn35();
    $db35 = $connection->db;
    $info = sessions::get_sessions_info_by_course_and_date_range($shortname, $startdate, $enddate, $db35);

    echo $renderer->heading(format_string($info['session_count']) . ' ' . get_string('sessionsfound', 'tool_sessionmigrate'));

    if ($info['session_count'] > 0) {
        $migrateurl = new moodle_url($PAGE->url, ['action' => 'migrate_sessions_by_course_and_date']);
        echo $renderer->single_button($migrateurl, get_string('migratesessionsbycourseanddate', 'tool_sessionmigrate'));
    }

    echo $renderer->heading(get_string('coursesaffected', 'tool_sessionmigrate'), 4);
    if (!empty($info['courses'])) {
        $downloadurl = new moodle_url($PAGE->url, ['download' => 1]);
        echo $renderer->single_button($downloadurl, get_string('downloadcourses', 'tool_sessionmigrate'));

        $table = new html_table();
        $table->head = [get_string('shortname', 'tool_sessionmigrate')];
        $table->data = [];
        foreach ($info['courses'] as $course) {
            $table->data[] = [$course];
        }
        echo html_writer::table($table);
    } else {
        echo $renderer->notification(get_string('nocoursesfound', 'tool_sessionmigrate'));
    }
}

echo $renderer->footer();