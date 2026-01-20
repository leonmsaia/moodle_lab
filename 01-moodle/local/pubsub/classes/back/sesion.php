<?php

namespace local_pubsub\back;

defined('MOODLE_INTERNAL') || die;

class sesion{

    public static function inser_update_sesion_back($response, $sessionid ){
        global $DB;

        $record = new \stdClass();
        $record->agenciamutual          = $response['AgenciaMutual'];
        $record->auditorio              = $response['Auditorio'];
        $record->cantidadparticipantes  = $response['CantidadParticipantes'];
        $record->capacitacionportal     = $response['CapacitacionPortal'];
        $record->codigocurso            = $response['CodigoCurso'];
        $record->estado                 = $response['Estado'];
        $record->idsesion               = $response['Id'];
        $record->idejecutivo            = $response['IdEjecutivo'];
        $record->idevento               = $response['IdEvento'];
        $record->idrelator              = $response['IdRelator'];
        $record->iniciocapacitacion     = $response['InicioCapacitacion'];
        $record->motivosuspension       = $response['MotivoSuspension'];
        $record->terminocapacitacion    = $response['TerminoCapacitacion'];
        $record->id_sesion_moodle       = $sessionid;
        $record->formatosesion          = $response['FormatoSesion'];
        $record->idcomuna               = $response['IdComuna'];
        $record->direccion              = $response['Direccion'];
        $record->idadherente            = $response['IdAdherente'];
        $record->numeroadherente        = $response['NumeroAdherente'];
        $record->poseereservainstalacion= $response['PoseeReservaInstalacion'];
        $record->idregion               = $response['IdRegion'];
        $record->rutadherente           = $response['RutAdherente'];
        $record->cargocontacto          = $response['CargoContacto'];
        $record->emailcontacto          = $response['EmailContacto'];
        $record->fonocontacto           = $response['FonoContacto'];
        $record->nombrecontacto         = $response['NombreContacto'];
        $record->nombrecomuna           = $response['NombreComuna'];
        $record->codigocomuna           = $response['CodigoComuna'];
        $record->nombreregion           = $response['NombreRegion'];
        $record->codigoregion           = $response['CodigoRegion'];
        $record->tipodependencia        = $response['TipoDependencia'];
        $record->nombreadherente        = $response['NombreAdherente'];
        $record->urlseminarioweb        = $response['UrlSeminarioWeb'];

        $get_sesion = $DB->get_record("sesion_back", array("id_sesion_moodle" => $sessionid));

        if (!empty($get_sesion)) {
            $record->id     = $get_sesion->id;                    
            $DB->update_record('sesion_back', $record);
        }else{
            $DB->insert_record('sesion_back', $record);
        }
    }
}