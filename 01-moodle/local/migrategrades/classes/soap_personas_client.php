<?php

namespace local_migrategrades;

defined('MOODLE_INTERNAL') || die();

class soap_personas_client {
    private const ENDPOINT = 'http://200.14.250.147:8110/MaestroPersonas-web/BuscarPersonaService';

    public function fetch_persona_by_identificador(string $identificador) : array {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $identificador = trim($identificador);

        $xml = $this->build_envelope($identificador);

        $curl = new \curl();
        $curl->setHeader(array(
            'Content-Type: text/xml; charset=UTF-8',
        ));

        $options = array(
            'CURLOPT_CONNECTTIMEOUT' => 10,
            'CURLOPT_TIMEOUT' => 30,
        );

        $resp = $curl->post(self::ENDPOINT, $xml, $options);
        if ($resp === false || $resp === null || $resp === '') {
            return array('error' => '1', 'mensaje' => 'Respuesta vacía del servicio');
        }

        return $this->parse_response($resp);
    }

    private function build_envelope(string $identificador) : string {
        $identificador = htmlspecialchars($identificador, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        return '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cl="http://cl.mutual.ws/">'
            . '<soapenv:Header/>'
            . '<soapenv:Body>'
            . '<cl:obtenerInformacionPersona>'
            . '<solicitud>'
            . '<identificador>' . $identificador . '</identificador>'
            . '<tipo>1</tipo>'
            . '</solicitud>'
            . '</cl:obtenerInformacionPersona>'
            . '</soapenv:Body>'
            . '</soapenv:Envelope>';
    }

    private function parse_response(string $xmlstring) : array {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlstring);
        if ($xml === false) {
            return array('error' => '1', 'mensaje' => 'XML inválido', 'rawxml' => $xmlstring);
        }

        // Find <return> node regardless of namespaces.
        $returns = $xml->xpath('//*[local-name()="return"]');
        if (!$returns || empty($returns[0])) {
            return array('error' => '1', 'mensaje' => 'Respuesta sin nodo return', 'rawxml' => $xmlstring);
        }

        $ret = $returns[0];
        $error = (string)($ret->error ?? '');
        $mensaje = (string)($ret->mensaje ?? '');

        $empresaout = null;
        $empresasout = array();
        $empresas = $ret->xpath('./*[local-name()="empresas"]');
        if ($empresas) {
            foreach ($empresas as $e) {
                $empresasout[] = array(
                    'rut' => (string)($e->rut ?? ''),
                    'dv' => (string)($e->dv ?? ''),
                    'razonSocial' => (string)($e->razonSocial ?? ''),
                    'contrato' => (string)($e->contrato ?? ''),
                    'activo' => (string)($e->activo ?? ''),
                );
            }

            // Prefer first active company if present.
            $chosen = null;
            foreach ($empresas as $e) {
                if ((string)($e->activo ?? '') === '1') {
                    $chosen = $e;
                    break;
                }
            }
            if ($chosen === null) {
                $chosen = $empresas[0];
            }

            $empresaout = array(
                'rut' => (string)($chosen->rut ?? ''),
                'dv' => (string)($chosen->dv ?? ''),
                'razonSocial' => (string)($chosen->razonSocial ?? ''),
                'contrato' => (string)($chosen->contrato ?? ''),
                'activo' => (string)($chosen->activo ?? ''),
            );
        }

        return array(
            'error' => $error,
            'mensaje' => $mensaje,
            'empresa' => $empresaout,
            'empresas' => $empresasout,
            'rawxml' => $xmlstring,
        );
    }
}
