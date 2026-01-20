<?php

namespace emma\message;

use function GuzzleHttp\json_decode;

require_once('utils.php');

class message_procesor {
    
    private $idempresa;
    private $clave;
    private $idcampana;
    private $idcategoria;    

    public function __construct(){
        $this->idempresa  = get_config('message_emma', 'idempresa');
        $this->clave      = get_config('message_emma', 'clave');
        $this->idcampana  = get_config('message_emma', 'idcampana'); 
        $this->idcategoria = get_config('message_emma', 'idcategoria');         
    }

    /**
     * Metodo clean_xml para limpiar el XML devuelto
     * @param string $output respuesta del XML
     * @return object $clean_xml XML limpio
     */
    public static function clean_xml($output){
        $clean_xml  = str_ireplace([
            'Soap:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/"',
            'Soap:Envelope','soap:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"', 
            ' xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"', 
            ' xmlns:soapenc="http://schemas.xmlsoap.org/soap/encoding/"', 
            'xmlns:xsd="http://www.w3.org/2001/XMLSchema"', 
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"',
            'soap:Body',
            '<   >', 
            '<>',
            '</>',
            '<?xml version="1.0" encoding="UTF-8"?>'
        ], '', $output);

        return @simplexml_load_string($clean_xml);
    }

    /**
     * Metodo enviadirecto para enviar un correo
     * @param object $message informarción del email a enviar     
     * @param string $attachpatch ruta adjunto a enviar
     * @return string $value valor retornado con el Id de creación
     */

    public function enviadirecto($message,$attachpatch){
        
        if ($message->fullmessageformat == 0){
            $cuerpo = "<![CDATA[".$message->smallmessage."]]>";
        }elseif($message->fullmessageformat == 2){
            $cuerpo = "<![CDATA[".$message->fullmessagehtml."]]>";
        }else{
            $cuerpo = "<![CDATA[".$message->fullmessage."]]>";
        }

        $cuerposinformato = str_replace('&', '', $message->fullmessage); // Replaces all spaces with hyphens.
        $adjunto = ($attachpatch) ? "<![CDATA[".$attachpatch."]]>" : '';

        $soap_request = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="'.get_config('message_emma', 'emma_wsdl').'">
            <soapenv:Header/>
            <soapenv:Body>
                <ns1:enviadirecto xmlns:ns1="EMMA">
                <ns1:idempresa>'.$this->idempresa.'</ns1:idempresa>
                <ns1:clave>'.$this->clave.'</ns1:clave>
                <ns1:idcampana>'.$this->idcampana.'</ns1:idcampana>
                <ns1:idcategoria>'.$this->idcategoria.'</ns1:idcategoria>
                <ns1:email>'.$message->userto->email.'</ns1:email>
                <ns1:campo1>'.$message->subject.'</ns1:campo1>
                <ns1:campo2>'.$cuerpo.'</ns1:campo2>
                <ns1:campo3>'.$cuerposinformato.'</ns1:campo3>
                <ns1:campo4>'.$adjunto.'</ns1:campo4>
                <ns1:campo5>'.$message->userfrom->id.'</ns1:campo5>
                <ns1:campo6>'.$message->userto->id.'</ns1:campo6>
                <ns1:campo7>'.$message->userfrom->email.'</ns1:campo7>
                </ns1:enviadirecto>
            </soapenv:Body>
        </soapenv:Envelope>';

        $headers    = \emma\message\utils::headers_curl($soap_request);
        $url        = get_config('message_emma', 'emma_webservices');                
        $output     = \emma\message\utils::send_curl($url,$headers,$soap_request);
        $clean_xml  = self::clean_xml($output);
        
        if($clean_xml->faultcode){
            $error = (string) $clean_xml->faultstring;
            //throw new \Exception('Ocurrió un error obteniendo datos del servicio "enviadirecto" Emma. Error: "'.$error. '" Codigo: '.$clean_xml->faultcode);
            return false;
        }else{
            //$value  = (string) $clean_xml->enviadirecto[0]; 
            return true;     
        }
    }

