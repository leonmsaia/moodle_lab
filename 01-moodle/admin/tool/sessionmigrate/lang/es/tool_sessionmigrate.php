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
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Herramienta de Migración de Sesiones';
$string['coursearch'] = 'Búsqueda de Cursos';
$string['closedgroupsqueue'] = 'Cola: grupos cerrados(3.5) abiertos(4.5)';
$string['searchcourses'] = 'Buscar Cursos en Moodle 3.5';
$string['search'] = 'Buscar';
$string['productoid'] = 'ID de Producto';
$string['shortname'] = 'Nombre Corto';
$string['nocoursesfound'] = 'No se encontraron cursos con los criterios dados.';
$string['err_atleastonefield'] = 'Por favor, proporcione un ID de Producto o un Nombre Corto para buscar.';
$string['syncsessions'] = 'Sincronizar sesiones cerradas';
$string['syncsessionsconfirm'] = 'Está a punto de iniciar la sincronización de sesiones cerradas para este curso. Este proceso puede ser lento ya que validará cada sesión individualmente. ¿Desea continuar?';
$string['syncsessionsstarted'] = 'La sincronización de sesiones se ha iniciado en segundo plano.';
$string['logviewer'] = 'Visor de Logs de Acciones';
$string['id'] = 'ID';
$string['action'] = 'Acción';
$string['targettype'] = 'Tipo de Objetivo';
$string['targetidentifier'] = 'Identificador del Objetivo';
$string['status'] = 'Estado';
$string['message'] = 'Mensaje';
$string['triggeredby'] = 'Iniciado por';
$string['timecreated'] = 'Fecha de Creación';
$string['timemodified'] = 'Fecha de Modificación';
$string['nologsfound'] = 'No se encontraron entradas de log.';
$string['unknownuser'] = 'Usuario Desconocido';

$string['invaliddbconnection'] = 'No se pudo conectar a la base de datos de Moodle 3.5. Por favor, revise la configuración de conexión.';
$string['sessionsearch'] = 'Búsqueda de Sesiones';
$string['searchsessions'] = 'Buscar Sesiones en Moodle 3.5 por GUID';
$string['sessionguids'] = 'GUIDs de Sesión (uno por línea)';
$string['err_sessionguidsrequired'] = 'Por favor, proporcione al menos un GUID de sesión.';
$string['nosessionsfound'] = 'No se encontraron sesiones con los GUIDs proporcionados.';
$string['migratesession'] = 'Migrar Sesión';
$string['migratesessionconfirm'] = 'Está a punto de iniciar la migración de esta sesión. ¿Desea continuar?';
$string['migratesessionstarted'] = 'La migración de la sesión se ha iniciado en segundo plano.';

// Strings for duplicate search
$string['duplicatesearch'] = 'Búsqueda de Sesiones Duplicadas';
$string['searchbygrupoid'] = 'Buscar por ID de Grupo';
$string['searchbyidevento'] = 'Buscar por ID de Evento';
$string['searchbysessionguid'] = 'Buscar por GUID de Sesión';
$string['searchtype'] = 'Tipo de Búsqueda';
$string['searchvalue'] = 'Valor de Búsqueda';
$string['deletesession'] = 'Eliminar Sesión';
$string['deletesessionconfirm'] = '¿Está seguro de que desea eliminar la sesión con GUID: {$a}? Esta acción no se puede deshacer.';
$string['deletesesionbackconfirm'] = '¿Está seguro de que desea eliminar el registro de sesion_back con ID: {$a}? Esta acción no se puede deshacer.';
$string['deletesessionstarted'] = 'La eliminación de la sesión se ha iniciado en segundo plano.';
$string['details'] = 'Detalles';
$string['viewdetails'] = 'Ver detalles';

$string['migrationbydate'] = 'Migración por Rango de Fechas';
$string['startdate'] = 'Fecha de inicio';
$string['enddate'] = 'Fecha de fin';
$string['enddatebeforestartdate'] = 'La fecha de fin debe ser posterior a la fecha de inicio.';
$string['sessionsfound'] = 'sesiones encontradas';
$string['coursesaffected'] = 'Cursos Afectados (Nombre Corto)';
$string['migratesessionsbydate'] = 'Migrar Sesiones por Fecha';
$string['migratesessionsbydateconfirm'] = 'Está a punto de migrar {$a->count} sesiones entre {$a->startdate} y {$a->enddate}. Esto afectará a los siguientes cursos: {$a->courses}. ¿Desea continuar?';
$string['migratesessionsbydatestarted'] = 'La migración de sesiones por fecha se ha iniciado en segundo plano.';
$string['downloadcourses'] = 'Descargar Cursos Afectados (CSV)';

$string['migrationbysessions'] = 'Migración masiva de sesiones';
$string['migratesessions'] = 'Migrar sesiones';
$string['migrationstarted'] = 'La migración masiva de sesiones se ha iniciado en segundo plano.';
$string['confirmbulkmigration'] = 'Estás a punto de iniciar la migración de {$a} sesiones. El proceso se ejecutará en segundo plano. ¿Deseas continuar?';
$string['nosessionguids'] = 'Debes introducir al menos un GUID de sesión.';

// Nuevas cadenas añadidas
$string['filter'] = 'Filtro';
$string['startdate'] = 'Fecha inicio';
$string['enddate'] = 'Fecha fin';

$string['migrationbydate'] = 'Migrar sesiones por rango de fechas';
$string['migratesessionsbydatestarted'] = 'Migración de sesiones iniciada';
$string['migratesessionsbydate'] = 'Migrar sesiones (rango de fechas)';
$string['migratesessionsbydateconfirm'] = '¿Seguro que desea migrar {$a->count} sesiones entre {$a->startdate} y {$a->enddate}? Cursos afectados: {$a->courses}';

$string['migrationbycourseanddate'] = 'Migrar sesiones por curso y rango de fechas';
$string['migratesessionsbycourseanddatestarted'] = 'Migración de sesiones (curso+fecha) iniciada';
$string['migratesessionsbycourseanddate'] = 'Migrar sesiones (curso + rango de fechas)';
$string['migratesessionsbycourseanddateconfirm'] = '¿Seguro que desea migrar {$a->count} sesiones del curso {$a->shortname} entre {$a->startdate} y {$a->enddate}? Cursos afectados: {$a->courses}';

$string['downloadcourses'] = 'Descargar cursos';
$string['sessionsfound'] = 'sesiones encontradas';
$string['coursesaffected'] = 'Cursos afectados';
$string['nocoursesfound'] = 'No se encontraron cursos';
$string['error_enddate_before_startdate'] = 'La fecha de fin debe ser posterior a la fecha de inicio';

