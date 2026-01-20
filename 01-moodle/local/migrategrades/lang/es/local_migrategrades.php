<?php

$string['pluginname'] = 'Migración de notas (viejo → nuevo)';
$string['settingstitle'] = 'Migración de notas';

$string['dbsettings'] = 'Conexión Moodle viejo';
$string['old_dbhost'] = 'Host BD (viejo)';
$string['old_dbport'] = 'Puerto BD (viejo)';
$string['old_dbname'] = 'Nombre BD (viejo)';
$string['old_dbuser'] = 'Usuario BD (viejo)';
$string['old_dbpass'] = 'Password BD (viejo)';
$string['old_dbprefix'] = 'Prefijo tablas (viejo)';
$string['old_dbcharset'] = 'Charset (viejo)';

$string['gradesettings'] = 'Notas';
$string['old_grade_history_from'] = 'Desde (histórico de notas viejo)';
$string['old_grade_history_from_help'] = 'Solo considera grade_grades_history del Moodle viejo desde esta fecha/hora (formato: YYYY-MM-DD HH:MM:SS).';

$string['uploadtitle'] = 'Subir CSV para migrar notas';
$string['upload'] = 'Subir';
$string['file'] = 'Archivo CSV';
$string['checktitle'] = 'Validar conexión a BD vieja';
$string['backtoupload'] = 'Volver a carga CSV';
$string['check_ok'] = 'Conexión OK a la base de datos vieja.';
$string['check_fail'] = 'Sin conexión a la base de datos vieja: {$a}';
$string['results'] = 'Resultados';
$string['processed'] = 'Filas procesadas';
$string['updated'] = 'Filas actualizadas';
$string['skipped'] = 'Filas sin cambios';
$string['errors'] = 'Errores';

$string['fileempty'] = 'Archivo vacío';

$string['companyassigntitle'] = 'Asignar empresa por RUT (SOAP)';
$string['companyassign_csv_help'] = 'El CSV debe tener cabecera: username (RUT, por ejemplo 11225459-5).';
$string['companyassign_linked_count'] = 'Usuarios vinculados';
$string['companyassign_user_missing'] = 'Usuario no existe en Moodle.';
$string['companyassign_soap_error'] = 'SOAP error: {$a}';
$string['companyassign_no_company_in_soap'] = 'SOAP OK, pero sin empresa en respuesta.';
$string['companyassign_company_missing'] = 'Empresa no existe en tabla company: {$a}';
$string['companyassign_linked'] = 'Asignado a empresa {$a} y campos actualizados.';

$string['soapchecktitle'] = 'Consultar SOAP por RUT';
$string['soapcheck_username'] = 'Username (RUT)';
$string['soapcheck_submit'] = 'Consultar';
$string['soapcheck_rawxml'] = 'XML crudo';

$string['csv_help'] = 'El CSV debe tener cabeceras: username, shortname';
$string['csv_missing_headers'] = 'Faltan cabeceras requeridas en el CSV: {$a}';
$string['row_error'] = 'Error en fila {$a}';
$string['missing_user_new'] = 'Usuario no existe en Moodle nuevo';
$string['missing_course_new'] = 'Curso no existe en Moodle nuevo';
$string['missing_gradeitem_new'] = 'No existe ítem de calificación del curso en Moodle nuevo';
$string['missing_user_old'] = 'Usuario no existe en Moodle viejo';
$string['missing_course_old'] = 'Curso no existe en Moodle viejo';
$string['missing_gradeitem_old'] = 'No existe ítem de calificación del curso en Moodle viejo';
$string['missing_grade_old'] = 'No existe nota en Moodle viejo';
$string['grade_not_higher'] = 'La nota del viejo no es mayor que la del nuevo';
$string['grade_updated'] = 'Nota actualizada en Moodle nuevo';