    /**
     * Este método devuelve el total de correos devueltos de una campaña 
     * así como el detalle por tipo de correo devuelto. 
     * @return object $datadevueltos valores de total_devueltos, motivo_desconocido, motivo_rechazado_mail_malo,
     * motivo_dominio_no_responde, motivo_casilla_llena_o_temporal,motivo_considerado_spam
     */
    public function campanadevueltos(){
        $soap_request = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="'.get_config('message_emma', 'emma_wsdl').'">
            <soapenv:Header/>
            <soapenv:Body>
                <ns1:campanadevueltos xmlns:ns1="EMMA">
                <ns1:idempresa>'.$this->idempresa.'</ns1:idempresa>
                <ns1:clave>'.$this->clave.'</ns1:clave>
                <ns1:idcampana>'.$this->idcampana.'</ns1:idcampana>                                
                </ns1:campanadevueltos>
            </soapenv:Body>
        </soapenv:Envelope>';

        $headers    = \emma\message\utils::headers_curl($soap_request);
        $url        = get_config('message_emma', 'emma_webservices');                
        $output     = utils::send_curl($url,$headers,$soap_request);  
        $devueltos  = self::clean_xml($output);
        $datadevueltos = [];

        if($devueltos->faultcode){
            $error = (string) $devueltos->faultstring;
            throw new \Exception('Ocurrió un error obteniendo datos del servicio "campanadevueltos" Emma. Error: "'.$error. '" Codigo: '.$devueltos->faultcode);
        }else{
            $datadevueltos = [
                'total_devueltos'                   => (string) $devueltos->total_devueltos[0],
                'motivo_desconocido'                => (string) $devueltos->motivo_desconocido[0],
                'motivo_rechazado_mail_malo'        => (string) $devueltos->motivo_rechazado_mail_malo[0],
                'motivo_dominio_no_responde'        => (string) $devueltos->motivo_dominio_no_responde[0],
                'motivo_casilla_llena_o_temporal'   => (string) $devueltos->motivo_casilla_llena_o_temporal[0],
                'motivo_considerado_spam'           => (string) $devueltos->motivo_considerado_spam[0],
            ];
        }
        return $datadevueltos; 
    }

    /**
     * Este método devuelve 5 strings ; 
     * tema de la campaña, 
     * fecha del último envío, 
     * hora del último envío, 
     * fecha finalización y hora finalización del último envío.
     * @return object $campanainfo valores con los detalles de la ultima campaña
     */
    public function campanainfo(){               
        $soap_request = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="'.get_config('message_emma', 'emma_wsdl').'">
            <soapenv:Header/>
            <soapenv:Body>
                <ns1:campanainfo xmlns:ns1="EMMA">
                <ns1:idempresa>'.$this->idempresa.'</ns1:idempresa>
                <ns1:clave>'.$this->clave.'</ns1:clave>
                <ns1:idcampana>'.$this->idcampana.'</ns1:idcampana>                                
                </ns1:campanainfo>
            </soapenv:Body>
        </soapenv:Envelope>';

        $headers    = \emma\message\utils::headers_curl($soap_request);
        $url        = get_config('message_emma', 'emma_webservices');                
        $output     = utils::send_curl($url,$headers,$soap_request);        
        $campanainfo  = self::clean_xml($output);
        $datacampanainfo = [];

        if($campanainfo->faultcode){
            $error = (string) $campanainfo->faultstring;
            throw new \Exception('Ocurrió un error obteniendo datos del servicio "campanainfo" de Emma. Error: "'.$error. '" Codigo: '.$campanainfo->faultcode);
        }else{
            $datacampanainfo = [
                'tema'      => (string) $campanainfo->tema[0],
                'fecha'     => (string) $campanainfo->fecha[0],
                'hora'      => (string) $campanainfo->hora[0],
                'fechafin'  => (string) $campanainfo->fechafin[0],
                'horafin'   => (string) $campanainfo->horafin[0],
            ];
        }

        return $datacampanainfo; 
    }

    /**
     * Este método permite solicitar la generación de un reporte de resultado general de una campaña, 
     * el cual es enviado en formato CSV al correo indicado. 
     * Reporta sobre el último envío de la campaña solicitada.
     * El CSV provee; nombre de la campaña, asunto, hora inicio envío, hora fin envío, 
     * mails enviados, mails entregados, mails rebotados, aperturas únicas, aperturas totales, 
     * tasa apertura desktop, tasa apertura mobile y otros, clics únicos por evento y clics totales por evento.
     * @return object $clean_xml valor de respuesta OK si el envío fue exitoso
     */
    public function campanareporteenvio($email, $fdesde, $fhasta){
        $soap_request = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="'.get_config('message_emma', 'emma_wsdl').'">
            <soapenv:Header/>
            <soapenv:Body>
                <ns1:campanareporteenvio xmlns:ns1="EMMA">
                <ns1:idempresa>'.$this->idempresa.'</ns1:idempresa>
                <ns1:clave>'.$this->clave.'</ns1:clave>
                <ns1:idcampana>'.$this->idcampana.'</ns1:idcampana>
                <ns1:email>'.$email.'</ns1:email>
                <ns1:fechaini>'.$fdesde.'</ns1:fechaini>
                <ns1:fechafin>'.$fhasta.'</ns1:fechafin>
                </ns1:campanareporteenvio>
            </soapenv:Body>
        </soapenv:Envelope>';

        $headers    = \emma\message\utils::headers_curl($soap_request);
        $url        = get_config('message_emma', 'emma_webservices');                
        $output     = utils::send_curl($url,$headers,$soap_request);        
        $clean_xml  = self::clean_xml($output);

        if($clean_xml->faultcode){
            $error = (string) $clean_xml->faultstring;
            throw new \Exception('Ocurrió un error en el servicio "campanareporteenvio" Emma. Error: "'.$error. '" Codigo: '.$clean_xml->faultcode);
            return false;
        }else{ 
            return true;     
        }
    }

