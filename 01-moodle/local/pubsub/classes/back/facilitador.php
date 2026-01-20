<?php

namespace local_pubsub\back;

defined('MOODLE_INTERNAL') || die;

class facilitador{

    public static function insert_update_facilitador_back($response, $newuserid ){
        global $DB;

        $record = new \stdClass();
        $record->apellidomaterno    = $response['ApellidoMaterno'];
        $record->apellidopaterno    = $response['ApellidoPaterno'];
        $record->correoelectronico  = $response['CorreoElectronico'];
        $record->dv                 = $response['DV'];
        $record->id_facilitador     = $response['Id'];
        $record->modalidad          = $response['Modalidad'];
        $record->nombre             = $response['Nombre'];
        $record->rut                = $response['Rut'];
        $record->telefono           = $response['Telefono'];
        $record->tipofacilitador    = $response['TipoFacilitador'];
        $record->id_user_moodle     = $newuserid;

        $get_facilitador = $DB->get_record("facilitador_back", array("id_user_moodle" => $newuserid));

        if (!empty($get_facilitador)) {
            $record->id     = $get_facilitador->id;                    
            $DB->update_record('facilitador_back', $record);
        }else{
            $DB->insert_record('facilitador_back', $record);
        }
    }
}