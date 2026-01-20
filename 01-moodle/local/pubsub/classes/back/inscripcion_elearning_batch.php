<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_pubsub\back;

defined('MOODLE_INTERNAL') || die;

use stdClass;
use local_pubsub\utils;

/**
 * Class for processing inscripcion_elearning data in batches
 */
class inscripcion_elearning_batch extends inscripcion_elearning {

    const RESULTADO_APROBADO = 1;
    const RESULTADO_REPROBADO = 2;

    const ASISTENCIA_COMPLETA = 100;
    const ASISTENCIA_NULA = 0;

    const OBSERVACION_APROBADO = "Terminó todo y aprobó";
    const OBSERVACION_REPROBADO_NOTA_BAJA = "Reprobado por nota baja";
    const OBSERVACION_REPROBADO_INASISTENCIA = "Reprobado por inasistencia";

    const PROCESSING_MODE_SYNC = 'sync';
    const PROCESSING_MODE_PARALLEL = 'parallel';
    const PROCESSING_MODE_WEBHOOK = 'webhook';

    /**
     * @var array Búfer para acumular solicitudes de cierre en modos 'parallel' o 'webhook'.
     */
    private $cierrebuffer = [];

    /**
     * @var int Contador para los errores durante el procesamiento.
     */
    private $errorcount = 0;

    /**
     * @var stdClass Almacenamiento en caché de la configuración del plugin.
     */
    private $config = null;

    /**
     * Constructor for inscripcion_elearning_batch class.
     * @param stdClass $config Optional configuration object.
     * If not provided, the configuration will be retrieved from the database.
     */
    public function __construct($config = null) {
        if (!empty($config)) {
            $this->config = $config;
        } else {
            $this->config = get_config('local_pubsub');
        }
        if (empty($this->config->processing_mode)) {
            $this->config->processing_mode = self::PROCESSING_MODE_SYNC;
        }
        if (empty($this->config->parallel_batch_size)) {
            $this->config->parallel_batch_size = 20;
        }
        if (!isset($this->config->filter_pendientes_by_timecompleted)) {
            $this->config->filter_pendientes_by_timecompleted = true; // Valor seguro por defecto.
        }
    }

    /**
     * Return an array of processing modes for e-learning finalization.
     *
     * @return array Array with processing mode strings as keys and their
     * corresponding translations as values.
     */
    public static function get_processing_modes() {
        return [
            self::PROCESSING_MODE_SYNC => get_string(
                'processing_mode_sync', 'local_pubsub'
            ),
            self::PROCESSING_MODE_PARALLEL => get_string(
                'processing_mode_parallel', 'local_pubsub'
            ),
            self::PROCESSING_MODE_WEBHOOK => get_string(
                'processing_mode_webhook', 'local_pubsub'
            )
        ];
    }

