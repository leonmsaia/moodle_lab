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
 * CLI script to delete duplicate data.
 * Supports deleting by session GUID or by sesion_back record ID.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../../../config.php');
global $CFG, $DB;

// Activar debug únicamente en esta ejecución.
$CFG->debug = E_ALL | E_STRICT;
$CFG->debugdisplay = true;
$CFG->debugdeveloper = true;

require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    [
        'targettype' => false,
        'targetidentifier' => false,
        'logid' => false,
        'userid' => false,
        'help' => false,
    ],
    [
        't' => 'targettype',
        'i' => 'targetidentifier',
        'l' => 'logid',
        'u' => 'userid',
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['targettype']) || empty($options['targetidentifier']) || empty($options['logid']) || empty($options['userid'])) {
    $help = <<<'EOT'
Delete duplicate data.

Options:
-t, --targettype        The type of the target: sessionguid | sesionbackid. (Required)
-i, --targetidentifier  The identifier: GUID when targettype=sessionguid, or sesion_back ID when targettype=sesionbackid. (Required)
-l, --logid             The ID of the log entry to update. (Required)
-u, --userid            The ID of the user who triggered the action. (Required)
-h, --help              Print out this help.

Examples:
$ sudo -u www-data /usr/bin/php admin/tool/sessionmigrate/cli/delete_duplicate_sessions.php --targettype=sessionguid --targetidentifier=SOME-GUID --logid=1 --userid=2
$ sudo -u www-data /usr/bin/php admin/tool/sessionmigrate/cli/delete_duplicate_sessions.php --targettype=sesionbackid --targetidentifier=12345 --logid=1 --userid=2
EOT;
    cli_writeln($help);
    exit(0);
}

$targettype = $options['targettype'];
$targetidentifier = $options['targetidentifier'];
$logid = (int)$options['logid'];
$userid = (int)$options['userid'];

// Update log status to running.
$logentry = (object)[
    'id' => $logid,
    'status' => 'running',
    'timemodified' => time(),
    'message' => "Iniciando eliminación de sesión: {$targettype} = {$targetidentifier}"
];
$DB->update_record('tool_sessionmigrate_log', $logentry);

cli_heading('Deleting session ' . $targettype . ': ' . $targetidentifier);

require_once($CFG->dirroot . '/admin/tool/sessionmigrate/classes/sessions.php');

try {
    $connection = new \tool_sessionmigrate\conn35();
    $db35 = $connection->db;

    if ($targettype === 'sesionbackid') {
        $id = (int)$targetidentifier;
        $details = [];
        $record = $db35->get_record('sesion_back', ['id' => $id]);
        if (!$record) {
            $result = ['success' => false, 'message' => "Registro sesion_back no encontrado: id={$id}", 'details' => []];
        } else {
            $db35->delete_records('sesion_back', ['id' => $id]);
            $result = ['success' => true, 'message' => "Registro sesion_back eliminado: id={$id}", 'details' => ["idsesion={$record->idsesion}"]];
        }
    } else {
        $result = \tool_sessionmigrate\sessions::delete_session_by_guid($targetidentifier, $db35, $logid, $userid);
    }

    $logentry->status = $result['success'] ? 'success' : 'failed';
    $logentry->message = $result['message'];
    if (isset($result['details'])) {
        $logentry->details = json_encode($result['details']);
    }
    $logentry->timemodified = time();
    $DB->update_record('tool_sessionmigrate_log', $logentry);

    cli_writeln($result['message']);
    if (!empty($result['details'])) {
        foreach ($result['details'] as $d) {
            cli_writeln(" - $d");
        }
    }

} catch (\Exception $e) {
    $logentry->status = 'failed';
    $logentry->message = 'Excepción: ' . $e->getMessage();
    $logentry->timemodified = time();
    $DB->update_record('tool_sessionmigrate_log', $logentry);
    cli_error('Error: ' . $e->getMessage());
}

exit(0);

