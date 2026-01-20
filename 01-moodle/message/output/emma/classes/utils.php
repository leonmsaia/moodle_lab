<?php

namespace emma\message;

defined('MOODLE_INTERNAL') || die();

class utils{
    
    /**
     * Metodo headers: cabeceras de envío del CURL
     * @param string $soap_request cuerpo xml a enviar
     * @return string $headers valor retornado para header del CURL
     */
    public static function headers_curl($soap_request){
        $headers = array(
            "Content-type: text/xml",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "Content-length: " . strlen($soap_request),
        );
        return $headers;
    }

    /**
     * Metodo send_curl: Realiza el del XML por CURL
     * @param string $url ruta del webservices soap
     * @param string $soap_request cuerpo xml a enviar
     * @param string $headers valor retornado de la funcion header para CURL
     * @return string $output valor retornado al procesar el CURL
     */
    public static function send_curl($url,$headers,$soap_request){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soap_request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }
}