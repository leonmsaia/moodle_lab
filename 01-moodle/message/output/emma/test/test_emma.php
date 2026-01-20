<?php

include_once('../../../../config.php');
require_once('../classes/message_procesor.php');
/* 
$message = new \stdClass();
$message->subject = 'Hola';
$message->fullmessagehtml = '<h1>Test emma</h1>';
$message->fullmessage = 'Test emma';
$message->userfrom->id = 1;
$message->userto->id = 1;
$message->userto->email = 'alainj47@gmail.com';
$message->userfrom->email = 'alain@e-abclearning.com';
$message->fullmessageformat = 2;
$attachment=''; */

$message_procesor = new \emma\message\message_procesor();

/* $send = $message_procesor->enviadirecto($message,$attachment);
var_dump($send); */

$campanaresultadoevento = $message_procesor->campanaresultadoevento();
/* $devueltos              = $message_procesor->campanadevueltos();
$campanainfo            = $message_procesor->campanainfo();
$campanareporteenvio    = $message_procesor->campanareporteenvio('alainj47@gmail.com');



echo "CAMPAÑA DEVUELTOS: <pre>"; print_r($devueltos);
echo "CAMPAÑA INFO: <pre>"; print_r($campanainfo);
echo "CAMPAÑA ENVIO: <pre>"; print_r($campanareporteenvio->campanareporteenvio[0]); */