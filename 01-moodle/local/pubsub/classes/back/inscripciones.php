<?php

namespace local_pubsub\back;

defined('MOODLE_INTERNAL') || die;

class inscripciones{

    public static function insert_update_inscripciones_back($response, $sessionid, $userid, $rut = ''){
        global $DB;

        $record = new \stdClass();        
        $record->idinterno              = $response['IdInterno'];
        $record->idsexo                 = $response['IdSexo'];
        $record->numeroadherente        = $response['NumeroAdherente'];
        $record->participanteapellido1  = $response['ParticipanteApellido1'];
        $record->participanteapellido2  = $response['ParticipanteApellido2'];
        $record->participanteemail      = $response['ParticipanteEmail'];
        $record->participantefono       = $response['ParticipanteFono'];
        $record->participanteidentificador = $response['ParticipanteIdentificador'];
        $record->participantenombre     = $response['ParticipanteNombre'];
        $record->participantepais       = $response['ParticipantePais'];
        $record->participantetipodocumento = $response['ParticipanteTipoDocumento'];
        $record->responsableemail       = $response['ResponsableEmail'];
        $record->responsableidentificador  = $response['ResponsableIdentificador'];
        $record->responsablenombres     = $response['ResponsableNombres'];
        $record->id_sesion_moodle       = $sessionid;

        $get_inscripciones = $DB->get_record("inscripciones_back", array("idinterno" => $response['IdInterno']));

        if (!empty($get_inscripciones)) {
            $record->id     = $get_inscripciones->id;
            $DB->update_record('inscripciones_back', $record);
        }else{
            $DB->insert_record('inscripciones_back', $record);
        }
        self::update_custom_fields($record, $response, $userid, $rut);
    }

    public static function update_custom_fields($record, $response, $userid, $rut){

        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $explode_rut = explode('-', $rut);
        $response['ParticipanteRUT'] = !empty($explode_rut[0]) ? $explode_rut[0] : '' ;
        $response['ParticipanteDV'] = !empty($explode_rut[1]) ? $explode_rut[1] : '' ;

        $explode_rut_empresa = explode('-', $response['ResponsableIdentificador']);
        $record->participanterutadherente       = !empty($explode_rut_empresa[0]) ? $explode_rut_empresa[0] : '';
        $record->participantedvadherente        = !empty($explode_rut_empresa[1]) ? $explode_rut_empresa[1] : '';

        $record->participanterut                = $response['ParticipanteRUT'];
        $record->participantedv                 = $response['ParticipanteDV'];
        $record->participantefechanacimiento    = $response['ParticipanteFechaNacimiento'];
        $record->participantecargo              = $response['ParticipanteCargo'];
        $record->responsablenombre              = $response['ResponsableNombres'];
        $record->responsableapellido1           = $response['ResponsableApellido1'];
        $record->responsableapellido2           = $response['ResponsableApellido2'];
        $record->responsabletipodocumento       = $response['ResponsableTipoDocumento'];
        $record->responsablerut                 = $response['ResponsableRUT'];
        $record->responsabledv                  = $response['ResponsableDV'];
        $record->responsablefechanacimiento     = $response['ResponsableFechaNacimiento'];
        $record->responsabletelefonomovil       = $response['ResponsableTelefonoMovil'];
        $record->responsabletelefonofijo        = $response['ResponsableTelefonoFijo'];
        $record->responsablecargo               = $response['ResponsableCargo'];
        $record->responsablecodigocomuna        = $response['ResponsableCodigoComuna'];
        $record->responsablecodigoregion        = $response['ResponsableCodigoRegion'];
        $record->responsabledireccion           = $response['ResponsableDireccion'];
        $record->participanteidsexo             = $response['IdSexo'];
        /** CAMPOS PERSONALIZADOS */

        /*  ResponsableIdentificador */
        
        $data = \local_mutual\front\utils::create_object_user_custon_field($record);
        $custom_field = \local_mutual\front\utils::insert_custom_fields_user($userid, $data);
    }
}