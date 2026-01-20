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
 * Queue view: courses with groups closed in 3.5 but open in 4.5.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once (__DIR__ . './../../../../config.php');

global $PAGE, $CFG, $DB, $USER;

require_once($CFG->libdir.'/adminlib.php');

require_admin();

// Action definitions (reuse sync action).
$actions = [
    'sync_closed_sessions' => [
        'confirm_string' => 'syncsessionsconfirm',
        'started_string' => 'syncsessionsstarted',
        'button_string' => 'syncsessions',
        'cli_script' => 'sync_closed_sessions.php',
        'icon' => 'i/switch',
        'target_type' => 'productoid',
    ],
];

// URL parameters.
$page = optional_param('page', 0, PARAM_INT);
$perpage = 10;
$productoid = optional_param('productoid', '', PARAM_TEXT);
$shortname = optional_param('shortname', '', PARAM_TEXT);
$action = optional_param('action', '', PARAM_ALPHAEXT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$target_identifier = optional_param('target_identifier', '', PARAM_TEXT);

$urlparams = [];
if (!empty($productoid)) {
    $urlparams['productoid'] = $productoid;
}
if (!empty($shortname)) {
    $urlparams['shortname'] = $shortname;
}

$PAGE->set_url(new moodle_url('/admin/tool/sessionmigrate/pages/closedgroupsqueue.php', $urlparams));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('closedgroupsqueue', 'tool_sessionmigrate'));
$PAGE->set_heading(get_string('closedgroupsqueue', 'tool_sessionmigrate'));

