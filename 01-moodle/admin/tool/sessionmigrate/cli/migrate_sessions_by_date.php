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
 * CLI script to migrate sessions by daterange.
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
        'startdate' => false,
        'enddate' => false,
        'logid' => false,
        'userid' => false,
        'help' => false,
    ],
    [
        's' => 'startdate',
        'e' => 'enddate',
        'l' => 'logid',
        'u' => 'userid',
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['startdate']) || empty($options['enddate']) || empty($options['logid']) || empty($options['userid'])) {
    $help = <<<'EOT'
Migrate sessions by daterange.

Options:
-s, --startdate         The start date timestamp. (Required)
-e, --enddate           The end date timestamp. (Required)
-l, --logid             The ID of the log entry to update. (Required)
-u, --userid            The ID of the user who triggered the action. (Required)
-h, --help              Print out this help.

Example:
$ sudo -u www-data /usr/bin/php admin/tool/sessionmigrate/cli/migrate_sessions_by_date.php --startdate=1672531200 --enddate=1675209600 --logid=1 --userid=2
EOT;
    cli_writeln($help);
    exit(0);
}


$startdate = (int)$options['startdate'];
$enddate = (int)$options['enddate'];
$logid = (int)$options['logid'];
$userid = (int)$options['userid'];




$logentry = $DB->get_record('tool_sessionmigrate_log', ['id' => $logid]);
if (!$logentry) {
    cli_error("No se encontró la entrada de log con ID {$logid}.");
}
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

// Update log status to running.
$logentry->status = 'running';
$logentry->timemodified = time();
$logentry->message = "Iniciando migración de sesiones para el rango: " . userdate($startdate) . " - " . userdate($enddate);
$DB->update_record('tool_sessionmigrate_log', $logentry);

cli_heading('Migrando sesiones para el rango de fechas: ' . userdate($startdate) . ' a ' . userdate($enddate));

require_once($CFG->dirroot . '/admin/tool/sessionmigrate/classes/sessions.php');

$details = [];
$successcount = 0;
$failedcount = 0;

try {
    $connection = new \tool_sessionmigrate\conn35();
    $db35 = $connection->db;

    $guids = \tool_sessionmigrate\sessions::get_session_guids_by_date_range($startdate, $enddate, $db35);
    $total = count($guids);
    cli_writeln("Se encontraron {$total} sesiones para migrar.");
    $details[] = "Se encontraron {$total} sesiones para migrar.";

    if ($total > 0) {
        foreach ($guids as $index => $guid) {
            cli_writeln("Procesando " . ($index + 1) . "/{$total}: {$guid}");
            $details[] = "- Procesando " . ($index + 1) . "/{$total}: {$guid}";
            $result = \tool_sessionmigrate\sessions::migrate_session_by_guid($guid, $db35, $logid, $userid);
            if ($result['success']) {
                $successcount++;
            } else {
                $failedcount++;
            }
            // integramos los detalles de la migración de cada sesión.
            $details = array_merge($details, $result['details']);

            $details[] = $result['message'];
        }
        /**
         * Logica antigua
         *
         * $result = \tool_sessionmigrate\sessions::migrate_sessions_by_date_range($startdate, $enddate, $db35, $logid, $userid);
        $details = array_merge($details, $result['details']);
         * **
         */

    }

    /**
     * Logica antigua
     *
    $logentry->status = isset($result['success']) && $result['success'] ? 'completed' : 'failed';
    $logentry->message = isset($result['success']) && $result['success'] ? $result['message'] : 'Migración fallida.';

    $logentry->details = json_encode($details);
    $logentry->timemodified = time();
    $DB->update_record('tool_sessionmigrate_log', $logentry);**/

    $logentry->status = ($failedcount > 0) ? 'partial_success' : 'success';
    $logentry->message = "Migración masiva completada. Éxito: {$successcount}. Fallos: {$failedcount}.";
    $logentry->details = json_encode($details);
    $logentry->timemodified = time();
    $DB->update_record('tool_sessionmigrate_log', $logentry);

    cli_writeln("Migración finalizada.");
    cli_writeln("Exitosas: {$successcount}");
    cli_writeln("Fallidas: {$failedcount}");


} catch (\Exception $e) {
    $details[] = 'Excepción: ' . $e->getMessage();
    $details[] = 'Stack trace: ' . $e->getTraceAsString();
    $logentry->status = 'failed';
    $logentry->message = 'Excepción: ' . $e->getMessage();
    $logentry->details = json_encode($details);
    $logentry->timemodified = time();
    $DB->update_record('tool_sessionmigrate_log', $logentry);
    cli_error('Error: ' . $e->getMessage());
}

exit(0);