    /**
     * Este método reporta el resultado general de los correos enviados, 
     * indicando los correos rebotados y filtrando los dominios conflictivos,
     * detallando la cantidad de correos devueltos por los motivos:
     * 1 :    'Desconocido'
     * 2 :    'Rechazado, probable mail erróneo'
     * 3 :    'Dominio no existe o no responde'
     * 4 :    'Falla temporal o casilla llena'
     * 5 :    'Considerado SPAM’     
     * @return array $datacampanaresultadoevento con los dominios, cantidades y motivos de devolucion
     */
    public function campanaresultadoevento(){               
        $soap_request = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="'.get_config('message_emma', 'emma_wsdl').'">
            <soapenv:Header/>
            <soapenv:Body>
                <ns1:campanaresultadoevento xmlns:ns1="EMMA">
                <ns1:idempresa>'.$this->idempresa.'</ns1:idempresa>
                <ns1:clave>'.$this->clave.'</ns1:clave>
                <ns1:idcampana>'.$this->idcampana.'</ns1:idcampana>
                </ns1:campanaresultadoevento>
            </soapenv:Body>
        </soapenv:Envelope>';

        $headers    = \emma\message\utils::headers_curl($soap_request);
        $url        = get_config('message_emma', 'emma_webservices');                
        $output     = utils::send_curl($url,$headers,$soap_request);        
        $campanaresultadoevento  = self::clean_xml($output);
        $datacampanaresultadoevento = [];

        if($campanaresultadoevento->faultcode){
            $error = (string) $campanaresultadoevento->faultstring;
            throw new \Exception('Ocurrió un error obteniendo datos del servicio "campanaresultadoevento" de Emma. Error: "'.$error. '" Codigo: '.$campanaresultadoevento->faultcode);
        }else{
            $mail_desconocidos = [];
            $mail_rechazados = [];
            $mail_dominio = [];
            $mail_falla = [];
            $mail_spam = [];
            $motivo_desconocidos = '';
            $motivo_rechazados = '';
            $motivo_dominio = '';
            $motivo_falla = '';
            $motivo_spam = '';

            foreach($campanaresultadoevento->resultado->Lista as $lista){

                if ($lista->devuelto != 0){
                    switch($lista->devuelto){
                        case 1: 
                            $emailDesconocidos = explode('@',$lista->email);
                            array_push($mail_desconocidos, $emailDesconocidos[1]);
                            $motivo_desconocidos = 'Desconocido';
                            break;
                        case 2: 
                            $emailRechazados = explode('@',$lista->email);
                            array_push($mail_rechazados, $emailRechazados[1]);
                            $motivo_rechazados = 'Rechazado, probable mail erróneo';
                            break;
                        case 3: 
                            $emailDominio = explode('@',$lista->email);
                            array_push($mail_dominio, $emailDominio[1]);
                            $motivo_dominio = 'Dominio no existe o no responde';
                            break;
                        case 4: 
                            $emailFalla = explode('@',$lista->email);
                            array_push($mail_falla, $emailFalla[1]);
                            $motivo_falla = 'Falla temporal o casilla llena';
                            break;
                        case 5: 
                            $emailSpam = explode('@',$lista->email);
                            array_push($mail_spam, $emailSpam[1]);
                            $motivo_spam =  'Considerado SPAM';
                            break;
                        default:
                            break;
                    }
                }     
            }
            $etiquetas = ['desconocidos','rechazados','dominio','falla','spam'];
            foreach($etiquetas as $etiqueta){
                $repetidos_mail = array_unique(${"mail_$etiqueta"});            
                foreach($repetidos_mail as $mail){
                    $counts = array_count_values(${"mail_$etiqueta"});
                    $data = [
                        'dominio'  => $mail,
                        'cantidad' => $counts[$mail],
                        'motivo'   => ${"motivo_$etiqueta"},
                    ];
                    array_push($datacampanaresultadoevento, $data);
                }
            }
        }
        return $datacampanaresultadoevento; 
    }

    /** 
     * Metodo que sustituye el envío nativo de restauración de Password de moodle
     * Este metodo se implementa dentro del Core de MOODLE en la ruta: lib/moodlelib.php
     * En la funcion send_password_change_confirmation_email()
     */
    public function send_password_email_moodle($subject,$message,$supportuser,$user){
        $messageEmma = new \stdClass();
        $messageEmma->userfrom = new \stdClass();
        $messageEmma->userto = new \stdClass();
        $messageEmma->subject           = $subject;
        $messageEmma->fullmessagehtml   = $message;
        //$messageEmma->fullmessage       = $message;
        $messageEmma->userfrom->id      = $supportuser->id;
        $messageEmma->userto->id        = $user->id;
        $messageEmma->userto->email     = $user->email;
        //$messageEmma->userfrom->email   = $supportuser->email;
        $messageEmma->fullmessageformat = 2;
        $attachment='';
        $send = $this->enviadirecto($messageEmma,$attachment);
        return $send;
    }
}