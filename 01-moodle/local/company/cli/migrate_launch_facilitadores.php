
<?php



define('NO_MOODLE_COOKIES', true); // evita sesión de usuario

require_once(__DIR__ . '/../../../config.php');

$script = escapeshellcmd($CFG->dirroot . '/local/company/cli/migrate_facilitadores.php');

$cmd = "php $script > /dev/null 2>&1 &";
exec($cmd);

echo "Proceso migrado facilitadoresiniciado en segundo plano.";
