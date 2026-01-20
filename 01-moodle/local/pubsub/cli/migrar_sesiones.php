<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
global $CFG;
require_once $CFG->libdir.'/clilib.php';

list($options, $unrecognized) = cli_get_params(
    array(
        'name' => "migrar_sesiones.csv",
        'help' => false
    ),
    array('n' => 'name', 'h' => 'help')
);

if ($options['help']) {
    echo "
    Options:
    -n, --name     Nombre del archivo a procesar

    Example:
    php local/pubsub/cli/migrar_sesiones.php --name=migrar_sesiones.csv
    ";
    exit(0);
}

global $DB, $CFG;

$contentcsv = './'.trim($options['name']);
if(!file_exists($contentcsv)){
    echo "El archivo de migracion no existe";
    exit(0);
}
$rutscsv = file_get_contents($contentcsv);
$sessions = explode("\n", $rutscsv);

$line = 1;
echo "productoid,idsesion,idevento,detalles\n";
foreach($sessions as $session){
    if(trim($session) == 'OK'){
        continue;
    }
    $registro   = explode(",", $session);
    $productoid = trim($registro[0]);
    $idsession  = trim($registro[1]);
    $idevento   = trim($registro[2]);

    try {
        $response = \local_pubsub\utils::update_sesion_migrate_logic($idsession, $productoid, $idevento, 'Actualizacion');
        echo "OK,OK,OK,{$line}\n";
    }catch (\Exception $e){
        $err = $e->getMessage();
        echo "{$productoid},{$idsession},{$idevento},{$err}\n";
    }

    $line++;
}