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
 * Page to find and manage duplicate sessions.
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
    'delete_session' => [
        'confirm_string' => 'deletesesionbackconfirm',
        'started_string' => 'deletesessionstarted',
        'button_string' => 'deletesession',
        'cli_script' => 'delete_duplicate_sessions.php',
        'icon' => 't/delete',
        'target_type' => 'sesionbackid',
    ],
];

// URL parameters.
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20;
$searchtype = optional_param('searchtype', '', PARAM_ALPHA);
$searchvalue = optional_param('searchvalue', '', PARAM_TEXT);
$action = optional_param('action', '', PARAM_ALPHAEXT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$target_identifier = optional_param('target_identifier', '', PARAM_TEXT);

$urlparams = [];
if (!empty($searchtype)) {
    $urlparams['searchtype'] = $searchtype;
}
if (!empty($searchvalue)) {
    $urlparams['searchvalue'] = $searchvalue;
}

$PAGE->set_url(new moodle_url('/admin/tool/sessionmigrate/pages/duplicates.php', $urlparams));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('duplicatesearch', 'tool_sessionmigrate'));
$PAGE->set_heading(get_string('duplicatesearch', 'tool_sessionmigrate'));

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
        echo $renderer->confirm(get_string($currentaction['confirm_string'], 'tool_sessionmigrate', $target_identifier), $confirmurl, $cancelurl);
        echo $renderer->footer();
        die();
    }
}

$mform = new \tool_sessionmigrate\form\duplicates_form(null, ['searchtype' => $searchtype, 'searchvalue' => $searchvalue]);

$mform->set_data(['searchtype' => $searchtype, 'searchvalue' => $searchvalue]);

if ($mform->is_cancelled()) {
    redirect($PAGE->url);
} else if ($data = $mform->get_data()) {
    $redirecturl = new moodle_url($PAGE->url, ['searchtype' => $data->searchtype, 'searchvalue' => $data->searchvalue]);
    redirect($redirecturl);
}

echo $renderer->header();
echo $renderer->secondary_navigation();

$mform->display();

if (!empty($searchtype) && !empty($searchvalue)) {
    $connection = new \tool_sessionmigrate\conn35();
    $db35 = $connection->db;

    $validtypes = ['grupoid', 'idevento', 'sessionguid'];
    if (in_array($searchtype, $validtypes, true)) {
        list($sql, $params) = \tool_sessionmigrate\sessions::get_duplicate_sessions_sql($searchtype, $searchvalue);
        
        $sessions = $db35->get_records_sql($sql, $params);
        $totalcount = count($sessions);

        if ($sessions) {
            $paginationurl = new moodle_url($PAGE->url, ['searchtype' => $searchtype, 'searchvalue' => $searchvalue]);
            echo $renderer->paging_bar($totalcount, $page, $perpage, $paginationurl);
            $table = new html_table();
            $table->head = array('ID Sesión (GUID)', 'GroupID', 'ID sesion_back', 'ID Evento', 'Curso', 'Grupo', 'Acciones');
            $table->data = [];

            $pagedsessions = array_slice($sessions, $page * $perpage, $perpage);

            foreach ($pagedsessions as $session) {
                $actionbuttons = [];
                foreach ($actions as $actionname => $actiondata) {
                    $actionurl = new moodle_url($PAGE->url, ['action' => $actionname, 'target_identifier' => $session->sbid]);
                    $actionbuttons[] = $renderer->action_icon($actionurl, new pix_icon($actiondata['icon'], get_string($actiondata['button_string'], 'tool_sessionmigrate')));
                }

                $table->data[] = [
                    $session->idsesion,
                    $session->groupid,
                    $session->sbid,
                    $session->idevento,
                    $session->shortname,
                    $session->groupname,
                    implode(' ', $actionbuttons)
                ];
            }
            echo html_writer::table($table);
            echo $renderer->paging_bar($totalcount, $page, $perpage, $paginationurl);
        } else {
            echo $renderer->notification(get_string('nosessionsfound', 'tool_sessionmigrate'));
        }
    }
}

echo $renderer->footer();