    /**
     * Process inscripcion_elearning data in batches
     *
     * @param int $last_execute the last time the batch was executed
     * @param int $days the number of days to process
     * @param string $participante_condicion the condition for the participante
     * @param int $batchsize the batch size
     * @param string $datestart the start date
     * @param string $dateend the end date
     * @return int
     */
    public function finalizar_elearning_batch(
            $last_execute,
            $days,
            $participante_condicion,
            $batchsize = 0,
            $datestart = null,
            $dateend = null)
        {

        // @codingStandardsIgnoreLine
        /** @var \moodle_database $DB */
        global $DB, $CFG;

        $this->cierrebuffer = [];
        $this->errorcount = 0;

        $today = time();
        $lockid = $today;
        $batchsize = (int) $batchsize;
        $params = [];

        // Paso 1: Construir condiciones de filtrado.
        $condicionsql = ""; // Por defecto, no se filtra por estado de finalización.
        if ($participante_condicion == 'culminados') {
            $condicionsql = "AND !ISNULL(cc.timecompleted)";
        } else if (
                $participante_condicion == 'pendientes' &&       
                $this->config->filter_pendientes_by_timecompleted
            ) {
            // Si la configuración está activa, aplicamos el filtro estricto para 'pendientes'.
            $condicionsql = "AND ISNULL(cc.timecompleted)";
        } else if (
                $participante_condicion == 'pendientes' &&
                !$this->config->filter_pendientes_by_timecompleted
            ) {
            // Si la configuración está inactiva, no se aplica filtro por 'timecompleted' para pendientes.
            // La lógica interna de procesar_inscripcion_individual se encargará de decidir qué hacer con cada registro.
            $condicionsql = "";
        }

        // Condición de fechas.
        $dateconditionsql = '';
        if (!empty($datestart)) {
            $dateconditionsql .= " AND ie.createdat >= :datestart";
            $params['datestart'] = $datestart;
        }
        if (!empty($dateend)) {
            $dateconditionsql .= " AND ie.createdat <= :dateend";
            $params['dateend'] = $dateend;
        }

        // PASO 2: Reclamar un lote atómicamente.
        // Primero, encontramos los IDs candidatos.
        $sqlfindids = "SELECT ie.id
                           FROM {inscripcion_elearning_back} ie
                           JOIN {course_completions} cc ON cc.userid = ie.id_user_moodle
                                                           AND cc.course = ie.id_curso_moodle
                          WHERE ie.timereported = 0
                                $condicionsql
                                $dateconditionsql
                       ORDER BY ie.id ASC";

        $idstoprocess = $DB->get_records_sql_menu($sqlfindids, $params, 0, $batchsize);

        if (empty($idstoprocess)) {
            mtrace("No se encontraron registros para procesar.");
            return 0; // No hay nada que procesar.
        }

        // Convertimos el array de [id => id] a [id1, id2, ...].
        $idslist = array_keys($idstoprocess);

        // Obtenemos un placeholder SQL para la cláusula IN().
        list($insql, $inparams) = $DB->get_in_or_equal($idslist, SQL_PARAMS_NAMED, 'id');

        // Ahora, reclamamos estos IDs de forma atómica.
        $sqlclaim = "UPDATE {inscripcion_elearning_back}
                         SET timereported = :timereported
                       WHERE id $insql
                             AND timereported = 0";

        $inparams['timereported'] = $lockid;

        $DB->execute($sqlclaim, $inparams);

        // PASO 3: Pre-cargar (Pre-fetch) todos los datos para el lote reclamado.
        list($insql, $inparams) = $DB->get_in_or_equal($idslist, SQL_PARAMS_NAMED, 'param');

        $sqlprefetch = "SELECT ie.id, ie.id_user_moodle,
                               ie.id_curso_moodle,
                               ie.participanteidregistroparticip,
                               cc.timecompleted AS cc_timecompleted,
                               ue.timecreated AS ue_timecreated,
                               ue.timestart AS ue_timestart,
                               gi.id AS gi_id, gi.gradepass,
                               gg.finalgrade, gg.timemodified AS gg_timemodified,
                               gg.timecreated AS gg_timecreated
                          FROM {inscripcion_elearning_back} ie
                          JOIN {course_completions} cc ON cc.userid = ie.id_user_moodle
                                                      AND cc.course = ie.id_curso_moodle
                          JOIN {enrol} e ON e.courseid = ie.id_curso_moodle
                                        AND e.enrol = 'manual'
                          JOIN {user_enrolments} ue ON ue.enrolid = e.id
                                                   AND ue.userid = ie.id_user_moodle
                          JOIN {grade_items} gi ON gi.courseid = ie.id_curso_moodle
                                               AND gi.itemtype = 'course'
                          JOIN {grade_grades} gg ON gg.itemid = gi.id
                                                AND gg.userid = ie.id_user_moodle
                         WHERE ie.id $insql";

        $enrollments = $DB->get_records_sql($sqlprefetch, $inparams);

        if (empty($enrollments)) {
            // Esto no debería pasar si el UPDATE fue exitoso, pero es una guarda de seguridad.
            // Significa que reclamamos registros pero no pudimos leerlos.
            // Dejamos el timereported en $lockid para investigarlo.
            mtrace("ADVERTENCIA: Se reclamaron " . count($idslist), " ");
            mtrace("registros (Lock ID: $lockid) pero no se pudieron leer para procesar.");
            return 0;
        }

        // PASO 4: Bucle de procesamiento (Ahora solo lógica de negocio y API).
        $processedcount = 0;
        $gradevalue = $CFG->gradevalue ?? false;
        foreach ($enrollments as $enrol) {

            // Delegamos toda la lógica compleja a un método dedicado.
            // Esto mantiene el bucle principal limpio y legible.
            $resultado = $this->procesar_inscripcion_individual(
                $enrol, $days, $today, $gradevalue
            );

            if ($resultado) {
                $processedcount++;
            }

        }

        $this->flush_cierre_buffer();

        if ($this->errorcount > 0) {
            mtrace("-------------------------------------------------");
            mtrace("Total de registros con error de envío: " . $this->errorcount);
            mtrace("-------------------------------------------------");
        }
        // Devolvemos el recuento de procesados exitosamente.
        return $processedcount;

    }


