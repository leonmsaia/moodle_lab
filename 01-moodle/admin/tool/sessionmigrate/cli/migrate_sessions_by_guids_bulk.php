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
 * CLI script for bulk migrating sessions using a config setting.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../../config.php');
global $CFG, $DB;

// Enable debug for this execution only.
$CFG->debug = E_ALL | E_STRICT;
$CFG->debugdisplay = true;
$CFG->debugdeveloper = true;

require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    [
        'configkey' => false,
        'userid' => false,
        'help' => false,
    ],
    [
        'c' => 'configkey',
        'u' => 'userid',
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['configkey']) || empty($options['userid'])) {
    $help = <<<'EOT'
Bulk migrate sessions using GUIDs stored in a temporary config setting.

Options:
-c, --configkey       The key used to store the GUIDs in the config. (Required)
-u, --userid          The ID of the user who triggered the action. (Required)
-h, --help            Print out this help.

Example:
$ sudo -u www-data /usr/bin/php admin/tool/sessionmigrate/cli/migrate_sessions_by_guids_bulk.php --configkey=bm_guids_1678886400 --userid=2
EOT;
    cli_writeln($help);
    exit(0);
}

$configkey = $options['configkey'];
$userid = (int)$options['userid'];

// Get the GUIDs from the config.
$guidsstring = get_config('tool_sessionmigrate', $configkey);

// IMPORTANT: Immediately unset the config to prevent re-runs and to clean up.
unset_config($configkey, 'tool_sessionmigrate');

// Create a log entry.
$logentry = new stdClass();
$logentry->action = 'migrate_session_by_guid_bulk';
$logentry->targettype = 'sessionguid_bulk';
$logentry->targetidentifier = $configkey; // Use the unique key as the identifier.
$logentry->status = 'created';
$logentry->userid = $userid;
$logentry->timecreated = time();
$logentry->timemodified = time();
$logid = $DB->insert_record('tool_sessionmigrate_log', $logentry);
$logentry->id = $logid;

if(!$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*')) {
    cli_error("No se encontró el usuario con ID {$userid}.");
    $logentry->status = 'failed';
    $logentry->message = "No se encontró el usuario con ID {$userid}.";
    $logentry->timemodified = time();
    $DB->update_record('tool_sessionmigrate_log', $logentry);
    exit(0);
}
\core\session\manager::set_user($user);
core_php_time_limit::raise();

if (empty($guidsstring)) {
    $logentry->status = 'failed';
    $logentry->timemodified = time();
    $logentry->message = "Error: No se encontraron GUIDs en la configuración para la clave {$configkey}. La tarea no puede continuar.";
    $DB->update_record('tool_sessionmigrate_log', $logentry);
    cli_error($logentry->message);
    exit(1);
}

// Update log status to running.
$logentry->status = 'running';
$logentry->timemodified = time();
$logentry->message = "Iniciando migración masiva de sesiones para la clave {$configkey}";
$DB->update_record('tool_sessionmigrate_log', $logentry);

cli_heading("Migrating sessions for config key: {$configkey}");

// Include the class with the core logic.
require_once($CFG->dirroot . '/admin/tool/sessionmigrate/classes/sessions.php');

try {
    $guids = explode(',', $guidsstring);
    $guids = array_map('trim', $guids);
    $guids = array_filter($guids);
    $totalguids = count($guids);
    cli_writeln("Se encontraron {$totalguids} GUIDs para procesar.");

    $connection = new \tool_sessionmigrate\conn35();
    $db35 = $connection->db;
    
    $successcount = 0;
    $failedcount = 0;
    $details = [];

    foreach ($guids as $index => $guid) {
        cli_writeln("Procesando " . ($index + 1) . "/{$totalguids}: {$guid}");
        $result = \tool_sessionmigrate\sessions::migrate_session_by_guid($guid, $db35, $logid, $userid);
        if ($result['success']) {
            $successcount++;
        } else {
            $failedcount++;
        }
        $details[$guid] = $result['message'];
    }

    $logentry->status = ($failedcount > 0) ? 'partial_success' : 'success';
    $logentry->message = "Migración masiva completada. Éxito: {$successcount}. Fallos: {$failedcount}.";
    $logentry->details = json_encode($details);
    $logentry->timemodified = time();
    $DB->update_record('tool_sessionmigrate_log', $logentry);

    cli_writeln($logentry->message);

} catch (\Exception $e) {
    $logentry->status = 'failed';
    $logentry->message = 'Excepción: ' . $e->getMessage();
    $logentry->timemodified = time();
    $DB->update_record('tool_sessionmigrate_log', $logentry);
    cli_error('Error: ' . $e->getMessage());
}

exit(0);
