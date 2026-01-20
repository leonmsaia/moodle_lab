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

/**
 *
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_sessionmigrate;

use core\exception\moodle_exception;
use Exception;
use format_eabctiles\utils\eabctiles_utils;
use local_pubsub\utils;
use moodle_database;

defined('MOODLE_INTERNAL') || die();

class sessions
{
    public static function session_is_closed(string $guid) : bool
    {
        $conn = new conn35();
        $db35 = $conn->db;

        $sql = "SELECT * FROM {eabcattendance_sessions} AS eas ";
        $sql.= "LEFT JOIN {format_eabctiles_closegroup} AS cg ON cg.groupid = eas.groupid ";
        $sql.= "WHERE eas.guid = :guid ";
        $sql.= "AND (cg.id IS NULL OR cg.status = 0)";

        return $db35->record_exists_sql($sql, ['guid' => $guid]);

    }

    /**
     * Migra una sesión específica de Moodle 3.5 a 4.5 por su GUID.
     *
     * @param string $sessionguid El GUID de la sesión a migrar.
     * @param moodle_database $db35 Conexión a la BD Moodle 3.5.
     * @param int $logid ID del registro de log a actualizar.
     * @param int $userid Usuario que ejecuta la acción.
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public static function migrate_session_by_guid(string $sessionguid, $db35, int $logid, int $userid): array {
        global $DB;

        $details = [];
        $starttime = time();

        try {
            // Obtener datos de sesión desde la base de datos de Moodle 3.5
            $sql = "SELECT DISTINCT
                    cb.productoid,
                    eas.guid AS idsesion,
                    sb.idevento
                FROM {course} AS c
                JOIN {curso_back} AS cb
                    ON cb.id_curso_moodle = c.id
                JOIN {eabcattendance} AS ea
                    ON ea.course = c.id
                JOIN {eabcattendance_sessions} AS eas
                    ON eas.eabcattendanceid = ea.id
                JOIN {sesion_back} AS sb
                    ON sb.idsesion = eas.guid
                WHERE eas.guid = :sessionguid";

            $sessiondata = $db35->get_record_sql($sql, ['sessionguid' => $sessionguid]);

            if (!$sessiondata) {
                $msg = "No se encontraron datos de sesión en 3.5 para el GUID: {$sessionguid}";
                return ['success' => false, 'message' => $msg, 'details' => [$msg]];
            }

            $details[] = "Datos de sesión encontrados para GUID {$sessionguid}:";
            $details[] = "  Producto ID: {$sessiondata->productoid}";
            $details[] = "  ID Sesión: {$sessiondata->idsesion}";
            $details[] = "  ID Evento: {$sessiondata->idevento}";

            // Lógica de integración (migración real)
            try {
                $response = self::update_sesion_migrate_logic(
                    $sessiondata->idsesion,
                    $sessiondata->productoid,
                    $sessiondata->idevento,
                    'Actualizacion'
                );

                $details[] = "Integración ejecutada correctamente.";
                $details[] = "Respuesta: " . (is_string($response) ? $response : json_encode($response));

            } catch (\dml_exception $e) {
                // Error exacto de la base de datos (lo que necesitas!!)
                $details[] = "Error durante integración DML (Base de datos): " . $e->getMessage();
                $details[] = "Debug: " . $e->debuginfo;
                return ['success' => false, 'message' => 'Error pubsub: '.$e->getMessage(), 'details' => $details];

            } catch (\moodle_exception $e) {
                // Captura errores Moodle más descriptivamente
                $details[] = "Error Moodle durante integración: " . $e->getMessage();
                $details[] = "Debug: " . $e->debuginfo;
                return ['success' => false, 'message' => 'Error pubsub: '.$e->getMessage(), 'details' => $details];

            } catch (\Throwable $e) {
                // Cualquier otro error inesperado
                $details[] = "Error Moodle durante integración: " . $e->getMessage();
                return ['success' => false, 'message' => 'Error pubsub: '.$e->getMessage(), 'details' => $details];
            }

            // Si la sesión está cerrada en 3.5, cerrar el grupo correspondiente en 4.5.
            $closeddata = $db35->get_record_sql("SELECT g.name AS groupname
                        FROM {eabcattendance_sessions} eas
                        JOIN {eabcattendance} ea ON ea.id = eas.eabcattendanceid
                        JOIN {groups} g ON g.id = eas.groupid
                        JOIN {format_eabctiles_closegroup} cg ON cg.groupid = eas.groupid AND cg.status = 1
                        WHERE eas.guid = :guid", ['guid' => $sessionguid]);

            if ($closeddata && !empty($closeddata->groupname)) {
                // Buscar curso 4.5 por productoid.
                $course45 = $DB->get_record_sql("SELECT c.*
                            FROM {curso_back} cb
                            JOIN {course} c ON c.shortname = cb.codigocurso AND c.id = cb.id_curso_moodle
                            WHERE cb.productoid = :productoid", ['productoid' => $sessiondata->productoid]);

                if ($course45) {
                    // Buscar grupo por nombre en 4.5 y cerrar si corresponde.
                    $group45 = $DB->get_record('groups', ['courseid' => $course45->id, 'name' => $closeddata->groupname]);
                    if(!$group45) {
                        $details[] = "No se encontró grupo '{$closeddata->groupname}' en 4.5 para el curso id={$course45->id}; intentando fallback por GUID.";
                        // Intentar fallback por GUID asociado a la sesión en 4.5.
                        $session45 = $DB->get_record('eabcattendance_sessions', ['guid' => $sessionguid], 'id,groupid');
                        if ($session45 && $session45->groupid) {
                            $group45 = $DB->get_record('groups', ['id' => $session45->groupid]);
                            // Sobreescribimos el nombre del grupo en 4.5 por el de 3.5 para consistencia.
                            if ($group45 && $group45->name !== $closeddata->groupname) {
                                $oldname = $group45->name;
                                $group45->name = $closeddata->groupname;
                                $DB->update_record('groups', $group45);
                                $details[] = "Actualizado nombre de grupo en 4.5 de '{$oldname}' a '{$closeddata->groupname}' (id={$group45->id}).";
                            }
                        }else{
                            $details[] = "No se pudo encontrar grupo por GUID {$sessionguid} en 4.5.";
                        }
                    }

                    if ($group45) {
                        $existing = $DB->get_record('format_eabctiles_closegroup', ['groupid' => $group45->id]);

                        $record = (object) [
                            'groupid' => $group45->id,
                            'status' => 1,
                            'timemodified' => time(),
                        ];

                        if ($existing) {
                            $record->id = $existing->id;
                            $DB->update_record('format_eabctiles_closegroup', $record);
                            $details[] = "Actualizado cierre de grupo '{$closeddata->groupname}' (id={$group45->id}) en 4.5.";
                        } else {
                            $record->timecreated = time();
                            $insertid = $DB->insert_record('format_eabctiles_closegroup', $record);
                            $details[] = "Cerrado grupo '{$closeddata->groupname}' en 4.5 (nuevo registro id={$insertid}).";
                        }
                    }else {
                        $details[] = "Grupo '{$closeddata->groupname}' no existe en 4.5 y no hay fallback por GUID; se omite cierre.";
                    }
                } else {
                    $details[] = "Curso 4.5 no encontrado para productoid={$sessiondata->productoid}; se omite cierre de grupo.";
                }
            } else {
                $details[] = "La sesión no está marcada como cerrada en 3.5; no se aplica cierre en 4.5.";
            }

            $msg = "Migración de sesión completada correctamente para GUID: {$sessionguid}.";
            $details[] = $msg;
            $details[] = "Duración total: " . (time() - $starttime) . "s";

            return ['success' => true, 'message' => $msg, 'details' => $details];

        } catch (Exception $e) {
            $msg = "Excepción durante la migración de sesión para GUID {$sessionguid}: " . $e->getMessage();
            $details[] = $msg;
            return ['success' => false, 'message' => $msg, 'details' => $details];
        }
    }

    public static function migrate_sessions_by_date_range(int $startdate, int $enddate, $db35, int $logid, int $userid): array {
        global $DB;
        $details = [];
        $starttime = time();

        try {
            // Determinar si es un único GUID o múltiples
            $sql = "SELECT DISTINCT
                    cb.productoid,
                    eas.guid AS idsesion,
                    sb.idevento
                FROM {course} AS c
                JOIN {curso_back} AS cb
                    ON cb.id_curso_moodle = c.id
                JOIN {eabcattendance} AS ea
                    ON ea.course = c.id
                JOIN {eabcattendance_sessions} AS eas
                    ON eas.eabcattendanceid = ea.id
                JOIN {sesion_back} AS sb
                    ON sb.idsesion = eas.guid
                WHERE eas.sessdate >= :startdate
                  AND eas.sessdate < :enddate AND eas.guid IS NOT NULL";

            $params = [
                'startdate' => $startdate,
                'enddate' => $enddate
            ];
            $sessiondataarray = $db35->get_records_sql($sql, $params);

            if (empty($sessiondataarray)) {
                $msg = "No se encontraron datos de sesión en 3.5 para el rango de fechas: " . userdate($startdate) . " - " . userdate($enddate);
                return ['success' => false, 'message' => $msg, 'details' => [$msg]];
            }

            $all_success = true;
            $all_messages = [];

            // Sesiones encontradas en 3.5
            $details[] = "Iniciando el proceso rango: " . $startdate . " - " . $enddate;
            $details[] = "Se encontraron " . count($sessiondataarray) . " sesiones para migrar.";

            // Procesar cada sesión encontrada
            foreach ($sessiondataarray as $sessiondata) {
                $singleguid = $sessiondata->idsesion;
                $details[] = "--- Procesando GUID: {$singleguid} ---";

                $details[] = "Datos de sesión encontrados para GUID {$singleguid}:";
                $details[] = "  Producto ID: {$sessiondata->productoid}";
                $details[] = "  ID Sesión: {$sessiondata->idsesion}";
                $details[] = "  ID Evento: {$sessiondata->idevento}";

                // Lógica de integración (migración real)
                try {
                    $response = self::update_sesion_migrate_logic(
                        $sessiondata->idsesion,
                        $sessiondata->productoid,
                        $sessiondata->idevento,
                        'Actualizacion'
                    );

                    $details[] = "Integración ejecutada correctamente.";
                    $details[] = "Respuesta: " . (is_string($response) ? $response : json_encode($response));

                } catch (\dml_exception $e) {
                    // Error exacto de la base de datos (lo que necesitas!!)
                    $details[] = "Error durante integración DML (Base de datos): " . $e->getMessage();
                    $details[] = "Debug: " . $e->debuginfo;
                    $all_success = false;
                    $all_messages[] = 'Error pubsub: '.$e->getMessage();
                    continue;

                } catch (\moodle_exception $e) {
                    // Captura errores Moodle más descriptivamente
                    $details[] = "Error Moodle durante integración: " . $e->getMessage();
                    $details[] = "Debug: " . $e->debuginfo;
                    $all_success = false;
                    $all_messages[] = 'Error pubsub: '.$e->getMessage();
                    continue;

                } catch (\Throwable $e) {
                    // Cualquier otro error inesperado
                    $details[] = "Error Moodle durante integración: " . $e->getMessage();
                    $all_success = false;
                    $all_messages[] = 'Error pubsub: '.$e->getMessage();
                    continue;
                }

                // Si la sesión está cerrada en 3.5, cerrar el grupo correspondiente en 4.5.
                $closeddata = $db35->get_record_sql("SELECT g.name AS groupname
                        FROM {eabcattendance_sessions} eas
                        JOIN {eabcattendance} ea ON ea.id = eas.eabcattendanceid
                        JOIN {groups} g ON g.id = eas.groupid
                        JOIN {format_eabctiles_closegroup} cg ON cg.groupid = eas.groupid AND cg.status = 1
                        WHERE eas.guid = :guid", ['guid' => $singleguid]);

                if ($closeddata && !empty($closeddata->groupname)) {
                    // Buscar curso 4.5 por productoid.
                    $course45 = $DB->get_record_sql("SELECT c.*
                            FROM {curso_back} cb
                            JOIN {course} c ON c.shortname = cb.codigocurso AND c.id = cb.id_curso_moodle
                            WHERE cb.productoid = :productoid", ['productoid' => $sessiondata->productoid]);

                    if ($course45) {
                        // Buscar grupo por nombre en 4.5 y cerrar si corresponde.
                        $group45 = $DB->get_record('groups', ['courseid' => $course45->id, 'name' => $closeddata->groupname]);
                        if(!$group45) {
                            $details[] = "No se encontró grupo '{$closeddata->groupname}' en 4.5 para el curso id={$course45->id}; intentando fallback por GUID.";
                            // Intentar fallback por GUID asociado a la sesión en 4.5.
                            $session45 = $DB->get_record('eabcattendance_sessions', ['guid' => $singleguid], 'id,groupid');
                            if ($session45 && $session45->groupid) {
                                $group45 = $DB->get_record('groups', ['id' => $session45->groupid]);
                                // Sobreescribimos el nombre del grupo en 4.5 por el de 3.5 para consistencia.
                                if ($group45 && $group45->name !== $closeddata->groupname) {
                                    $oldname = $group45->name;
                                    $group45->name = $closeddata->groupname;
                                    $DB->update_record('groups', $group45);
                                    $details[] = "Actualizado nombre de grupo en 4.5 de '{$oldname}' a '{$closeddata->groupname}' (id={$group45->id}).";
                                }
                            }else{
                                $details[] = "No se pudo encontrar grupo por GUID {$singleguid} en 4.5.";
                            }
                        }

                        if ($group45) {
                            $existing = $DB->get_record('format_eabctiles_closegroup', ['groupid' => $group45->id]);

                            $record = (object) [
                                'groupid' => $group45->id,
                                'status' => 1,
                                'timemodified' => time(),
                            ];

                            if ($existing) {
                                $record->id = $existing->id;
                                $DB->update_record('format_eabctiles_closegroup', $record);
                                $details[] = "Actualizado cierre de grupo '{$closeddata->groupname}' (id={$group45->id}) en 4.5.";
                            } else {
                                $record->timecreated = time();
                                $insertid = $DB->insert_record('format_eabctiles_closegroup', $record);
                                $details[] = "Cerrado grupo '{$closeddata->groupname}' en 4.5 (nuevo registro id={$insertid}).";
                            }
                        }else {
                            $details[] = "Grupo '{$closeddata->groupname}' no existe en 4.5 y no hay fallback por GUID; se omite cierre.";
                        }
                    } else {
                        $details[] = "Curso 4.5 no encontrado para productoid={$sessiondata->productoid}; se omite cierre de grupo.";
                    }
                } else {
                    $details[] = "La sesión no está marcada como cerrada en 3.5; no se aplica cierre en 4.5.";
                }

                $msg = "Migración de sesión completada correctamente para GUID: {$singleguid}.";
                $details[] = $msg;
                $all_messages[] = $msg;
            }

            $details[] = "Duración total: " . (time() - $starttime) . "s";

            if ($all_success) {
                $final_msg = "Migración de sesiones completada correctamente para el rango de fechas: " . userdate($startdate) . " - " . userdate($enddate) . ".";
                return ['success' => true, 'message' => $final_msg, 'details' => $details];
            } else {
                return ['success' => false, 'message' => implode("; ", $all_messages), 'details' => $details];
            }

        } catch (Exception $e) {
            $msg = "Ocurrió un error durante la ejecución del rango de fechas: " . userdate($startdate) . " - " . userdate($enddate).": " . $e->getMessage();
            $details[] = $msg;
            return ['success' => false, 'message' => "Ocurrió un error. Lote :", 'details' => $details];
        }
    }


    /**
     * Sincroniza grupos "cerrados" desde la BD Moodle 3.5 hacia la BD actual (4.5).
     *
     * @param string $productoid Identificador del producto (productoid).
     * @param moodle_database $db35 Conexión a la BD Moodle 3.5 (tu clase conn35->db).
     * @param int $logid Id del registro de log a actualizar.
     * @param int $userid Usuario que ejecuta la acción.
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    /**
     * Sincroniza grupos cerrados (por nombre) desde Moodle 3.5 hacia 4.5.
     *
     * @param string $productoid Identificador del producto.
     * @param moodle_database $db35 Conexión a la BD Moodle 3.5.
     * @param int $logid ID del registro de log.
     * @param int $userid ID del usuario que ejecuta la acción.
     * @return array ['success'=>bool, 'message'=>string, 'details'=>array]
     */
    public static function sync_closed_groups(string $productoid, $db35, int $logid, int $userid): array {
        global $DB;

        $details = [];
        $starttime = time();
        $CLOSED_STATUS = 1; // ← Cambia si el valor "cerrado" es distinto.

        try {
            // 1. Buscar curso en 4.5
            $course = $DB->get_record_sql("
                SELECT c.*
                FROM {curso_back} cb
                JOIN {course} c ON c.shortname = cb.codigocurso AND c.id = cb.id_curso_moodle
                WHERE cb.productoid = :productoid
            ", ['productoid' => $productoid]);

            if (!$course) {
                $msg = "No se encontró curso en 4.5 para productoid={$productoid}";
                return ['success' => false, 'message' => $msg, 'details' => [$msg]];
            }

            $details[] = "Curso 4.5 encontrado: id={$course->id}, shortname={$course->shortname}";

            // 2. Buscar curso en 3.5
            $course35 = $db35->get_record_sql("
                SELECT c.*
                FROM {curso_back} cb
                JOIN {course} c ON c.shortname = cb.codigocurso AND c.id = cb.id_curso_moodle
                WHERE cb.productoid = :productoid
            ", ['productoid' => $productoid]);

            if (!$course35) {
                $msg = "No se encontró curso en 3.5 para productoid={$productoid}";
                return ['success' => false, 'message' => $msg, 'details' => [$msg]];
            }

            $details[] = "Curso 3.5 encontrado: id={$course35->id}, shortname={$course35->shortname}";

            // 3. Obtener grupos cerrados en 3.5, incluyendo un GUID de sesión para fallback.
            $sql = "
                SELECT g.name, MAX(eas.guid) as sessionguid
                FROM {eabcattendance_sessions} eas
                JOIN {eabcattendance} ea ON ea.id = eas.eabcattendanceid
                JOIN {format_eabctiles_closegroup} cg ON cg.groupid = eas.groupid
                JOIN {groups} g ON g.id = eas.groupid
                WHERE cg.status = :status AND ea.course = :courseid
                GROUP BY g.name
            ";
            $params = ['status' => $CLOSED_STATUS, 'courseid' => $course35->id];
            $closedgroups = $db35->get_records_sql($sql, $params);

            if (empty($closedgroups)) {
                $msg = "No se encontraron grupos cerrados en 3.5 para el curso.";
                return ['success' => true, 'message' => $msg, 'details' => $details];
            }

            $details[] = "Grupos cerrados en 3.5: " . count($closedgroups);

            $created = $updated = $skipped = 0;

            foreach ($closedgroups as $cg) {
                $groupname = trim($cg->name);
                if (empty($groupname)) {
                    $details[] = "Grupo sin nombre en 3.5 (omitido).";
                    $skipped++;
                    continue;
                }

                // Buscar grupo por nombre en 4.5
                $group45 = $DB->get_record('groups', [
                    'courseid' => $course->id,
                    'name' => $groupname
                ]);

                if (!$group45) {
                    $details[] = "No se encontró grupo '{$groupname}' en 4.5 por nombre; intentando fallback por GUID.";
                    $sessionguid = $cg->sessionguid ?? null;
                    if ($sessionguid) {
                        // Intentar fallback por GUID asociado a la sesión en 4.5.
                        $sessions45 = $DB->get_records('eabcattendance_sessions', ['guid' => $sessionguid], 'id,groupid');
                        if(count($sessions45) > 1){
                            $details[] = "Múltiples sesiones encontradas en 4.5 para GUID {$sessionguid}; usando la más reciente.";
                            // Imprimir todas las sesiones encontradas
                            foreach ($sessions45 as $sess) {
                                $details[] = "  Sesión ID: {$sess->id}, Group ID: {$sess->groupid}";
                            }
                            // Ordenar por ID descendente y tomar la primera
                            usort($sessions45, function($a, $b) {
                                return $b->id - $a->id;
                            });

                            $session45 = $sessions45[0];
                        }else{
                            $session45 = reset($sessions45);
                        }
                        if ($session45 && $session45->groupid) {
                            $group45 = $DB->get_record('groups', ['id' => $session45->groupid]);
                            if ($group45) {
                                $details[] = "Grupo '{$groupname}' encontrado vía fallback por GUID {$sessionguid}.";
                            }
                            // Sobreescribimos el nombre del grupo en 4.5 por el de 3.5 para consistencia.
                            if ($group45 && $group45->name !== $groupname) {
                                $oldname = $group45->name;
                                $group45->name = $groupname;
                                $DB->update_record('groups', $group45);
                                $details[] = "Actualizado nombre de grupo en 4.5 de '{$oldname}' a '{$groupname}' (id={$group45->id}).";
                            }
                        }
                    }
                }

                if (!$group45) {
                    $details[] = "No se pudo encontrar el grupo '{$groupname}' en 4.5 (omitido).";
                    $skipped++;
                    continue;
                }

                // Crear o actualizar registro de cierre
                $existing = $DB->get_record('format_eabctiles_closegroup', ['groupid' => $group45->id]);


                $record = (object)[
                    'groupid' => $group45->id,
                    'status' => $CLOSED_STATUS,
                    'timemodified' => time(),
                ];

                if ($existing) {
                    $record->id = $existing->id;
                    $DB->update_record('format_eabctiles_closegroup', $record);
                    $updated++;
                    $details[] = "Actualizado cierre de grupo '{$groupname}' (id={$group45->id}).";
                } else {
                    $record->timecreated = time();
                    $insertid = $DB->insert_record('format_eabctiles_closegroup', $record);
                    $created++;
                    $details[] = "Cerrado grupo '{$groupname}' en 4.5 (nuevo registro id={$insertid}).";
                }

                /**
                 * Validar si se requiere disparar algún evento o acción adicional al cerrar el grupo.
                 * $event = \format_eabctiles\event\eabctiles_close_group::create(
                 * array(
                 * 'context' => \context_course::instance($course->id),
                 * 'other' => array(
                 * 'shortname' => $course->shortname,
                 * 'fullname' => $course->fullname,
                 * 'groupid' => $groupid,
                 * 'status' => $status,
                 * ),
                 * 'courseid' => $course->id,
                 * )
                 * );
                 * $event->trigger();
                 */
            }

            $msg = "Sincronización finalizada. Creados={$created}, Actualizados={$updated}, Omitidos={$skipped}.";
            $details[] = $msg;
            $details[] = "Duración: " . (time() - $starttime) . "s";

            return ['success' => true, 'message' => $msg, 'details' => $details];

        } catch (Exception $e) {
            $msg = "Excepción: " . $e->getMessage();
            $details[] = $msg;
            return ['success' => false, 'message' => $msg, 'details' => $details];
        }
    }

    /**
     * Sincroniza grupos cerrados para el curso asociado a una sesión específica.
     *
     * @param string $sessionguid El GUID de la sesión.
     * @param moodle_database $db35 Conexión a la BD Moodle 3.5.
     * @param int $logid ID del registro de log a actualizar.
     * @param int $userid Usuario que ejecuta la acción.
     * @return array ['success' => bool, 'message' => string, 'details' => array]
     */
    public static function sync_closed_groups_by_sessionguid(string $sessionguid, $db35, int $logid, int $userid): array {
        $details = [];

        // 1. Buscar el productoid a partir del sessionguid en la BD 3.5
        $sql = "SELECT cb.productoid
                FROM {eabcattendance_sessions} eas
                JOIN {eabcattendance} ea ON ea.id = eas.eabcattendanceid
                JOIN {course} c ON c.id = ea.course
                JOIN {curso_back} cb ON cb.id_curso_moodle = c.id
                WHERE eas.guid = :sessionguid";
        
        $productoid = $db35->get_field_sql($sql, ['sessionguid' => $sessionguid]);

        if (!$productoid) {
            $msg = "No se pudo encontrar un 'productoid' para el GUID de sesión: {$sessionguid}";
            $details[] = $msg;
            return ['success' => false, 'message' => $msg, 'details' => $details];
        }

        $details[] = "Productoid encontrado para la sesión {$sessionguid}: {$productoid}.";
        
        // 2. Llamar a la función existente con el productoid encontrado.
        $result = self::sync_closed_groups($productoid, $db35, $logid, $userid);

        // Prepend our details to the result from the called function.
        $result['details'] = array_merge($details, $result['details']);

        return $result;
    }

    /**
     * Builds the SQL query to find duplicate sessions.
     *
     * @param string $searchtype The field to search by (grupoid, idevento, sessionguid).
     * @param string $searchvalue The value to search for.
     * @return array An array containing the SQL query and parameters.
     */
    public static function get_duplicate_sessions_sql(string $searchtype, string $searchvalue): array {
        $searchcolumn = '';
        if ($searchtype === 'grupoid') {
            $searchcolumn = 'eas.groupid';
        } else if ($searchtype === 'idevento') {
            $searchcolumn = 'sb.idevento';
        } else if ($searchtype === 'sessionguid') {
            $searchcolumn = 'eas.guid';
        }

        $sql = "SELECT
                    eas.id as easid,
                    eas.guid AS idsesion,
                    eas.groupid,
                    sb.id AS sbid,
                    sb.idevento,
                    c.shortname,
                    g.name as groupname
                FROM {eabcattendance_sessions} eas
                JOIN {sesion_back} sb ON sb.idsesion = eas.guid
                JOIN {eabcattendance} ea ON ea.id = eas.eabcattendanceid
                JOIN {course} c ON c.id = ea.course
                JOIN {groups} g ON g.id = eas.groupid
                WHERE {$searchcolumn} = :searchvalue
                ORDER BY eas.id DESC";

        $params = ['searchvalue' => $searchvalue];

        return [$sql, $params];
    }

    /**
     * Deletes a session by its GUID from the 3.5 database.
     *
     * @param string $sessionguid The GUID of the session to delete.
     * @param moodle_database $db35 Connection to the Moodle 3.5 database.
     * @param int $logid The ID of the log entry to update.
     * @param int $userid The ID of the user who triggered the action.
     * @return array An array with the result of the operation.
     */
    public static function delete_session_by_guid(string $sessionguid, $db35, int $logid, int $userid): array {
        $details = [];
        $starttime = time();

        try {
            $transaction = $db35->start_delegated_transaction();

            // Find the session to get its ID
            $session = $db35->get_record('eabcattendance_sessions', ['guid' => $sessionguid]);
            if (!$session) {
                $msg = "Session with GUID {$sessionguid} not found in 'eabcattendance_sessions'.";
                $details[] = $msg;
                return ['success' => false, 'message' => $msg, 'details' => $details];
            }
            $details[] = "Found session in 'eabcattendance_sessions' with id: {$session->id}";

            // Delete from sesion_back
            if ($db35->record_exists('sesion_back', ['idsesion' => $sessionguid])) {
                $db35->delete_records('sesion_back', ['idsesion' => $sessionguid]);
                $details[] = "Deleted record from 'sesion_back' where idsesion = {$sessionguid}.";
            } else {
                $details[] = "No record found in 'sesion_back' for idsesion = {$sessionguid}. Skipping.";
            }

            // Delete from eabcattendance_sessions
            $db35->delete_records('eabcattendance_sessions', ['id' => $session->id]);
            $details[] = "Deleted record from 'eabcattendance_sessions' where id = {$session->id}.";

            $transaction->allow_commit();

            $msg = "Successfully deleted session with GUID: {$sessionguid}.";
            $details[] = $msg;
            $details[] = "Total duration: " . (time() - $starttime) . "s";

            return ['success' => true, 'message' => $msg, 'details' => $details];

        } catch (Exception $e) {
            isset($transaction) && $transaction->rollback($e);
            $msg = "Exception while deleting session with GUID {$sessionguid}: " . $e->getMessage();
            $details[] = $msg;
            return ['success' => false, 'message' => $msg, 'details' => $details];
        }
    }

    /**
     * @param $id
     * @param $idcurso
     * @param $idevento
     * @param $action
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     */
    public static function update_sesion_migrate_logic($id, $idcurso, $idevento, $action) {
        global $CFG;
        require_once($CFG->libdir . '/externallib.php');


        global $DB;
        // 🔹 Cargar configuraciones una sola vez (reduce consultas)
        $config = (object)[
            'approvedstatus'   => get_config('local_pubsub', 'approvedstatus'),
            'suspendedstatus'  => get_config('local_pubsub', 'suspendedstatus'),
            'endpointsession'  => get_config('local_pubsub', 'endpointsession'),
            'rolwscreateactivity' => get_config('eabcattendance', 'rolwscreateactivity') ?: 3
        ];

        // 🔹 Consultar endpoint remoto
        $endpoint = $config->endpointsession . $id;
        $response = \local_pubsub\metodos_comunes::request($endpoint);
        \local_pubsub\metodos_comunes::save_event_response_endpointsession(
            \context_system::instance(),
            ['response' => json_encode($response)]
        );

        if ($response['status'] > 299) {
            throw new \moodle_exception("error request: {$response['status']} Endpoint: $endpoint");
        }

        $response = json_decode($response['data'], true);


        // 🔹 Tiempos
        $timestart = self::fix_invierno_2025(utils::date_to_timestamp($response['InicioCapacitacion']));
        $timeend   = self::fix_invierno_2025(utils::date_to_timestamp($response['TerminoCapacitacion']));
        $timesecond = $timeend - $timestart;

        // 🔹 Buscar curso en Moodle (ajustado igual que el WS: se añade la comprobación de shortname)
        $course = $DB->get_record_sql("
            SELECT c.*
            FROM {curso_back} cb
            JOIN {course} c ON c.shortname = cb.codigocurso and c.id = cb.id_curso_moodle
            WHERE cb.productoid = :productoid
        ", ['productoid' => $idcurso]);

        if (!$course) {
            throw new \moodle_exception("Curso no encontrado en Moodle para el ID externo: " . $idcurso);
        }

        $coursecontext = \context_course::instance($course->id);

        // 🔹 Nombre de grupo
        $guidevento = explode("-", $idevento);
        $name_group = date("d-m-Y H:i", $timestart) . " " . $guidevento[0];

        // 🔹 Buscar grupo existente por nombre
        $groupid = $DB->get_field('groups', 'id', [
            'courseid' => $course->id,
            'name' => $name_group
        ]);

        // 🔹 Intentar recuperar grupo desde la tabla pivote si no existe
        if (!$groupid) {
            $group_from_pivot = $DB->get_record('eabcattendance_course_groups', [
                'uuid' => $idevento,
                'curso' => $course->id
            ]);

            if (empty($group_from_pivot)) {
                // Crear grupo si no existe en pivote ni en grupos
                $groupid = \local_pubsub\metodos_comunes::create_group(['createname' => $name_group], $course);
                if (empty($groupid)) {
                    throw new \moodle_exception("Error al crear grupo");
                }
            } else {
                $groupid = $group_from_pivot->grupo;
                // Validar que el grupo aún exista
                if (!$DB->record_exists('groups', ['id' => $groupid])) {
                    $groupid = \local_pubsub\metodos_comunes::create_group(['createname' => $name_group], $course);
                    if (empty($groupid)) {
                        throw new \moodle_exception("Error al crear grupo");
                    }
                }
            }
        }

        // 🔹 Actualizar nombre si ha cambiado
        if ($groupid) {
            $get_grupo = $DB->get_record('groups', ['id' => $groupid]);
            if ($get_grupo && $get_grupo->name !== $name_group) {
                $record = (object)[
                    'id' => $get_grupo->id,
                    'courseid' => $course->id,
                    'name' => $name_group
                ];
                groups_update_group($record);
            }
        }

        // 🔹 Asegurar existencia en la tabla pivote (cambiado para igualar comportamiento del WS)
        if ($groupid) {
            \local_pubsub\metodos_comunes::eabcattendance_course_groups($groupid, $course->id, $idevento);
        } else {
            throw new \moodle_exception("Error al obtener el ID de grupo en el curso: " . $course->id);
        }

        // 🔹 Buscar la relación grupo-curso-uuid
        $groups = $DB->get_record('eabcattendance_course_groups', [
            'curso' => $course->id,
            'uuid' => $idevento
        ]);

        if (empty($groups) || !$DB->record_exists('groups', ['id' => $groups->grupo])) {
            throw new \moodle_exception("No se pudo encontrar o crear una referencia de grupo válida para el evento UUID: " . $idevento);
        }

        $groupid = $groups->grupo;

        // 🔹 Crear relator (mantener del original)
        if (!empty($response['IdRelator'])) {
            $guion = substr($response['IdRelator'], -2, 1);
            if ($guion !== "-") {
                $rut = substr($response['IdRelator'], 0, -1);
                $dv = substr($response['IdRelator'], -1, 1);
                $response['IdRelator'] = sprintf("%s-%s", $rut, $dv);
            }

            $user = $DB->get_record('user', ['username' => $response['IdRelator']]);
            if ($user) {
                if($user->id == 0){
                    throw new \moodle_exception("Relator con ID de usuario 0");
                }
                \local_pubsub\metodos_comunes::enrol_user($course, $user->id, $groups->grupo, $config->rolwscreateactivity);
                role_assign($config->rolwscreateactivity, $user->id, $coursecontext->id);
            } else {
                $other = [
                    'error' => 'Relator no registrado en Moodle',
                    'guid' => "Guid de sesión: " . $id
                ];
                \local_pubsub\metodos_comunes::save_event_sessions(\context_course::instance($course->id), $other, $course->id);
            }
        }



        // 🔹 Buscar o crear sesión de asistencia
        $get_session = $DB->get_record('eabcattendance_sessions', ['guid' => $id]);

        if ($get_session) {
            $attendance = $DB->get_record('eabcattendance', ['id' => $get_session->eabcattendanceid]);
            $cm = get_coursemodule_from_instance('eabcattendance', $attendance->id, 0, false, MUST_EXIST);
        } else {
            // Buscar actividad de asistencia
            $get_attendances_activities = $DB->get_records('eabcattendance', ['course' => $course->id]);

            if (empty($get_attendances_activities)) {
                $other = [
                    'error' => get_string('coursenotattendance', 'local_pubsub'),
                    'guid' => $id
                ];
                \local_pubsub\metodos_comunes::save_event_sessions(\context_course::instance($course->id), $other, $course->id);
                throw new \moodle_exception("coursenotattendance", "local_pubsub");
            }

            // Tomar el primero disponible
            $attendance = reset($get_attendances_activities);
            $cm = get_coursemodule_from_instance('eabcattendance', $attendance->id, 0, false, MUST_EXIST);
        }

        // 🔹 Crear o actualizar sesión
        $sesionid = \local_pubsub\metodos_comunes::create_or_update_session(
            $attendance,
            $cm,
            $course,
            $timestart,
            $timesecond,
            $groups,
            $id
        );

        $participantes = \local_pubsub\metodos_comunes::get_participantes_sesion($id);

        global $CFG;
        require_once $CFG->libdir.'/enrollib.php';
        $enrolplugin = enrol_get_plugin('manual');


        // ============================================
        // 🔹 OPTIMIZACIÓN: Cargar todos los usuarios de una sola vez
        // ============================================

        // Normalizar identificadores y preparar mapeo
        $usernames = [];
        foreach ($participantes as $p) {
            $rut = \core_user::clean_field(strtolower(trim($p['ParticipanteIdentificador'])), 'username');
            if (!empty($rut)) {
                $usernames[$rut] = $p;
            }
        }

        // Cargar todos los usuarios existentes en una sola consulta
        $existing_users = !empty($usernames)
            ? $DB->get_records_list('user', 'username', array_keys($usernames))
            : [];

        // Cargar todos los usuarios enrolados para evitar múltiples consultas
        $enrolled_users = get_enrolled_users($coursecontext, '', 0, 'u.id, u.username');
        $enrolled_map = array_column($enrolled_users, 'id', 'username');

        $enrolinstances = $DB->get_records('enrol', [
            'courseid'      => $course->id,
            'status'        =>  ENROL_INSTANCE_ENABLED,
            'enrol'         => 'manual'
        ], 'sortorder,id');

        if(empty($enrolinstances)){
            throw new \Exception('No existe la instancia de matriculación manual.');
        }
        $enrolinstances = reset($enrolinstances);



        foreach ($usernames as $rut => $participante) {

            $user = $existing_users[$rut] ?? null;

            if ($user) {
                // Usuario existente
                if (!isset($enrolled_map[$rut])) {
                    if($user->id == 0){
                        throw new \moodle_exception("Usuario con ID 0 - Code 1");
                    }
                    $enrolplugin->enrol_user($enrolinstances, $user->id, 5);
                }
                groups_add_member($groupid, $user->id);
            }else{
                // Creamos el usuario si no existe
                $enrol_passport = ($participante['ParticipanteTipoDocumento'] == 100);

                try {
                    $transaction = $DB->start_delegated_transaction();

                    // Obtener sesión (para registrar el participante correctamente)
                    $get_session = $DB->get_record('eabcattendance_sessions', ['id' => $sesionid]);

                    // Crear usuario y enrolarlo
                    $newuserid = \local_pubsub\metodos_comunes::register_participants($rut, $participante, $course, $get_session, $enrol_passport, false);


                    if(!empty($newuserid)){
                        groups_add_member($groupid, $newuserid);
                    }else{
                        throw new \Exception('Usuario vacío');
                    }

                    $transaction->allow_commit();
                } catch (Exception $e) {
                    $transaction->rollback($e);
                }

            }
        }

        /**
        if ($response["Estado"] == $config->suspendedstatus) {

            self::format_eabctiles_suspendactivity(
                $groupid,
                $course->id,
                $response['MotivoSuspension'],
                "suspendido desde back"
            );

            \local_pubsub\metodos_comunes::save_event_response_suspend_session(
                \context_course::instance($course->id),
                $response
            );
        }**/

        // 🔹 Actualizar tabla sesion_back
        \local_pubsub\back\sesion::inser_update_sesion_back($response, $sesionid);

        return [
            'moodlesesionid' => $sesionid
        ];
    }

    public static function format_eabctiles_suspendactivity($groupid, $courseid, $motivo, $textother)
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->dirroot . '/lib/grouplib.php');


        try {
            $transaction = $DB->start_delegated_transaction(); // Rollback all enrolment if an error occurs
            $group = groups_get_group($groupid, '*', IGNORE_MISSING);

            groups_delete_group($group);
            eabctiles_utils::create_event_suspend_group($groupid, $courseid, $motivo, $textother);
            eabctiles_utils::save_suspend($groupid, $courseid, $motivo, $textother);
            $response = \local_pubsub\metodos_comunes::suspend_sesion($groupid, $motivo);
            //guardar evento de respuesta
            eabctiles_utils::eabctiles_response_suspendsession( \context_course::instance($courseid), $response, $groupid, $motivo);
            if(!($response)){
                if($response > 299){
                    throw new Exception('Hubo un error al comunicarse con el Back, codigo de error: '.$response);
                }
            }

            $transaction->allow_commit();
        }catch (Exception $exception){
            $transaction->rollback($exception);
        }



        return array();
    }

    /**
     * Función que aplica el ajuste de invierno (+3600) para el año 2025.
     *
     * @param int $timestampBase El timestamp que generó 'utils' (asumiendo UTC-3).
     * @return int El timestamp UTC corregido.
     */
    public static function fix_invierno_2025($timestampBase) {

        // Inicio Invierno (6 Abr 2025 00:00 UTC-3)
        $inicioInviernoTS = 1743994800;

        // Inicio Verano (7 Sep 2025 00:00 UTC-3)
        $inicioVeranoTS = 1757290800;

        // Comprobamos si el timestamp cae DENTRO del período de invierno
        if ($timestampBase >= $inicioInviernoTS && $timestampBase < $inicioVeranoTS) {
            return $timestampBase + 3600;
        }

        // Si es una sesión vieja, pero está programada posterior al verano 2025, se resta la hora
        if($timestampBase >= $inicioVeranoTS){
            return $timestampBase - 3600;
        }

        return $timestampBase;
    }

    /**
     * Gets the count of sessions and affected courses within a date range.
     *
     * @param int $startdate Start timestamp.
     * @param int $enddate End timestamp.
     * @param moodle_database $db35 Connection to the Moodle 3.5 database.
     * @return array An array containing session_count and courses shortnames.
     */
    public static function get_sessions_info_by_date_range(int $startdate, int $enddate, $db35): array {
        $sql = "SELECT DISTINCT c.shortname
                FROM {eabcattendance_sessions} eas
                JOIN {eabcattendance} ea ON ea.id = eas.eabcattendanceid
                JOIN {course} c ON c.id = ea.course
                WHERE eas.sessdate >= :startdate AND eas.sessdate < :enddate AND eas.guid IS NOT NULL";

        $params = ['startdate' => $startdate, 'enddate' => $enddate];
        $courses = $db35->get_fieldset_sql($sql, $params);

        $countsql = "SELECT COUNT(DISTINCT guid) FROM {eabcattendance_sessions} WHERE sessdate >= :startdate AND sessdate < :enddate AND guid IS NOT NULL";
        $sessioncount = $db35->count_records_sql($countsql, $params);

        return [
            'session_count' => $sessioncount,
            'courses' => $courses,
        ];
    }

    /**
     * Gets the session GUIDs within a date range.
     *
     * @param int $startdate Start timestamp.
     * @param int $enddate End timestamp.
     * @param moodle_database $db35 Connection to the Moodle 3.5 database.
     * @return array An array of session GUIDs.
     */
    public static function get_session_guids_by_date_range(int $startdate, int $enddate, $db35): array {
        // Para que el conteo de GUIDs en el CLI coincida con las sesiones
        // que realmente se procesan en `migrate_sessions_by_date_range`,
        // usamos la misma lógica/joins: se requiere existencia de
        // `curso_back` y `sesion_back` y mapeo al curso.
        $sql = "SELECT DISTINCT eas.guid
                FROM {course} AS c
                JOIN {curso_back} AS cb ON cb.id_curso_moodle = c.id
                JOIN {eabcattendance} AS ea ON ea.course = c.id
                JOIN {eabcattendance_sessions} AS eas ON eas.eabcattendanceid = ea.id
                JOIN {sesion_back} AS sb ON sb.idsesion = eas.guid
                WHERE eas.sessdate >= :startdate
                  AND eas.sessdate < :enddate
                  AND eas.guid IS NOT NULL";
        $params = ['startdate' => $startdate, 'enddate' => $enddate];
        return $db35->get_fieldset_sql($sql, $params);
    }

    /**
     * Obtiene la información de las sesiones filtrando por shortname del curso y rango de fechas.
     *
     * @param string $shortname Shortname del curso.
     * @param int $startdate Timestamp de inicio.
     * @param int $enddate Timestamp de fin.
     * @param \moodle_database $db35 Conexión a la base de datos externa.
     * @return array Array con 'session_count', 'courses' (array de nombres) y 'sessions' (objetos con guid).
     */
    public static function get_sessions_info_by_course_and_date_range($shortname, $startdate, $enddate, $db35) {
        // Consulta basada en la que solicitaste, añadiendo el filtro por shortname.
        $sql = "SELECT DISTINCT eas.guid
                FROM {course} AS c
                JOIN {curso_back} AS cb ON cb.id_curso_moodle = c.id
                JOIN {eabcattendance} AS ea ON ea.course = c.id
                JOIN {eabcattendance_sessions} AS eas ON eas.eabcattendanceid = ea.id
                JOIN {sesion_back} AS sb ON sb.idsesion = eas.guid
                WHERE c.shortname = :shortname
                  AND eas.sessdate >= :startdate
                  AND eas.sessdate < :enddate
                  AND eas.guid IS NOT NULL";
        $params = ['shortname' => $shortname, 'startdate' => $startdate, 'enddate' => $enddate];

        try {
            $guids = $db35->get_fieldset_sql($sql, $params);
        } catch (\Exception $e) {
            // En caso de error con la conexión/consulta devolvemos vacío.
            $guids = [];
        }

        $sessions = [];
        if (!empty($guids)) {
            foreach ($guids as $g) {
                // Mantener compatibilidad con código que espera objetos de sesión.
                $sessions[] = (object)['guid' => $g];
            }
        }

        return [
            'session_count' => count($sessions),
            'courses' => !empty($sessions) ? [$shortname] : [],
            'sessions' => $sessions
        ];
    }
}