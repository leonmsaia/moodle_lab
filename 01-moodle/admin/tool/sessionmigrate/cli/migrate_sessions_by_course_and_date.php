<?php
define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
global $CFG, $DB;

// Opcional: activar debug para ejecución CLI si se desea (como en bulk).
$CFG->debug = E_ALL | E_STRICT;
$CFG->debugdisplay = true;
$CFG->debugdeveloper = true;

require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params(
    [
        'shortname' => false,
        'startdate' => false,
        'enddate'   => false,
        'logid'     => false,
        'userid'    => false,
        'help'      => false,
    ],
    [
        's' => 'shortname',
        'b' => 'startdate',
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

if ($options['help'] || empty($options['shortname']) || empty($options['startdate']) || empty($options['enddate']) || empty($options['userid'])) {
    $help = <<<'EOT'
Migrate sessions by course shortname and date range.

Options:
--shortname=STRING   Course shortname (required)
--startdate=INT      Start timestamp (required)
--enddate=INT        End timestamp (required)
--logid=INT          Log ID (optional; if missing a new log is created)
--userid=INT         User ID who triggered the migration (required)
-h, --help           Print out this help

Example:
sudo -u www-data /usr/bin/php admin/tool/sessionmigrate/cli/migrate_sessions_by_course_and_date.php --shortname=COURSE123 --startdate=1678886400 --enddate=1678972800 --userid=2
EOT;
    cli_writeln($help);
    exit(0);
}

$shortname = $options['shortname'];
$startdate = (int)$options['startdate'];
$enddate   = (int)$options['enddate'];
$logid     = !empty($options['logid']) ? (int)$options['logid'] : 0;
$userid    = (int)$options['userid'];

// Validar usuario
if (!$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*')) {
    cli_error("No se encontró el usuario con ID {$userid}.");
    exit(1);
}

// Crear o actualizar log
if ($logid) {
    $logentry = new stdClass();
    $logentry->id = $logid;
    $logentry->status = 'running';
    $logentry->timemodified = time();
    $logentry->message = "Ejecución CLI iniciada para curso {$shortname} ({$startdate} - {$enddate}).";
    $DB->update_record('tool_sessionmigrate_log', $logentry);
} else {
    $logentry = new stdClass();
    $logentry->action = 'migrate_sessions_by_course_and_date';
    $logentry->targettype = 'course_daterange';
    $logentry->targetidentifier = "{$shortname}-{$startdate}-{$enddate}";
    $logentry->status = 'running';
    $logentry->userid = $userid;
    $logentry->timecreated = time();
    $logentry->timemodified = time();
    $logentry->message = "Ejecución CLI iniciada para curso {$shortname} ({$startdate} - {$enddate}).";
    $logid = $DB->insert_record('tool_sessionmigrate_log', $logentry);
    $logentry->id = $logid;
}

\core\session\manager::set_user($user);
core_php_time_limit::raise();

cli_heading("Migrating sessions for course: {$shortname} ({$startdate} - {$enddate})");

require_once($CFG->dirroot . '/admin/tool/sessionmigrate/classes/sessions.php');
require_once($CFG->dirroot . '/admin/tool/sessionmigrate/classes/conn35.php');

try {
    $connection = new \tool_sessionmigrate\conn35();
    $db35 = $connection->db;

    // Obtener sesiones (GUIDs) usando la función dedicada.
    $info = \tool_sessionmigrate\sessions::get_sessions_info_by_course_and_date_range($shortname, $startdate, $enddate, $db35);
    $sessions = $info['sessions'] ?? [];
    $total = count($sessions);

    cli_writeln("Se encontraron {$total} sesiones para procesar.");

    $successcount = 0;
    $failedcount = 0;
    $details = [];

    foreach ($sessions as $idx => $sessobj) {
        $guid = is_object($sessobj) && isset($sessobj->guid) ? $sessobj->guid : (is_scalar($sessobj) ? $sessobj : null);
        if (empty($guid)) {
            $failedcount++;
            $details[] = "Registro {$idx} sin GUID válido, omitido.";
            continue;
        }

        cli_writeln("Procesando " . ($idx + 1) . "/{$total}: {$guid}");

        try {
            $result = \tool_sessionmigrate\sessions::migrate_session_by_guid($guid, $db35, $logid, $userid);
            if (!empty($result['success'])) {
                $successcount++;
            } else {
                $failedcount++;
            }
            $details[$guid] = $result['message'] ?? 'No message';
        } catch (Exception $e) {
            $failedcount++;
            $details[$guid] = 'Excepción: ' . $e->getMessage();
            cli_writeln("Error procesando GUID {$guid}: " . $e->getMessage());
        }
    }

    // Actualizar log final
    $logentry = new stdClass();
    $logentry->id = $logid;
    $logentry->status = $failedcount > 0 ? ($successcount > 0 ? 'partial_success' : 'failed') : 'finished';
    $logentry->message = "Migración completada. Éxitos: {$successcount}. Fallos: {$failedcount}.";
    $logentry->details = json_encode($details);
    $logentry->timemodified = time();
    $DB->update_record('tool_sessionmigrate_log', $logentry);

    cli_writeln($logentry->message);

} catch (Exception $e) {
    $logentry = new stdClass();
    $logentry->id = $logid;
    $logentry->status = 'failed';
    $logentry->message = 'Excepción fatal: ' . $e->getMessage();
    $logentry->timemodified = time();
    $DB->update_record('tool_sessionmigrate_log', $logentry);
    cli_error('Error fatal: ' . $e->getMessage());
    exit(1);
}

exit(0);
