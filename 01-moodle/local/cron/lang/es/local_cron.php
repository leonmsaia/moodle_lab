<?php

$string['pluginname'] = 'Local cron';
$string['formatnocorrect'] = 'La información enviada no tiene un formato correcto';
$string['response'] = '
<?xml version="1.0" standalone="yes"?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
   <S:Body>
      <ns0:solicitudInscripcionResponse xmlns:ns0="http://cl.mutual.ws/">
         <return>
            <respuesta>
                <codigo>0</codigo>
                <mensaje></mensaje>
		<idInscripcion>{$a->enrolid}</idInscripcion>
            </respuesta>
         </return>
      </ns0:solicitudInscripcionResponse>
   </S:Body>
</S:Envelope>
';

$string['error_message'] = '
<?xml version="1.0" standalone="yes"?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
   <S:Body>
      <ns0:solicitudInscripcionResponse xmlns:ns0="http://cl.mutual.ws/">
         <return>
            <respuesta>
                <codigo>0</codigo>
                <mensaje>{$a->message}</mensaje>
		<idInscripcion>0</idInscripcion>
            </respuesta>
         </return>
      </ns0:solicitudInscripcionResponse>
   </S:Body>
</S:Envelope>
';

$string['user_already_enrolment'] = '<?xml version="1.0" encoding="UTF-8"?>
    <S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
	<S:Body>
            <ns0:solicitudInscripcionResponse xmlns:ns0="http://cl.mutual.ws/">
            <return>
                <respuesta>
                    <codigo>10</codigo>
                    <mensaje>Alumno ya tiene el curso activo y con estado cursando</mensaje>
                    <idInscripcion>0</idInscripcion>
                </respuesta>
            </return>
	</ns0:solicitudInscripcionResponse>
    </S:Body>
</S:Envelope>';

//comuna desarrollo
/*
 * $string['body_xml_to_encrypt'] = '<?xml version="1.0" encoding="UTF-8"?><capacitacion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><cabecera><id_inscripcion>{$a->enrol_id}</id_inscripcion><fecha_inicio>{$a->dateroltime}</fecha_inicio><fecha_fin>{$a->courseCompletionDate}</fecha_fin></cabecera><participante><tipo_documento>{$a->participantetipodocumento}</tipo_documento><identificador_documento>{$a->participantedocumento}</identificador_documento><nombres>{$a->firstname}</nombres><apellido_paterno>{$a->lastname}</apellido_paterno><apellido_materno>{$a->lastnamephonetic}</apellido_materno><email>{$a->email}</email><resultado>{$a->resultado}</resultado><motivo_resultado>{$a->motivo_resultado}</motivo_resultado><nota_1>{$a->calculate_average_seven}</nota_1><nota_2>{$a->calculate_average_hundred}</nota_2><comuna>{$a->comuna}</comuna></participante></capacitacion>';
 */

$string['body_xml_to_encrypt'] = '<?xml version="1.0" encoding="UTF-8"?><capacitacion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><cabecera><id_inscripcion>{$a->enrol_id}</id_inscripcion><fecha_inicio>{$a->dateroltime}</fecha_inicio><fecha_fin>{$a->courseCompletionDate}</fecha_fin></cabecera><participante><tipo_documento>{$a->participantetipodocumento}</tipo_documento><identificador_documento>{$a->participantedocumento}</identificador_documento><nombres>{$a->firstname}</nombres><apellido_paterno>{$a->lastname}</apellido_paterno><apellido_materno>{$a->lastnamephonetic}</apellido_materno><email>{$a->email}</email><resultado>{$a->resultado}</resultado><motivo_resultado>{$a->motivo_resultado}</motivo_resultado><nota_1>{$a->calculate_average_seven}</nota_1><nota_2>{$a->calculate_average_hundred}</nota_2></participante></capacitacion>';

$string['null_response'] = <<<XML
<?xml version='1.0' standalone='yes'?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
   <S:Body>
      <ns0:solicitudInscripcionResponse xmlns:ns0="http://cl.mutual.ws/">
         <return>
            <respuesta>
                <codigo>0</codigo>
                <mensaje>Request vacio</mensaje>
		<idInscripcion>0</idInscripcion>
            </respuesta>
         </return>
      </ns0:solicitudInscripcionResponse>
   </S:Body>
</S:Envelope>
XML;

$string['days'] = 'Días';
$string['days_desc'] = 'Dias para aprobar el curso';

$string['days_enrol'] = 'Días para rematricular';
$string['days_enrol_desc'] = 'Dias para rematricular';

$string['task_end_training'] = 'Finalizar capacitacion elearning';
$string['clean_table_log'] = 'Borrar tabla de registro finalizacion cron';

$string['mark_reaggregate'] = 'Marcar reaggregate finalización';
$string['reaggregate_date_from'] = 'Marcar reaggregate fecha desde';
$string['reaggregate_date_from_desc'] = 'La fecha tiene que estar en timestamp, esta configuración es necesaria.';
$string['reaggregate_date_to'] = 'Marcar reaggregate fecha hasta';
$string['reaggregate_date_to_desc'] = 'La fecha tiene que estar en timestamp en caso de no tener esta configuración la fecha final sera la actual';

$string['clear_local_cron'] = 'Limpiar registros local cron';