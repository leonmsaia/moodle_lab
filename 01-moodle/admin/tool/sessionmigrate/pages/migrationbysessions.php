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
 * Page for migrating multiple sessions by GUID.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

global $PAGE, $CFG, $DB, $USER;

$sessionguids = optional_param('sessionguids', '', PARAM_RAW);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

require_admin();
$PAGE->set_url(new moodle_url('/admin/tool/sessionmigrate/pages/migrationbysessions.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('migrationbysessions', 'tool_sessionmigrate'));
$PAGE->set_heading(get_string('migrationbysessions', 'tool_sessionmigrate'));

/** @var \tool_sessionmigrate\output\renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sessionmigrate');

if ($confirm && !empty($sessionguids)) {
    // Action confirmed.
    $guids = explode(',', $sessionguids);
    $guids = array_map('trim', $guids);
    $guids = array_filter($guids);
    $guidsstring = implode(',', $guids);

    // Create a unique key for the config setting.
    $configkey = 'bm_guids_' . time();

    // Store the GUIDs in the config.
    set_config($configkey, $guidsstring, 'tool_sessionmigrate');

    // Execute the action CLI script.
    $command = 'php ' . $CFG->dirroot . '/admin/tool/sessionmigrate/cli/migrate_sessions_by_guids_bulk.php' .
               ' --configkey=' . escapeshellarg($configkey) .
               ' --userid=' . $USER->id;
    exec($command . ' > /dev/null 2>&1 &');

    redirect($PAGE->url, get_string('migrationstarted', 'tool_sessionmigrate'), \core\output\notification::NOTIFY_SUCCESS);
}
echo $renderer->header();
echo $renderer->secondary_navigation();

$mform = new \tool_sessionmigrate\form\migrationbysessions_form();

if ($data = $mform->get_data()) {
    $guids = trim($data->sessionguids);
    $guidsarray = preg_split('/[\r\n]+/', $guids);
    $guidsarray = array_map('trim', $guidsarray);
    $guidsarray = array_filter($guidsarray);
    $sessioncount = count($guidsarray);
    
    if ($sessioncount > 0) {
        $sessionguids_for_url = implode(',', $guidsarray);
        
        // Show confirmation page.
        $confirmurl = new moodle_url($PAGE->url, ['confirm' => 1, 'sessionguids' => $sessionguids_for_url]);
        $cancelurl = new moodle_url($PAGE->url);
        echo $renderer->confirm(get_string('confirmbulkmigration', 'tool_sessionmigrate', $sessioncount), $confirmurl, $cancelurl);
        echo $renderer->footer();
        die();
    } else {
        redirect($PAGE->url, get_string('nosessionguids', 'tool_sessionmigrate'), \core\output\notification::NOTIFY_ERROR);
    }
} else {
    $mform->display();
}

echo $renderer->footer();

