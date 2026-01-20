<?php

namespace local_pubsub\back;

defined('MOODLE_INTERNAL') || die;

class curso{

    public static function insert_update_curso_back($response, $cursoid ){
        global $DB;

        $record = new \stdClass();
        $record->backcapacitacion       = $response['BackCapacitacion'];
        $record->codigocurso            = $response['CodigoCurso'];
        $record->codigopeligro          = $response['CodigoPeligro'];
        $record->codigosuseso           = $response['CodigoSUSESO'];
        $record->contenidos             = $response['Contenidos'];
        $record->cursofoco              = $response['CursoFoco'];
        $record->descripcion            = $response['Descripcion'];
        $record->disponibleenportal     = $response['DisponibleEnPortal'];
        $record->estado                 = $response['Estado'];
        $record->fechaenvio             = $response['FechaEnvio'];
        $record->foliosuseso            = $response['FolioSUSESO'];
        $record->horas                  = $response['Horas'];
        $record->identificadorsuseso    = $response['IdentificadorSUSESO'];
        $record->maximotiempo           = $response['MaximoTiempo'];
        $record->minimotiempo           = $response['MinimoTiempo'];
        $record->modalidaddistancia     = $response['ModalidadDistancia'];
        $record->nombrecorto            = $response['NombreCorto'];
        $record->objetivo               = $response['Objetivo'];
        $record->observacionsuseso      = $response['ObservacionSUSESO'];
        $record->productocurso          = $response['ProductoCurso'];
        $record->productoid             = $response['ProductoId'];
        $record->proveedor              = $response['Proveedor'];
        $record->tematica               = $response['Tematica'];
        $record->tipocurso              = $response['TipoCurso'];
        $record->tipomodalidad          = $response['TipoModalidad'];
        $record->vigenciadocumentos     = $response['VigenciaDocumentos'];
        $record->estadoenviosuseso      = $response['EstadoEnvioSuseso'];  
        $record->codigoproveedordistancia = $response['CodigoProveedorDistancia'];
        $record->nombreproveedorcursodistancia = $response['NombreProveedorCursoDistancia'];
        $record->id_curso_moodle        = $cursoid;

        $get_curso = $DB->get_record("curso_back", array("id_curso_moodle" => $cursoid));

        if (!empty($get_curso)) {
            $record->id     = $get_curso->id;
            $DB->update_record('curso_back', $record);
        }else{
            $DB->insert_record('curso_back', $record);
        }
    }
}