    /**
     * Vacia el búfer de cierre con todos los registros pre-cargados.
     * Registra en el log que se está vaciando el búfer y el modo en que se está procesando.
     * Luego, devuelve el conteo del búfer vaciado.
     * @return int Contenido del búfer vaciado.
     */
    private function flush_cierre_buffer() {
        if (empty($this->cierrebuffer)) {
            return 0; // Nada que vaciar
        }

        mtrace("", "\n\n");
        mtrace("Vaciando búfer de cierre con " . count($this->cierrebuffer) . " registros. Modo: " . $this->config->processing_mode, "\n\n");

        switch ($this->config->processing_mode) {
            case self::PROCESSING_MODE_WEBHOOK:
            case self::PROCESSING_MODE_PARALLEL:
                $this->enviar_cierre_parallel($this->cierrebuffer, $this->config->parallel_batch_size);
                break;
        }

        $count = count($this->cierrebuffer);
        $this->cierrebuffer = []; // Limpiar búfer
        return $count; // Devolver cuántos se procesaron
    }

    /**
     * Procesa la lógica de negocio para una única inscripción pre-cargada.
     * Utiliza Cláusulas de Guarda para mejorar la legibilidad.
     *
     * @param stdClass $enrol Objeto con todos los datos pre-cargados
     * @param int $days_limite Los días configurados para el límite
     * @param int $today Timestamp de la ejecución actual
     * @param mixed $gradevalue Configuración global de $CFG->gradevalue
     * @return bool True si se envió a la API, False si falló o se omitió.
     */
    protected function procesar_inscripcion_individual(
            $enrol,
            $days_limite,
            $today,
            $gradevalue
        ) {

        // ----- 1. Validaciones (Cláusulas de Guarda) -----

        // Guardia 1: ¿Existe matrícula?
        if (empty($enrol->ue_timecreated)) {
            mtrace("No procesó. ID inscripcion_elearning_back: ".$enrol->id ." ... por: No se encontró en user_enrolments.");
            self::rolback_timereported($enrol->id);
            return false;
        }

        // Guardia 2: ¿Existe ítem de calificación del curso?
        if (empty($enrol->gi_id)) {
            mtrace("No procesó. ID inscripcion_elearning_back: ".$enrol->id ." ... por: no existe grade_item para el curso.");
            self::rolback_timereported($enrol->id);
            return false;
        }

        // ----- 2. Preparación de Datos -----

        $course_completion_time = $enrol->cc_timecompleted;

        // Si el curso está completado, usamos esa fecha. Si no, usamos la fecha de hoy.
        $today_process = !empty($course_completion_time) ? $course_completion_time : $today;

        // Determinar la fecha de inicio real de la matrícula
        $timestart = !empty($enrol->ue_timestart) ? $enrol->ue_timestart : $enrol->ue_timecreated;
        $days_passed_enrol = self::interval($timestart, $today_process);

        $finalgradeuser = $enrol->finalgrade; // Puede ser NULL
        $gradepassed = false;
        $days_passed_grade = 0;

        // Calcular si el usuario aprobó por nota
        if (!is_null($finalgradeuser) && (float)$finalgradeuser > (float)$enrol->gradepass) {
            $gradepassed = true;
            $gradedate = !empty($enrol->gg_timemodified) ? $enrol->gg_timemodified : $enrol->gg_timecreated;
            $days_passed_grade = self::interval($timestart, $gradedate);
        }

        // ----- 3. Lógica de Decisión -----

        $ha_excedido_tiempo = $days_passed_enrol > (intval($days_limite));
        $esta_completado = !empty($course_completion_time);

        // CASO 1: APROBADO / REPROBADO POR NOTA (Cursos completados)
        // El curso SÍ está completado O aprobó por nota aunque esté fuera de tiempo.
        $completo_formalmente = $esta_completado && ($enrol->ue_timecreated < $course_completion_time);
        $aprobo_por_nota_tardio = $ha_excedido_tiempo && $gradepassed && $days_passed_grade <= (intval($days_limite));

        // La condición original era: ($days_passed_enrol <= (intval($days)) || ($gradevalue && $gradepassed))
        // Vamos a verificar si califica para un cierre (Aprobado o Reprobado con nota)
        if ($completo_formalmente || $aprobo_por_nota_tardio) {

            // Guardia 3: Para este caso, DEBE tener una nota.
            if (is_null($finalgradeuser)) {
                mtrace("No procesó. ID: ".$enrol->id ." ... por: sin grades_user (finalgrade es NULL).");
                self::rolback_timereported($enrol->id);
                return false;
            }

            // ¡Éxito! Calcular Aprobado/Reprobado
            $nota_calculada = self::calc_nota($finalgradeuser, $enrol->gradepass);
            $resultado = self::RESULTADO_REPROBADO; // Reprobado por defecto.
            $observacion = self::OBSERVACION_REPROBADO_NOTA_BAJA;

            if (floatval($finalgradeuser) >= floatval($enrol->gradepass)) {
                $resultado = self::RESULTADO_APROBADO; // Aprobado.
                $observacion = self::OBSERVACION_APROBADO;
            }

            self::gestionar_envio_cierre(
                $enrol->participanteidregistroparticip,
                $nota_calculada,
                $finalgradeuser,
                $resultado,
                $today_process,
                $observacion,
                self::ASISTENCIA_COMPLETA,
                $enrol->id_user_moodle,
                $enrol->id_curso_moodle,
                $enrol->id
            );
            return true; // Enviado.
        }

        // CASO 2: REPROBADO POR INASISTENCIA
        // Excedió el tiempo límite Y no tiene registro de finalización de curso.
        if ($ha_excedido_tiempo && !$esta_completado) {
            $nota_final = (!is_null($finalgradeuser)) ? $finalgradeuser : 0;
            $nota_calculada = self::calc_nota($nota_final, $enrol->gradepass);

            $this->gestionar_envio_cierre(
                $enrol->participanteidregistroparticip,
                $nota_calculada,
                $nota_final,
                self::RESULTADO_REPROBADO, // Resultado 2 = Reprobado.
                $today_process,
                self::OBSERVACION_REPROBADO_INASISTENCIA,
                self::ASISTENCIA_NULA, // Asistencia 0.
                $enrol->id_user_moodle,
                $enrol->id_curso_moodle,
                $enrol->id
            );

            return true; // Enviado.
        }

        // ----- 4. Casos Omitidos (Rollback) -----

        // Si el código llega hasta aquí, significa que el registro:
        // - No excedió el tiempo (todavía está "pendiente").
        // - O cayó en una lógica inesperada (ej. excedió tiempo pero tiene 'timecompleted').
        // En cualquier caso, no se procesa y se revierte el bloqueo.

        $log_msg = " - No procesó (Omitido). ID: ".$enrol->id ." ... por: Aún en tiempo o condición no manejada.";
        if ($ha_excedido_tiempo && $esta_completado) {
            $log_msg = " - No procesó. ID: ".$enrol->id ." ... por: Excedió el tiempo pero tiene course_completion.timecompleted (condición inesperada).";
        }

        mtrace($log_msg);
        self::rolback_timereported($enrol->id);
        return false;
    }


