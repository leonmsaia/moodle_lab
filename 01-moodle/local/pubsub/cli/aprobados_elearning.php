<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
use local_pubsub\utils;

global $DB, $CFG;

list($options, $unrecognized) = cli_get_params(
    array(
        'lote' => 0,
        'total' => 0,
        'count' => 0,
        'minid' => 0,
        'maxid' => 0,
        'help' => false
    ),
    array('l' => 'lote', 't' => 'total', 'c' => 'count', 'min' => 'minid', 'max' => 'maxid', 'h' => 'help')
);

if ($options['help']) {
    echo "
    Options:
    -l, --lote     Número de lote a procesar
    -t, --total    Número total de hilos
    -c, --count    Total de registros a procesar
    --minid        ID mínimo a procesar
    --maxid        ID máximo a procesar

    Example:
    php local/pubsub/cli/aprobados_elearning.php --lote=1 --t=15 --c=82500 --minid=1001 --maxid=391234
    ";
    exit(0);
}

$lote  = (int)$options['lote'];
$hilos = (int)$options['total'];
$total = (int)$options['count'];
$minid = (int)$options['minid'];
$maxid = (int)$options['maxid'];

if (!$lote || !$hilos || !$total || !$minid || !$maxid) {
    echo "Faltan parámetros requeridos (--lote, --total, --count, --minid, --maxid)\n";
    exit(1);
}

// Calcular rango de IDs por lote
$rango_por_lote = ceil(($maxid - $minid + 1) / $hilos);
$id_inicio = $minid + ($lote - 1) * $rango_por_lote;
$id_fin    = $id_inicio + $rango_por_lote - 1;

echo "Lote $lote procesará IDs desde $id_inicio hasta $id_fin\n";

// Obtener los registros de este rango
$sql = "SELECT
    ieb.participanteidregistroparticip AS idregistroparticipante,
    ROUND(gg.finalgrade) AS calificacion,
    ieb.id_user_moodle AS usuarioid,
    ieb.id_curso_moodle AS cursoid
FROM mdl_inscripcion_elearning_back ieb
JOIN mdl_course_completions cc ON cc.course = ieb.id_curso_moodle AND cc.userid = ieb.id_user_moodle
JOIN mdl_grade_items gi ON gi.courseid = cc.course AND gi.itemtype = 'course'
LEFT JOIN mdl_grade_grades gg ON gg.itemid = gi.id AND gg.userid = cc.userid
WHERE ieb.timereported = 0 
  AND cc.timecompleted IS NOT NULL
  AND gg.finalgrade > 75
  AND ieb.id BETWEEN :id_inicio AND :id_fin
ORDER BY ieb.id DESC";

$params = ['id_inicio' => $id_inicio, 'id_fin' => $id_fin];
$enrollments = $DB->get_records_sql($sql, $params);

if (!$enrollments) {
    echo "Lote $lote sin registros para procesar.\n";
    exit(0);
}

$procesados = 0;

foreach ($enrollments as $enrol) {
    $inscrito_elearning     = $enrol->idregistroparticipante;
    $calificacion           = $enrol->calificacion;
    $userid_moodle          = $enrol->usuarioid;
    $cursoid_moodle         = $enrol->cursoid;
    $observacionInscripcion = "Terminó todo y aprobó";
    $hoy                    = utils::date_utc();
    $nota                   = (($calificacion - 75) * 0.12) + 4;

    $query_elearning_back = $DB->get_record("inscripcion_elearning_back", ["participanteidregistroparticip" => $inscrito_elearning]);

    if ($query_elearning_back && $query_elearning_back->timereported == 0) {
        $data = [
            "IdRegistroParticipante"    => $inscrito_elearning,
            "NotaEvaluacion"            => floatval($nota),
            "NotaEvaluacionPorcentaje"  => floatval($calificacion),
            "Asistencia"                => 100,
            "Resultado"                 => 1,
            "FechaTemrinoResultado"     => $hoy,
            "Observacion"               => $observacionInscripcion,
        ];

        var_dump(" Data a enviar: ",$data);
    
        $DB->set_field('inscripcion_elearning_back', 'timereported', 1, array('participanteidregistroparticip' => $inscrito_elearning));

        $endpoint = get_config('local_pubsub', 'endpointcierreparticipantes');
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, $endpoint);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
            'Authorization: ' . get_config('local_pubsub', 'tokenapi'),
            'Ocp-Apim-Subscription-Key: ' . get_config('local_pubsub', 'subscriptionkey'),
            'Content-Type:application/json'
        ));
        $response = curl_exec($cURLConnection);
        $httpcode = curl_getinfo($cURLConnection, CURLINFO_HTTP_CODE);
        curl_close($cURLConnection);

        var_dump($response);
        var_dump($httpcode);
        if ($httpcode > 299) {
            $event = \local_pubsub\event\cierre_capacitacion_elearning::create(
                array(
                    'context' => \context_system::instance(),
                    'other' => array(
                        'error' => 'Error en respuesta de webservices back, response: ' . $response,
                        'IdRegistroParticipante' => $inscrito_elearning
                    ),
                )
            );
            $event->trigger();
            $error = "No proceso por: ".'Error en respuesta de webservices back, response: ' . $response. 'IdRegistroParticipante: '. $inscrito_elearning;
            var_dump($error);        
            $DB->set_field('inscripcion_elearning_back', 'timereported', 0, array('participanteidregistroparticip' => $inscrito_elearning));
            
        } else {
            $datos_log = [
                'id_user_moodle'    => $userid_moodle,
                'id_curso_moodle'   => $cursoid_moodle,
                "id_registro_participante" => $inscrito_elearning,
                "nota_evaluacion"   => floatval($nota),
                "nota_evaluacion_porcentaje" => floatval($calificacion),
                "asistencia"        => 100,
                "resultado"         => 1,
                "fecha_temrino_resultado" => $hoy,
                "observacion"       => $observacionInscripcion,
                "createdat"         => time()
            ];
            $DB->insert_record('cierre_elearning_back_log', $datos_log);
            var_dump($datos_log);
            $DB->set_field('inscripcion_elearning_back', 'timereported', time(), array('participanteidregistroparticip' => $inscrito_elearning));
            $event = \local_pubsub\event\cierre_capacitacion_elearning::create(
                array(
                    'context' => \context_system::instance(),
                    'other' => array(
                        'response' => 'Cierre de capacitación con nota: ' . $calificacion . ' Resultado: ' . 1 . 'Obervación: ' . $observacionInscripcion,
                        'IdRegistroParticipante' => $inscrito_elearning
                    ),
                )
            );
            $event->trigger(); 
            $procesados++;
        }
    }
}
echo "\n Fin de la ejecución. Procesados: ".$procesados. " de un total de: ".count($enrollments)."\n\n";