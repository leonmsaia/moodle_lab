<?php
/*
 * Script para leer un CSV con RUTs de usuarios que consulte el servicio de Nominativo
 * y actualice el apellido materno de los usuarios. 
 * El CSV "apellido_materno.csv" debe contener dos columnas, la primera con el ID del usuario
 * y la segunda con el RUT del mismo
*/
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

global $DB, $CFG;

$contentcsv = "./apellido_materno.csv";
$rutscsv = file_get_contents($contentcsv);
$users = explode("\n", $rutscsv);
$procesados = 0;

foreach($users as $user){
    $registro   = explode(",", $user);
    $userid     = $registro[0];
    $userrut    = $registro[1];

    $datosNominativo = \local_mutual\back\utils::get_personas_nominativo($userrut, 1);

    if ( isset($datosNominativo->return->error) &&  ($datosNominativo->return->error == 0)){
        $array_aditional_files = array("apellidom" => (string) $datosNominativo->return->persona->apellido2);

        //Guardo en campos personalizados del usuario el apellido materno
        profile_save_custom_fields($userid, $array_aditional_files);
        ++$procesados;
    }else{
        // Se marca No procesado con error y mensaje
        $error = (string) $datosNominativo->return->error . " - ".(string) $datosNominativo->return->mensaje;
        echo "Error en ". $error."\n";
    }
}

echo "\n Fin de la ejecución. Procesados: ".$procesados. " de un total de: ".count($users)."\n\n";