    /**
     * Gestionar el envío de cierre de capacitación e-learning.
     *
     * @param int $inscrito_elearning El ID del registro de inscripción en {inscripcion_elearning_back}
     * @param int $finalgradeuser La nota final del usuario
     * @param int $porcentaje La nota como porcentaje
     * @param int $resultado El resultado de la capacitación (1 = Aprobado, 2 = Reprobado)
     * @param string $today La fecha actual en formato UTC
     * @param string $observacionInscripcion La observación asociada al registro de inscripción
     * @param int $asistencia El nivel de asistencia (0 = Sin asistencia)
     * @param int $userid_moodle El ID del usuario en la plataforma de Moodle
     * @param int $cursoid_moodle El ID del curso en la plataforma de Moodle
     * @param int $inscripcion_id El ID de la tabla {inscripcion_elearning_back}
     *
     * @return bool True si se envió correctamente, false en caso contrario
     */
    private function gestionar_envio_cierre(
            $inscrito_elearning,
            $finalgradeuser,
            $porcentaje,
            $resultado,
            $today,
            $observacionInscripcion,
            $asistencia,
            $userid_moodle,
            $cursoid_moodle,
            $inscripcion_id // El ID de la tabla {inscripcion_elearning_back}.
        ) {

        // 1. Construir el payload (datos que se enviarán)
        $payload = [
            "IdRegistroParticipante" => $inscrito_elearning,
            "NotaEvaluacion" => floatval(round($finalgradeuser < 1 ? 1 : $finalgradeuser)),
            "NotaEvaluacionPorcentaje" => floatval(round($porcentaje)),
            "Asistencia" => $asistencia,
            "Resultado" => $resultado,
            "FechaTemrinoResultado" => utils::date_utc(), // Usamos la fecha UTC de envío
            "Observacion" => $observacionInscripcion,
            // Datos meta para Moodle (logging y rollbacks)
            "_moodle" => [
                "inscripcion_id" => $inscripcion_id,
                "userid_moodle" => $userid_moodle,
                "cursoid_moodle" => $cursoid_moodle
            ]
        ];

        // 2. Decidir la estrategia (el "Strategy" pattern)
        switch ($this->config->processing_mode) {
            case self::PROCESSING_MODE_SYNC:
                // MODO 1: Sincrono (original)
                // Llamamos a la función de envío original.
                return self::enviar_cierre_course_back(
                    $inscrito_elearning,
                    $finalgradeuser,
                    $porcentaje,
                    $resultado,
                    $today,
                    $observacionInscripcion,
                    $asistencia,
                    $userid_moodle,
                    $cursoid_moodle
                );

            case self::PROCESSING_MODE_WEBHOOK:
            case self::PROCESSING_MODE_PARALLEL:
            default:
                // MODO 2 y 3: Añadir al búfer para procesar al final
                $this->cierrebuffer[] = $payload;
                return true; // Asumimos éxito (se añade al búfer)
        }
    }


