<?php

namespace local_pubsub\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

require_once($CFG->libdir . '/externallib.php');

class documento extends external_api
{
    public static function get_documento_parameters()
    {
        return new external_function_parameters(
            array(
                'iddocumento' => new external_value(PARAM_RAW, 'id del documento'),
                'idinscripcion' => new external_value(PARAM_RAW, 'id de inscripcion'),
                'idsesion' => new external_value(PARAM_RAW, 'ID sesion'),
                'modalidad' => new external_value(PARAM_RAW),
                'tipodocumento' => new external_value(PARAM_RAW)
            )
        );
    }

    public static function get_documento($iddocumento, $idinscripcion, $idsesion, $modalidad, $tipodocumento)
    {
        $params = self::validate_parameters(
            self::get_documento_parameters(),
            array(
                'iddocumento' => $iddocumento,
                'idinscripcion' => $idinscripcion,
                'idsesion' => $idsesion,
                'modalidad' => $modalidad,
                'tipodocumento' => $tipodocumento
            )
        );

        $request = \local_mutual\back\utils::get_certificado_from_back($params['iddocumento']);

        if($request["error"]) {
            \local_mutual\back\utils::upsert_certificado_data($params); 
            throw new \moodle_exception($request['data']);
        }

        $params['fechaexpiracion'] = $request['data']->FechaExpiracion;
        $params['urlarchivo'] = $request['data']->UrlArchivo;
        \local_mutual\back\utils::upsert_certificado_data($params);

        return [
            "fechaexpiracion" => $request['data']->FechaExpiracion,
            "urlarchivo" => $request['data']->UrlArchivo
        ];
    }

    public static function get_documento_returns()
    {
        return new external_single_structure(
            array(
                'fechaexpiracion' => new external_value(PARAM_RAW),
                'urlarchivo' => new external_value(PARAM_RAW)
            )
        );
    }
}
