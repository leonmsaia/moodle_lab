<?php


$string['pluginname'] = 'Publish subscribe';
$string['get_message'] = 'Get message';

$string['conectionstring'] = 'Conection string';
$string['conectionstring-empty'] = 'Error: Connection string is empty. You should change the pluggin settings';
$string['tokenapi'] = 'Token API';
$string['subscriptionkey'] = 'Subscription key';
$string['subscription'] = 'Subscription';
$string['subscription-empty'] = 'Error: Subscription is empty. You should change the pluggin settings';
$string['topic'] = 'Topic';
$string['topic-empty'] = 'Error: Topic is empty. You should change the pluggin settings';

$string['configempty'] = 'Error was not configured {$a}';
$string['highsessions'] = 'High Sessions';
$string['guidnotfound'] = 'Course guid not found in moodle';
$string['guidnotfoundback'] = 'Course guid not found in back';
$string['coursenotattendance'] = 'This course has not created the assistance activity';
$string['activitynoconfigure'] = 'activity is not configured properly SEPARATEGROUPS';
$string['validatedatareisterfacilitator'] = 'Error no se envian todos los datos obligatorios: Identificador, Nombre, ApellidoPaterno, CorreoElectronico';

$string['pubsub:configpubsub'] = 'Config local pubsub';
$string['usuarionotexist'] = 'User not registered in this session';
$string['idsesionnoregister'] = 'Sessionid not registered';

$string['validatehuidandgroup'] = 'La sesión con id {$a} no tiene guid asociado o no tiene grupo.';
$string['validate-endpointupdatesession'] = 'No se configuro endpointupdatesession.';

$string['days'] = 'Días';
$string['days_desc'] = 'Dias para aprobar el curso';
$string['task_end_training'] = 'Finalizar capacitacion Culminados elearning';
$string['batchsize'] = 'Batch size';
$string['batchsize_desc'] = 'Number of records to process in each batch of the scheduled task. Affects e-learning completion tasks.';
$string['datestart'] = 'Filter start date (optional)';
$string['datestart_desc'] = 'Optional. If set, the task will only process enrollments created after this date (format YYYY-MM-DD).';
$string['dateend'] = 'Filter end date (optional)';
$string['dateend_desc'] = 'Optional. If set, the task will only process enrollments created before this date (format YYYY-MM-DD).';
$string['use_batch_processing_for_elearning'] = 'Use batch processing for e-learning finalization';
$string['use_batch_processing_for_elearning_desc'] = 'If enabled, the optimized batch processing task will be used to finalize pending e-learning enrollments.';
$string['task_end_training_pendientes'] = 'Finalizar capacitacion Pendientes elearning';
$string['processing_mode'] = 'E-learning finalization processing mode';
$string['processing_mode_desc'] = 'Selects the strategy to send completion data to the external API. "Sync" sends them one by one, "Parallel" sends them in parallel batches, and "Webhook" sends a single payload with all data.';
$string['processing_mode_sync'] = 'Synchronous (one by one)';
$string['processing_mode_parallel'] = 'Parallel (in batches)';
$string['processing_mode_webhook'] = 'Webhook (single batch)';
$string['parallel_batch_size'] = 'API parallel batch size';
$string['parallel_batch_size_desc'] = 'If "Parallel" mode is used, this is the number of requests to send simultaneously to the API.';
$string['filter_pendientes_by_timecompleted'] = 'Filter pending enrollments by time completed';
$string['filter_pendientes_by_timecompleted_desc'] = 'If enabled, pending enrollments will be filtered by time completed. By default, they are filtered by creation time.';
$string['curso_presencial_active'] = 'Activar curso presencial para la creación ws';
$string['curso_semi_presencial_active'] = 'Activar curso Semi-Presencial para la creación ws';
$string['curso_distancia_active'] = 'Activar curso Distancia para la creación ws';
$string['curso_distancia_elearning_active'] = 'Activar curso Distancia Elearning para la creación ws';
$string['curso_distancia_streaming_active'] = 'Activar curso Distancia streaming para la creación ws';
$string['curso_distancia_mobile_active'] = 'Activar curso Distancia mobile para la creación ws';
$string['errormsg'] = '{$a}';