    /**
     * Procesar el búfer de cierre en lotes paralelos
     *
     * @param array $payloads Arreglo de payloads a procesar
     * @param int $chunksize Tamaño del lote a procesar (paralelismo)
     */
    private function enviar_cierre_parallel($payloads, $chunksize) {
        $endpoint = $this->config->endpointcierreparticipantes;

        if (empty($endpoint)) {
            throw new \core\exception\moodle_exception(
                "Debe configurar el endpoint de Cierre capacitación e-learning "
            );
        }

        // Procesar en lotes (chunks) del tamaño de paralelismo (ej. 20)
        foreach (array_chunk($payloads, $chunksize) as $chunk) {
            $mh = curl_multi_init();
            $handles = [];
            $handle_map = [];

            // Iniciar el cronómetro para el lote completo.
            $batch_start_time = microtime(true);

            foreach ($chunk as $payload) {
                // Quitar datos meta de Moodle antes de enviar
                $moodle_data = $payload['_moodle'];
                unset($payload['_moodle']);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: ' . $this->config->tokenapi,
                    'Ocp-Apim-Subscription-Key: ' . $this->config->subscriptionkey,
                    'Content-Type:application/json'
                ]);

                // Añadir datos meta de nuevo para el procesamiento de respuesta
                $payload['_moodle'] = $moodle_data;

                curl_multi_add_handle($mh, $ch);
                $handles[] = $ch;
                $handle_map[(int)$ch] = $payload; // Mapear el recurso del handle al payload.
            }

