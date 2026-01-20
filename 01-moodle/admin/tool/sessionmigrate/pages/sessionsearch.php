<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute and/or modify
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
 *
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once (__DIR__ . './../../../../config.php');

global $PAGE, $CFG, $DB, $USER;

require_once($CFG->libdir.'/adminlib.php');


require_admin();
// Action definitions.
$actions = [
    'sync_closed_sessions' => [
        'confirm_string' => 'syncsessionsconfirm',
        'started_string' => 'syncsessionsstarted',
        'button_string' => 'syncsessions',
        'cli_script' => 'sync_closed_sessions_by_guid.php',
        'icon' => 'i/switch',
        'target_type' => 'sessionguid',
    ],
    'migrate_session_by_guid' => [
        'confirm_string' => 'migratesessionconfirm',
        'started_string' => 'migratesessionstarted',
        'button_string' => 'migratesession',
        'cli_script' => 'sync_sessions_by_guid.php',
        'icon' => 't/cohort',
        'target_type' => 'sessionguid',
    ],
];

// URL parameters.
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20;
$sessionguids = optional_param('sessionguids', '', PARAM_TEXT);
$action = optional_param('action', '', PARAM_ALPHAEXT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$target_identifier = optional_param('target_identifier', '', PARAM_TEXT);

$urlparams = [];
if (!empty($sessionguids)) {
    $urlparams['sessionguids'] = $sessionguids;
}

$PAGE->set_url(new moodle_url('/admin/tool/sessionmigrate/pages/sessionsearch.php', $urlparams));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('sessionsearch', 'tool_sessionmigrate'));
$PAGE->set_heading(get_string('sessionsearch', 'tool_sessionmigrate'));

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

$sessionguids_for_form = str_replace(',', "\n", $sessionguids);
$mform = new \tool_sessionmigrate\form\sessionsearch_form(null, ['sessionguids' => $sessionguids_for_form]);

$mform->set_data(['sessionguids' => $sessionguids_for_form]);

if ($mform->is_cancelled()) {
    redirect($PAGE->url);
} else if ($data = $mform->get_data()) {
    $guids = trim($data->sessionguids);
    $sessionguids_for_url = preg_replace('/[\r\n]+/', ',', $guids);
    $redirecturl = new moodle_url($PAGE->url, ['sessionguids' => $sessionguids_for_url]);
    redirect($redirecturl);
}

echo $renderer->header();
echo $renderer->secondary_navigation();

$mform->display();

if (!empty($sessionguids)) {
    $connection = new \tool_sessionmigrate\conn35();
    $db35 = $connection->db;

    $guids = explode(',', $sessionguids);
    $guids = array_map('trim', $guids);
    $guids = array_filter($guids);

    list($insql, $inparams) = $db35->get_in_or_equal($guids, SQL_PARAMS_QM, 'guid');

    $sql = "SELECT DISTINCT
                cb.productoid,
                eas.guid AS idsesion,
                sb.idevento
            FROM {course} AS c
            JOIN {curso_back} AS cb
                ON cb.id_curso_moodle = c.id
            JOIN {eabcattendance} AS ea
                ON ea.course = c.id
            JOIN {eabcattendance_sessions} AS eas
                ON eas.eabcattendanceid = ea.id
            JOIN {sesion_back} AS sb
                ON sb.idsesion = eas.guid
            WHERE eas.guid ". $insql;

    $params = $inparams;

    $totalcount = $db35->count_records_sql("SELECT COUNT(DISTINCT eas.guid) FROM {eabcattendance_sessions} eas WHERE eas.guid ". $insql, $params);
    $sessions = $db35->get_records_sql($sql, $params, $page * $perpage, $perpage);

    if ($sessions) {
        $paginationurl = new moodle_url($PAGE->url, ['sessionguids' => $sessionguids]);
        echo $renderer->paging_bar($totalcount, $page, $perpage, $paginationurl);
        $table = new html_table();
        $table->head = array('Producto ID', 'ID Sesión', 'ID Evento', 'Acciones');
        $table->data = [];
        foreach ($sessions as $session) {
            $actionbuttons = [];
            foreach ($actions as $actionname => $actiondata) {
                $actionurl = new moodle_url($PAGE->url, ['action' => $actionname, 'target_identifier' => $session->idsesion]);
                $actionbuttons[] = $renderer->action_icon($actionurl, new pix_icon($actiondata['icon'], get_string($actiondata['button_string'], 'tool_sessionmigrate')));
            }

            $table->data[] = [
                $session->productoid,
                $session->idsesion,
                $session->idevento,
                implode(' ', $actionbuttons)
            ];
        }
        echo html_writer::table($table);
        echo $renderer->paging_bar($totalcount, $page, $perpage, $paginationurl);
    } else {
        echo $renderer->notification(get_string('nosessionsfound', 'tool_sessionmigrate'));
    }
}

echo $renderer->footer();
