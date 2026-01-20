<?php
/*
 * Script para corregir la asignacion de usuarios a grupos en la creacion de sesiones streaming y presenciales
*/
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

global $DB, $CFG;

$contentcsv = "./group_sesion_users.csv";
$guidscsv = file_get_contents($contentcsv);
$guids = explode("\n", $guidscsv);
$procesados = 0;

foreach($guids as $guid){
    $sesion = $BD->get_record('eabcattendance_sessions', array('guid' => $guid));
    if ( (!empty($sesion)) && (!empty($sesion->groupid)) ){
        $participantes = \local_pubsub\metodos_comunes::get_participantes_sesion($guid);
        foreach ($participantes as $participante) {
            $user = $BD->get_record('user', array('username' => $participante['ParticipanteIdentificador']));
            if($user){
                groups_add_member($sesion->groupid, $user->id);
                $procesados++;
            }
        }
    }
}

echo "\n Fin de la ejecución. Procesados: ".$procesados. " de un total de: ".count($guids)."\n\n";