            // Ejecutar el lote paralelo.
            $active = null;
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);

            while ($active && $mrc == CURLM_OK) {
                if (curl_multi_select($mh) == -1) {
                    usleep(100);
                }
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }

            // Detener el cronómetro y calcular el tiempo total del lote.
            $batch_end_time = microtime(true);
            $total_batch_time = $batch_end_time - $batch_start_time;
            $individual_times = [];

            // Procesar respuestas
            foreach ($handles as $ch) {
                $response = curl_multi_getcontent($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $request_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME); // Tiempo para esta petición.
                $individual_times[] = $request_time;
                $payload = $handle_map[(int)$ch]; // Recuperar el payload por el map

                // Usar la misma lógica de respuesta.
                $this->procesar_respuesta_cierre($payload, $response, $httpcode);

                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
            curl_multi_close($mh);

            // Calcular promedio y registrar en logs.
            $num_requests = count($individual_times);
            if ($num_requests > 0) {
                $average_time = array_sum($individual_times) / $num_requests;
                mtrace("Lote de $num_requests peticiones paralelas completado.");
                mtrace(" - Tiempo total del lote: " . number_format($total_batch_time, 4) . " segundos.");
                mtrace(" - Tiempo promedio por petición (CURLINFO_TOTAL_TIME): " . number_format($average_time, 4) . " segundos.");
            }
        }
    }


    /**
     * Procesa la respuesta de cierre de capacitación e-learning.
     *
     * Procesa la respuesta de la petición al webservice de cierre de capacitación e-learning.
     *  - Si la respuesta es un error (HTTP code mayor que 299), se lanza un evento de tipo
     * \local_pubsub\event\cierre_capacitacion_elearning y se hace rollback de la fecha de envío.
     *  - Si la respuesta es un éxito, se inserta un registro en la tabla cierre_elearning_back_log
     * con los datos de la respuesta y se actualiza la fecha de envío con set_field.
     *
     * @param array $payload Arreglo de datos que se enviaron al webservice
     * @param string $response Respuesta del webservice
     * @param int $httpcode Código HTTP de la respuesta
     * @return bool True si la respuesta es un éxito, false si no lo es
     */
    private function procesar_respuesta_cierre($payload, $response, $httpcode) {
        global $DB;

        $mdata = $payload['_moodle'];
        $inscrito_elearning = $payload['IdRegistroParticipante'];

        if ($httpcode > 299) {
            $this->errorcount++;
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

            // Usamos el ID que ya tenemos, no necesitamos consultar la BD.
            self::rolback_timereported($mdata['inscripcion_id']);
            return false;

        } else {
            // Éxito.
            $datos_log = [
                'id_user_moodle'    => $mdata['userid_moodle'],
                'id_curso_moodle'   => $mdata['cursoid_moodle'],
                "id_registro_participante" => $inscrito_elearning,
                "nota_evaluacion"   => $payload['NotaEvaluacion'],
                "nota_evaluacion_porcentaje" => $payload['NotaEvaluacionPorcentaje'],
                "asistencia"        => $payload['Asistencia'],
                "resultado"         => $payload['Resultado'],
                "fecha_temrino_resultado" => $payload['FechaTemrinoResultado'],
                "observacion"       => $payload['Observacion'],
                "createdat"         => time()
            ];
            $DB->insert_record('cierre_elearning_back_log', $datos_log);

            // Usamos set_field con el ID que ya tenemos.
            $DB->set_field('inscripcion_elearning_back', 'timereported', time(), [
                'id' => $mdata['inscripcion_id']
            ]);

            $event = \local_pubsub\event\cierre_capacitacion_elearning::create(
                array(
                    'context' => \context_system::instance(),
                    'other' => array(
                        'response' => 'Cierre de capacitación con nota: ' . $payload['NotaEvaluacion'] . ' Resultado: ' . $payload['Resultado'] . 'Obervación: ' . $payload['Observacion'],
                        'IdRegistroParticipante' => $inscrito_elearning
                    ),
                )
            );
            $event->trigger();
            return true;
        }
    }

}