/** @var \tool_sessionmigrate\output\renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sessionmigrate');

// Handle actions.
if (!empty($action) && !empty($target_identifier) && isset($actions[$action])) {
    $currentaction = $actions[$action];

    if ($confirm) {
        // Create a log entry.
        $logentry = new stdClass();
        $logentry->action = $action;
        $logentry->targettype = $currentaction['target_type'];
        $logentry->targetidentifier = $target_identifier;
        $logentry->status = 'created';
        $logentry->userid = $USER->id;
        $logentry->timecreated = time();
        $logentry->timemodified = time();
        $logid = $DB->insert_record('tool_sessionmigrate_log', $logentry);

        // Execute the action CLI script.
        $command = 'php ' . $CFG->dirroot . '/admin/tool/sessionmigrate/cli/' . $currentaction['cli_script'] .
                   ' --targettype=' . escapeshellarg($currentaction['target_type']) .
                   ' --targetidentifier=' . escapeshellarg($target_identifier) .
                   ' --logid=' . $logid .
                   ' --userid=' . $USER->id;
        exec($command . ' > /dev/null 2>&1 &');

        redirect($PAGE->url, get_string($currentaction['started_string'], 'tool_sessionmigrate'), \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Show confirmation page.
        echo $renderer->header();
        echo $renderer->secondary_navigation();
        $confirmurl = new moodle_url($PAGE->url, ['action' => $action, 'confirm' => 1, 'target_identifier' => $target_identifier]);
        $cancelurl = new moodle_url($PAGE->url);
        echo $renderer->confirm(get_string($currentaction['confirm_string'], 'tool_sessionmigrate'), $confirmurl, $cancelurl);
        echo $renderer->footer();
        die();
    }
}

$mform = new \tool_sessionmigrate\form\coursearch_form(null, ['productoid' => $productoid, 'shortname' => $shortname]);
$mform->set_data(['productoid' => $productoid, 'shortname' => $shortname]);

if ($mform->is_cancelled()) {
    redirect($PAGE->url);
} else if ($data = $mform->get_data()) {
    $redirecturl = new moodle_url($PAGE->url, ['productoid' => $data->productoid, 'shortname' => $data->shortname]);
    redirect($redirecturl);
}

echo $renderer->header();
echo $renderer->secondary_navigation();

$mform->display();

$connection = new \tool_sessionmigrate\conn35();
$db35 = $connection->db;

// Base FROM with courses that have at least one closed group in 3.5.
$sqlfrom = "FROM {course} c
    JOIN {curso_back} cb ON cb.id_curso_moodle = c.id
    JOIN {eabcattendance} ea ON ea.course = c.id
    JOIN {eabcattendance_sessions} eas ON eas.eabcattendanceid = ea.id
    JOIN {format_eabctiles_closegroup} cg ON cg.groupid = eas.groupid AND cg.status = 1
    WHERE 1=1";

$params = [];
if (!empty($productoid)) {
    $likeproductoid = $db35->sql_like('cb.productoid', ':productoid', false);
    $sqlfrom .= " AND $likeproductoid";
    $params['productoid'] = '%' . $productoid . '%';
}
if (!empty($shortname)) {
    $likeshortname = $db35->sql_like('c.shortname', ':shortname', false);
    $sqlfrom .= " AND $likeshortname";
    $params['shortname'] = '%' . $shortname . '%';
}

// Build pending list (courses where at least one group is closed in 3.5 but remains open in 4.5)
$batchsize = 200;
$start = 0;
$pending = [];

do {
    $batch = $db35->get_records_sql("SELECT DISTINCT c.id, c.shortname, c.fullname, cb.productoid $sqlfrom", $params, $start, $batchsize);
    if (!$batch) {
        break;
    }
    foreach ($batch as $course) {
        // Ensure course exists in 4.5
        $course45 = $DB->get_record_sql("SELECT c.* FROM {curso_back} cb JOIN {course} c ON c.shortname = cb.codigocurso AND c.id = cb.id_curso_moodle WHERE cb.productoid = :productoid",
            ['productoid' => $course->productoid]);
        if (!$course45) {
            continue;
        }

        // Closed groups in 3.5
        $closedgroups = $db35->get_records_sql("SELECT DISTINCT g.name
            FROM {eabcattendance_sessions} eas
            JOIN {eabcattendance} ea ON ea.id = eas.eabcattendanceid
            JOIN {format_eabctiles_closegroup} cg ON cg.groupid = eas.groupid AND cg.status = 1
            JOIN {groups} g ON g.id = eas.groupid
            WHERE ea.course = :courseid", ['courseid' => $course->id]);
        if (empty($closedgroups)) {
            continue;
        }

        // Check mismatch existence (only count first one)
        $mismatchfound = false;
        foreach ($closedgroups as $cg) {
            $groupname = trim($cg->name);
            if ($groupname === '') { continue; }
            $group45 = $DB->get_record('groups', ['courseid' => $course45->id, 'name' => $groupname]);
            if (!$group45) { continue; } // Only groups existing in both.
            $close45 = $DB->get_record('format_eabctiles_closegroup', ['groupid' => $group45->id]);
            if (!$close45 || (isset($close45->status) && (int)$close45->status !== 1)) {
                $mismatchfound = true;
                break;
            }
        }

        if ($mismatchfound) {
            $pending[] = (object) [
                'courseid45' => $course45->id,
                'shortname' => $course->shortname,
                'fullname' => $course->fullname,
                'productoid' => $course->productoid,
            ];
        }
    }
    $start += $batchsize;
} while (true);

usort($pending, function($a, $b) { return $b->courseid45 <=> $a->courseid45; });
$totalcount = count($pending);
$rows = array_slice($pending, $page * $perpage, $perpage);

if (!empty($rows)) {
    $paginationurl = new moodle_url($PAGE->url);
    echo $renderer->paging_bar($totalcount, $page, $perpage, $paginationurl);
    $table = new html_table();
    $table->head = array('ID', 'Shortname', 'Fullname', 'Producto ID', 'Acciones');
    $table->data = [];
    foreach ($rows as $course) {
        $actionbuttons = [];
        foreach ($actions as $actionname => $actiondata) {
            $actionurl = new moodle_url($PAGE->url, ['action' => $actionname, 'target_identifier' => $course->productoid]);
            $actionbuttons[] = $renderer->action_icon($actionurl, new pix_icon($actiondata['icon'], get_string($actiondata['button_string'], 'tool_sessionmigrate')));
        }

        $table->data[] = [
            $course->courseid45,
            $course->shortname,
            $course->fullname,
            $course->productoid,
            implode(' ', $actionbuttons)
        ];
    }
    echo html_writer::table($table);
    echo $renderer->paging_bar($totalcount, $page, $perpage, $paginationurl);
} else {
    echo $renderer->notification(get_string('nocoursesfound', 'tool_sessionmigrate'));
}


echo $renderer->footer();
