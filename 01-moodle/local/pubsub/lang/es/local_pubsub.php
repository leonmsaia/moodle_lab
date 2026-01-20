<?php
// (13/12/2019)

$string['pluginname'] = 'Publish subscribe';
$string['get_message'] = 'Get message';

$string['conectionstring'] = 'Conection string';
$string['conectionstring-empty'] = 'Error: El conection string esta vacio. Deben cambiarse las configuraciones del pluggin';
$string['tokenapi'] = 'Token API';
$string['subscriptionkey'] = 'Subscription key';
$string['subscription'] = 'Subscription';
$string['subscription-empty'] = 'Error: La subscripcion esta vacia. Deben cambiarse las configuraciones del pluggin';
$string['topic'] = 'Topic';
$string['topic-empty'] = 'Error: El topic esta vacio. Deben cambiarse las configuraciones del pluggin';

$string['configempty'] = 'Error no se configuró {$a}';
$string['highsessions'] = 'Alta de sesiones';
$string['guidnotfound'] = 'Guid de curso no encontrado en moodle';
$string['guidnotfoundback'] = 'Guid de curso no encontrado en back';
$string['coursenotattendance'] = 'Este curso no tiene creada la actividad asistencia';
$string['activitynoconfigure'] = 'activity is not configured properly SEPARATEGROUPS';
$string['validatedatareisterfacilitator'] = 'Error not sending all mandatory data: Identificador, First Nombre, ApellidoPaterno, CorreoElectronico';

$string['pubsub:configpubsub'] = 'Configurar local pubsub';
$string['usuarionotexist'] = 'Usuario no registrado en esta sesión';
$string['idsesionnoregister'] = 'Id de sesión no registrada';

$string['validatehuidandgroup'] = 'La sesión con id {$a} no tiene guid asociado o no tiene grupo.';
$string['validate-endpointupdatesession'] = 'No se configuro endpointupdatesession.';

$string['days'] = 'Días';
$string['days_desc'] = 'Dias para aprobar el curso';
$string['task_end_training'] = 'Finalizar capacitacion Culminados elearning';
$string['batchsize'] = 'Tamaño del lote (batch)';
$string['batchsize_desc'] = 'Número de registros a procesar en cada lote de la tarea programada. Afecta a las tareas de finalización de e-learning.';
$string['datestart'] = 'Fecha de inicio del filtro (opcional)';
$string['datestart_desc'] = 'Opcional. Si se establece, la tarea solo procesará inscripciones creadas después de esta fecha (formato YYYY-MM-DD).';
$string['dateend'] = 'Fecha de fin del filtro (opcional)';
$string['dateend_desc'] = 'Opcional. Si se establece, la tarea solo procesará inscripciones creadas antes de esta fecha (formato YYYY-MM-DD).';
$string['use_batch_processing_for_elearning'] = 'Usar el procesamiento de lotes para la finalización de e-learning';
$string['use_batch_processing_for_elearning_desc'] = 'Si se habilita, la tarea optimizada de procesamiento de lotes se usara para la finalización de inscripciones pendientes de e-learning.';
$string['task_end_training_pendientes'] = 'Finalizar capacitacion Pendientes elearning';
$string['processing_mode'] = 'Modo de procesamiento para finalización de e-learning';
$string['processing_mode_desc'] = 'Selecciona la estrategia para enviar los datos de finalización a la API externa. "Síncrono" los envía uno por uno, "Paralelo" los envía en lotes paralelos, y "Webhook" envía un único payload con todos los datos.';
$string['processing_mode_sync'] = 'Síncrono (uno por uno)';
$string['processing_mode_parallel'] = 'Paralelo (en lotes)';
$string['processing_mode_webhook'] = 'Webhook (lote único)';
$string['parallel_batch_size'] = 'Tamaño del lote paralelo para API';
$string['parallel_batch_size_desc'] = 'Si se usa el modo "Paralelo", este es el número de peticiones que se enviarán simultáneamente a la API.';
$string['filter_pendientes_by_timecompleted'] = 'Filtrar inscripciones pendientes por completado';
$string['filter_pendientes_by_timecompleted_desc'] = 'Si se activa, las inscripciones pendientes se filtrarán por el tiempo de completado. Por defecto, se filtran por el tiempo de creación.';
$string['curso_presencial_active'] = 'Activar curso presencial para la creación ws';
$string['curso_semi_presencial_active'] = 'Activar curso Semi-Presencial para la creación ws';
$string['curso_distancia_active'] = 'Activar curso Distancia para la creación ws';
$string['curso_distancia_elearning_active'] = 'Activar curso Distancia Elearning para la creación ws';
$string['curso_distancia_streaming_active'] = 'Activar curso Distancia streaming para la creación ws';
$string['curso_distancia_mobile_active'] = 'Activar curso Distancia mobile para la creación ws';
$string['errormsg'] = '{$a}';