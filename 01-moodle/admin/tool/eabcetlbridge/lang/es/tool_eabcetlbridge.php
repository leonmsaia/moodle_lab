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
 * Plugin strings are defined here.
 *
 * @package     tool_eabcetlbridge
 * @category    lang
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'e-ABC ETL Bridge';
$string['migrationorchestratortask'] = 'e-ABC Orquestador de migraciones';
$string['populate_id_mapping_task'] = 'e-ABC Sincronización de IDs maestros';
$string['populate_id_mapping_batch_task'] = 'e-ABC Sincronización de Lote de IDs maestros';
$string['populate_planner_batch_task'] = 'e-ABC Generación de Lote de Planificación';
$string['get_external_grades_and_create_data_batch_task'] = 'e-ABC Obtener calificaciones externas y crear CSV Data Batch';
$string['update_planners_status_task'] = 'e-ABC Actualización de Estado de Planificadores';
$string['migrate_automatic_start_task'] = 'e-ABC Inicio de Encolamiento de Registros para Estrategias de Migración Automáticas';
$string['mark_processed_users_task'] = 'e-ABC Marcar Usuarios Procesados';
$string['register_users_in_a_file_task'] = 'e-ABC Registro de Usuarios en un lote de archivo';
$string['clean_overridden_grades_task'] = 'e-ABC Limpieza de Calificaciones Sobrescritas';
$string['view_logs'] = 'Ver logs';

// Status strings.
$string['status_disabled'] = 'Desactivado';
$string['status_preview'] = 'Previsualización';
$string['status_pending'] = 'Pendiente';
$string['status_senttoqueue'] = 'En cola';
$string['status_processing'] = 'Procesando';
$string['status_completed'] = 'Completado';
$string['status_failed'] = 'Fallido';
$string['type_manual'] = 'Manual';
$string['type_automated'] = 'Automatizado';
$string['planner_type_user'] = 'Usuario';
$string['planner_type_course'] = 'Curso';

// Form strings.
$string['form_strategy'] = 'Estrategia de migración';
$string['errornouserid'] = 'No se pudo encontrar una columna de identificación de usuario en el archivo CSV. Se esperaba una cabecera como: {$a}.';

// Report builder.
$string['entity_config'] = '[eabcetlbridge] Configuración de Estrategia de Migración';
$string['entity_batch_file'] = '[eabcetlbridge] Archivos de Migración de Lote';
$string['entity_planner'] = '[eabcetlbridge] Planificadores';
$string['entity_adhoc_task'] = '[eabcetlbridge] Tareas Ad-hoc';
$string['column_id'] = 'ID';
$string['column_name'] = 'Nombre';
$string['column_shortname'] = 'Abreviatura';
$string['column_strategyclass'] = 'Clase de Estrategia';
$string['column_sourcequery'] = 'Consulta de Origen';
$string['column_mapping'] = 'Mapeo';
$string['column_isenabled'] = '¿Está activo?';
$string['column_lastruntime'] = 'Última ejecución';
$string['column_usermodified'] = 'Modificado por';
$string['column_timecreated'] = 'Creado';
$string['column_timemodified'] = 'Modificado';
$string['column_timestarted'] = 'Iniciado';
$string['column_status'] = 'Estado';
$string['column_component'] = 'Componente';
$string['column_filearea'] = 'Área de Archivo';
$string['column_filename'] = 'Nombre de Archivo';
$string['column_filepath'] = 'Ruta de Archivo';
$string['column_type'] = 'Tipo';
$string['column_delimiter'] = 'Delimitador';
$string['column_encoding'] = 'Codificación';
$string['column_qtylines'] = 'Cantidad de Líneas';
$string['column_qtyrecords'] = 'Cantidad de Registros';
$string['column_qtyrecordsprocessed'] = 'Cantidad de Registros Procesados';
$string['column_errormessages'] = 'Mensajes de Error';
$string['column_objective'] = 'Objetivo';
$string['column_itemidentifier'] = 'Identificador';
$string['column_courseid'] = 'ID de Curso';
$string['column_batchfileid'] = 'ID de Archivo de Lote';
$string['column_configid'] = 'ID de Configuración';
$string['column_classname'] = 'Clase';
$string['column_nextruntime'] = 'Próxima ejecución';
$string['column_faildelay'] = 'Retraso de Fallo';
$string['column_attemptsavailable'] = 'Intentos disponibles';
$string['column_customdata'] = 'Datos Personalizados';
$string['column_pid'] = 'PID';

