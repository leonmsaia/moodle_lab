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
 * Page for migrating sessions by date range.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use tool_sessionmigrate\form\migrationbydate_form;
use tool_sessionmigrate\sessions;
use tool_sessionmigrate\conn35;

global $PAGE, $CFG, $DB, $USER;
$PAGE->set_context(context_system::instance());
require_admin();
$mform = new migrationbydate_form();

// Form processing and redirection.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/sessionmigrate/pages/migrationbydate.php'));
} else if ($data = $mform->get_data()) {
    $redirecturl = new moodle_url('/admin/tool/sessionmigrate/pages/migrationbydate.php', [
        'startdate' => $data->startdate,
        'enddate' => $data->enddate
    ]);
    redirect($redirecturl);
}

// URL parameters are read after form processing, so they are always from a GET request.
$startdate = optional_param('startdate', 0, PARAM_INT);
$enddate = optional_param('enddate', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHAEXT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$download = optional_param('download', 0, PARAM_BOOL);

$urlparams = [];
if ($startdate) {
    $urlparams['startdate'] = $startdate;
}
if ($enddate) {
    $urlparams['enddate'] = $enddate;
}

$PAGE->set_url(new moodle_url('/admin/tool/sessionmigrate/pages/migrationbydate.php', $urlparams));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('migrationbydate', 'tool_sessionmigrate'));
$PAGE->set_heading(get_string('migrationbydate', 'tool_sessionmigrate'));

/** @var \tool_sessionmigrate\output\renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sessionmigrate');

// Handle download request.
if ($download && $startdate && $enddate) {
    require_once($CFG->libdir . '/csvlib.class.php');
    $connection = new conn35();
    $db35 = $connection->db;
    $info = sessions::get_sessions_info_by_date_range($startdate, $enddate, $db35);

    // Prepare data for CSV export.
    $csvarray = [];
    foreach ($info['courses'] as $course) {
        $csvarray[] = ['shortname' => $course];
    }

    $filename = 'affected_courses_' . userdate($startdate, '%Y%m%d') . '-' . userdate($enddate, '%Y%m%d') . '.csv';
    csv_export_writer::download_array($filename, $csvarray, ['shortname']);
    exit;
}

// Handle actions.
if ($action === 'migrate_sessions_by_date' && $startdate && $enddate) {
    if ($confirm) {
        $logentry = new stdClass();
        $logentry->action = 'migrate_sessions_by_date';
        $logentry->targettype = 'daterange';
        $logentry->targetidentifier = "{$startdate}-{$enddate}";
        $logentry->status = 'created';
        $logentry->userid = $USER->id;
        $logentry->timecreated = time();
        $logentry->timemodified = time();
        $logid = $DB->insert_record('tool_sessionmigrate_log', $logentry);

        // Execute the action CLI script.
        $command = 'php ' . $CFG->dirroot . '/admin/tool/sessionmigrate/cli/migrate_sessions_by_date.php' .
                   ' --startdate=' . escapeshellarg($startdate) .
                   ' --enddate=' . escapeshellarg($enddate) .
                   ' --logid=' . $logid .
                   ' --userid=' . $USER->id;
        exec($command . ' > /dev/null 2>&1 &');

        redirect($PAGE->url, get_string('migratesessionsbydatestarted', 'tool_sessionmigrate'), \core\output\notification::NOTIFY_SUCCESS);
    } else {
        $connection = new conn35();
        $db35 = $connection->db;
        $info = sessions::get_sessions_info_by_date_range($startdate, $enddate, $db35);

        echo $renderer->header();
        echo $renderer->secondary_navigation();
        $confirmurl = new moodle_url($PAGE->url, ['action' => 'migrate_sessions_by_date', 'confirm' => 1]);
        $cancelurl = new moodle_url($PAGE->url);

        $a = new stdClass();
        $a->count = $info['session_count'];
        $a->startdate = userdate($startdate);
        $a->enddate = userdate($enddate);
        $a->courses = implode(', ', $info['courses']);
        if (count($info['courses']) > 10) {
            $a->courses = implode(', ', array_slice($info['courses'], 0, 10)) . '...';
        }
        echo $renderer->confirm(get_string('migratesessionsbydateconfirm', 'tool_sessionmigrate', $a), $confirmurl, $cancelurl);
        echo $renderer->footer();
        die();
    }
}

// Set form data from URL params to display current filter.
if ($startdate && $enddate) {
    $mform->set_data(['startdate' => $startdate, 'enddate' => $enddate]);
}

echo $renderer->header();
echo $renderer->secondary_navigation();

$mform->display();

if ($startdate && $enddate) {
    $connection = new conn35();
    $db35 = $connection->db;
    $info = sessions::get_sessions_info_by_date_range($startdate, $enddate, $db35);

    echo $renderer->heading(format_string($info['session_count']) . ' ' . get_string('sessionsfound', 'tool_sessionmigrate'));

    if ($info['session_count'] > 0) {
        $migrateurl = new moodle_url($PAGE->url, ['action' => 'migrate_sessions_by_date']);
        echo $renderer->single_button($migrateurl, get_string('migratesessionsbydate', 'tool_sessionmigrate'));
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