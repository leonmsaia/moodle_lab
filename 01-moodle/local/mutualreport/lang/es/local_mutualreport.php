<?php

defined('MOODLE_INTERNAL') || die();


$string['pluginname'] = 'ELSA - Reporte de usuarios';
$string['mutualreport:view'] = 'Ver reporte de usuarios';
$string['finalized'] = 'Fecha de finalización';
$string['enroleddate'] = 'Fecha de matriculación';
$string['currentgrade'] = 'Calificación actual';
$string['sendedgrade'] = 'Calificación enviada';
$string['status'] = 'Estado';
$string['courselastaccess'] = 'Ultimo acceso al curso';
$string['coursefirstaccess'] = 'Primer acceso al curso';
$string['sitelastaccess'] = 'Ultimo acceso al sitio';
$string['companyname'] = 'Nombre de empresa';
$string['companyrut'] = 'Rut de empresa';
$string['nroadherente'] = 'Nro adherente';
$string['managername'] = 'Nombre de encargado';
$string['managerlastname'] = 'Apellido de encargado';
$string['managermail'] = 'Email de encargado';
$string['managerrut'] = 'Rut de encargado';
$string['fromdate'] = 'Matriculados desde';
$string['todate'] = 'Matriculados hasta';
$string['company'] = 'Empresa';
$string['rut'] = 'Rut alumno';
$string['filter'] = 'Filtrar';
$string['course'] = 'Curso';
$string['rut_company'] = 'Rut empresa';
$string['adherente'] = 'Nro adherente';
$string['grade_approved'] = 'Aprobado';
$string['grade_failedforabsence'] = 'Reprobado por inasistencia';
$string['grade_failed'] = 'Reprobado';
$string['grade_inprogress'] = 'En curso';

// Settings.
$string['migrationdate_heading'] = 'Fecha de corte para reportes';
$string['migration_year'] = 'Año';
$string['migration_year_desc'] = 'Año de la fecha de corte.';
$string['migration_month'] = 'Mes';
$string['migration_month_desc'] = 'Mes de la fecha de corte.';
$string['migration_day'] = 'Día';
$string['migration_day_desc'] = 'Día de la fecha de corte.';
$string['migration_hour'] = 'Hora';
$string['migration_hour_desc'] = 'Hora de la fecha de corte.';
$string['migration_minute'] = 'Minuto';
$string['migration_minute_desc'] = 'Minuto de la fecha de corte.';
$string['migration_second'] = 'Segundo';
$string['migration_second_desc'] = 'Segundo de la fecha de corte.';
$string['migrationdate35_heading'] = 'Fecha de corte para reportes Históricos (3.5)';
$string['migration_year35'] = 'Año (3.5)';
$string['migration_month35'] = 'Mes (3.5)';
$string['migration_day35'] = 'Día (3.5)';
$string['migration_hour35'] = 'Hora (3.5)';
$string['migration_minute35'] = 'Minuto (3.5)';
$string['migration_second35'] = 'Segundo (3.5)';

$string['datefilter_heading'] = 'Configuración del filtro de fecha para reportes';
$string['datefilter_heading_desc'] = 'Estas opciones controlan si la fecha de corte se aplica para filtrar los datos que se muestran en los reportes ELSA.';
$string['enable_datefilter_admin'] = 'Activar filtro de fecha para administradores';
$string['enable_datefilter_admin_desc'] = 'Si se activa, los administradores verán los datos de los reportes filtrados por la fecha de corte.';
$string['enable_datefilter_user'] = 'Activar filtro de fecha para otros usuarios';
$string['enable_datefilter_user_desc'] = 'Si se activa, los usuarios con permiso para ver reportes (no administradores) verán los datos filtrados por la fecha de corte.';
$string['excluded_users_datefilter'] = 'Excluir usuarios del filtro de fecha';
$string['excluded_users_datefilter_desc'] = 'Ingrese una lista de IDs de usuario separados por coma que deben ser excluidos del filtro de fecha. Estos usuarios siempre verán todos los datos, sin importar las configuraciones anteriores.';
$string['default_dates_v2_heading'] = 'Rango de fechas por defecto para Reporte ELSA v2';
$string['default_dates_v2_heading_desc'] = 'Configura el rango de fechas por defecto para el reporte principal de ELSA.';
$string['default_date_from_v2'] = 'Fecha "desde" por defecto (días atrás)';
$string['default_date_from_v2_desc'] = 'Número de días en el pasado para establecer la fecha "desde" por defecto. Por ejemplo, 30 para 30 días atrás.';
$string['default_date_to_v2'] = 'Fecha "hasta" por defecto (días en el futuro)';
$string['default_date_to_v2_desc'] = 'Número de días en el futuro para establecer la fecha "hasta" por defecto. Por ejemplo, 1 para mañana.';
$string['default_dates_35_heading'] = 'Rango de fechas por defecto para Reporte Histórico (3.5)';
$string['default_dates_35_heading_desc'] = 'Configura el rango de fechas por defecto para el reporte histórico de ELSA.';
$string['default_single_company'] = 'Preseleccionar compañía única';
$string['default_single_company_desc'] = 'Si está habilitado y un usuario tiene acceso a una sola compañía, esta se seleccionará por defecto en los filtros del reporte.';
$string['default_date_from_35'] = 'Fecha "desde" por defecto (días antes del corte)';
$string['default_date_from_35_desc'] = 'Número de días antes de la fecha de corte de migración para establecer la fecha "desde" por defecto. Por ejemplo, 30 para 30 días antes de la fecha de corte.';
$string['externaldb_heading'] = 'Base de datos externa';
$string['external_db_mnethostid'] = 'ID del host de la base de datos externa';
$string['external_db_mnethostid_desc'] = 'ID del host de la base de datos externa. Por ejemplo, 1 para la base de datos principal.';
$string['reportvisibility_heading'] = 'Visibilidad de reportes';
$string['reportvisibility_heading_desc'] = 'Configura qué reportes están disponibles en el menú de la barra de acciones.';
$string['report_visibility_label'] = 'Visibilidad para el reporte: {$a}';
$string['report_visibility_label_desc'] = 'Controla quién puede ver el reporte "{$a}" en el menú de navegación.';
$string['report_visibility_users'] = 'Usuarios específicos para el reporte: {$a}';
$string['report_visibility_users_desc'] = 'Si la visibilidad está configurada como "Solo para usuarios específicos", ingrese aquí una lista de IDs de usuario separados por comas.';
$string['report_sort_order_label'] = 'Orden para el reporte: {$a}';
$string['report_sort_order_label_desc'] = 'Define el orden de aparición en los menús (números más bajos aparecen primero).';
$string['visibility_everyone'] = 'Habilitado para todos';
$string['visibility_admins'] = 'Solo para administradores';
$string['visibility_specific'] = 'Solo para usuarios específicos';
$string['visibility_disabled'] = 'Deshabilitado';

// Reportbuilder.
$string['field_id'] = 'ID';
$string['field_rut'] = 'Rut';
$string['field_calificacionenviada'] = 'Calificación';
$string['filter_company'] = 'Seleccionar empresa';
$string['filter_company_help'] = 'Busque empresas por nombre, RUT o número de adherente.';
$string['filter_usernamelist'] = 'Listado de ruts de usuarios';
$string['filter_usernamelist_help'] = 'Escriba los ruts de los usuarios, uno por línea o separados por coma/espacio.';
$string['filter_companyrutlist'] = 'Listado de RUTs de empresa';
$string['filter_companyrutlist_help'] = 'Escriba los RUTs de las empresas, uno por línea o separados por coma/espacio.';
$string['filter_courseshortnamelist'] = 'Listado de nombres cortos de curso';
$string['filter_courseshortnamelist_help'] = 'Escriba los nombres cortos de los cursos, uno por línea o separados por coma/espacio.';
$string['filter_companycontratolist'] = 'Listado de Nros. de adherente';
$string['filter_companycontratolist_help'] = 'Escriba los números de adherente, uno por línea o separados por coma/espacio.';
$string['company_user_entity'] = 'Empleado';
$string['company_entity'] = 'Empresa';
$string['report_elsa_maingroup'] = 'ELSA - Reporte de usuarios';
$string['report_elsa_35group'] = 'ELSA - Reporte de usuarios (Moodle 3.5)';
$string['report_elsa_v1'] = 'ELSA v1';
$string['report_elsa_consolidado_v1'] = 'Reporte ELSA';
$string['report_elsa_consolidado_v35'] = 'Reporte ELSA 3.5 (Histórico)';
$string['report_elsa_v2'] = 'ELSA v2';
$string['report_elsa_35'] = 'ELSA_35';
$string['report_elsa_v2_heading'] = 'Reporte de Usuarios ELSA';
$string['report_elsa_v1_heading'] = 'Reporte de Usuarios ELSA';
$string['report_elsa_consolidado_v1_heading'] = 'ELSA - Reporte de usuarios';
$string['report_elsa_consolidado_v35_heading'] = 'ELSA - Reporte de usuarios (Moodle 3.5)';
$string['report_elsa_35_heading'] = 'Reporte Histórico de Usuarios (ELSA Moodle 3.5)';
$string['download_report_elsa35'] = 'reporte_elsa_moodle35';
$string['download_report_elsa45'] = 'reporte_elsa_moodle45';
$string['download_report_elsa_consolidado'] = 'reporte_elsa_consolidado';

// Report descripcion.
$string['report_instructions_text_elsa_v2'] = '<div id="mutualreport_elsareport" class="mutualreport" role="">

    <p>
        Este reporte ha sido diseñado para proporcionar una vista detallada de los usuarios, su progreso académico y su información corporativa asociada. Permite al personal docente y administrativo consultar datos clave de matriculación, actividad y, fundamentalmente, el estado actual de cada estudiante en sus cursos.
    </p>

    <hr>

    <h5 class="mt-3">Interpretación del "Estado" del Estudiante</h5>
    <p>La columna "Estado" es el indicador principal del rendimiento del usuario. Se calcula automáticamente según las siguientes reglas, basadas en un <strong>plazo de 30 días desde la matriculación</strong>:</p>

    <ul class="list-group">

        <li class="list-group-item">
            <strong><i class="icon fa fa-check-circle fa-fw text-success" aria-hidden="true"></i> Aprobado</strong>
            <br>
            <small class="text-muted">El estudiante ha obtenido una calificación final igual o superior a la calificación mínima para aprobar el curso. Este estado es independiente del tiempo transcurrido.</small>
        </li>

        <li class="list-group-item">
            <strong><i class="icon fa fa-clock-o fa-fw text-info" aria-hidden="true"></i> En Curso</strong>
            <br>
            <small class="text-muted">El estudiante se ha matriculado hace <strong>menos de 30 días</strong> y aún no ha alcanzado la calificación aprobatoria.</small>
        </li>

        <li class="list-group-item">
            <strong><i class="icon fa fa-times-circle fa-fw text-danger" aria-hidden="true"></i> Reprobado</strong>
            <br>
            <small class="text-muted">El estudiante se matriculó hace <strong>más de 30 días</strong>, posee una calificación final, pero esta es inferior a la mínima para aprobar.</small>
        </li>

        <li class="list-group-item">
            <strong><i class="icon fa fa-ban fa-fw text-warning" aria-hidden="true"></i> Reprobado por Inasistencia</strong>
            <br>
            <small class="text-muted">El estudiante se matriculó hace <strong>más de 30 días</strong> y <strong>no posee ninguna calificación final</strong>, indicando inactividad o abandono.</small>
        </li>
    </ul>

    <h5 class="mt-4">Datos Clave del Reporte</h5>
    <div class="list-group">
        <div class="list-group-item">
            <strong><i class="icon fa fa-star fa-fw" aria-hidden="true"></i> Columna "Calificación"</strong>
            <p class="mb-0 text-muted">Esta columna muestra la calificación final <strong>únicamente si el estado es "Aprobado" o "Reprobado"</strong>. Permanecerá en blanco para los estudiantes "En Curso" o "Reprobados por Inasistencia".</p>
        </div>

        <div class="list-group-item">
            <strong><i class="icon fa fa-filter fa-fw" aria-hidden="true"></i> Filtros y Datos Corporativos</strong>
            <p class="mb-0 text-muted">El reporte permite filtrar por un rango de fechas de <strong>matriculación</strong>. Además, muestra información de la empresa (Nombre, RUT, Nro. Adherente) y del encargado (Nombre, Email, RUT) asociados a cada usuario para facilitar la gestión y el seguimiento.</p>
        </div>
    </div>

    <hr>

    <p class="mb-0">
        <i class="icon fa fa-info-circle fa-fw" aria-hidden="true"></i>
        Utilice los filtros que se muestran a continuación para acotar su búsqueda por RUT de usuario, RUT de empresa, Nro. de adherente o curso específico.
    </p>

</div>';
$string['report_instructions_text_elsa_35'] = '<div id="godeep_elsa35_report" class="godeep alert alert-warning" role="alert">

    <p>
        Este reporte es una consulta histórica diseñada para mostrar la trazabilidad de los datos de estudiantes <strong>previos a la migración</strong> a la nueva plataforma.
    </p>

    <div class="alert alert-danger" role="alert">
        <h5 class="alert-heading mb-0">
            <i class="icon fa fa-exclamation-triangle fa-fw" aria-hidden="true"></i>
            <strong>Atención: Datos Históricos</strong>
        </h5>
        <p class="mb-0 mt-2">
            La información mostrada aquí proviene de la plataforma anterior (Moodle 3.5) y <strong>NO se actualizará</strong>.
            Solo incluye matrículas registradas hasta el <strong>{$a->date}</strong>.
        </p>
    </div>

    {$a->navlink}
    <hr>

    <h5 class="mt-3">Interpretación del "Estado" del Estudiante (Lógica Moodle 3.5)</h5>
    <p>La columna "Estado" es el indicador principal del rendimiento del usuario. Se calcula automáticamente según las siguientes reglas, basadas en un <strong>plazo de 30 días desde la matriculación</strong>:</p>

    <ul class="list-group">

        <li class="list-group-item">
            <strong><i class="icon fa fa-check-circle fa-fw text-success" aria-hidden="true"></i> Aprobado</strong>
            <br>
            <small class="text-muted">El estudiante obtuvo una calificación final igual o superior a la mínima para aprobar el curso.</small>
        </li>

        <li class="list-group-item">
            <strong><i class="icon fa fa-clock-o fa-fw text-info" aria-hidden="true"></i> En Curso</strong>
            <br>
            <small class="text-muted">El estudiante se matriculó hace <strong>menos de 30 días</strong> (respecto a la fecha de consulta en la antigua plataforma) y aún no había alcanzado la calificación aprobatoria.</small>
        </li>

        <li class="list-group-item">
            <strong><i class="icon fa fa-times-circle fa-fw text-danger" aria-hidden="true"></i> Reprobado</strong>
            <br>
            <small class="text-muted">El estudiante se matriculó hace <strong>más de 30 días</strong>, poseía una calificación final, pero esta era inferior a la mínima para aprobar.</small>
        </li>

        <li class="list-group-item">
            <strong><i class="icon fa fa-ban fa-fw text-warning" aria-hidden="true"></i> Reprobado por Inasistencia</strong>
            <br>
            <small class="text-muted">El estudiante se matriculó hace <strong>más de 30 días</strong> y <strong>no poseía ninguna calificación final</strong> (<code>NULL</code>), indicando inactividad o abandono en ese momento.</small>
        </li>
    </ul>

    <h5 class="mt-4">Datos Clave del Reporte Histórico</h5>
    <div class="list-group">
        <div class="list-group-item">
            <strong><i class="icon fa fa-star fa-fw" aria-hidden="true"></i> Columna "Calificación"</strong>
            <p class="mb-0 text-muted">Muestra la calificación final (truncada) <strong>únicamente si el estado era "Aprobado" o "Reprobado"</strong> en la plataforma anterior. Permanece en blanco para los demás estados.</p>
        </div>

        <div class="list-group-item">
            <strong><i class="icon fa fa-filter fa-fw" aria-hidden="true"></i> Filtros y Datos Corporativos</strong>
            <p class="mb-0 text-muted">El reporte permite filtrar por un rango de fechas de <strong>matriculación</strong> (siempre anterior al {$a}), RUT de usuario, RUT de empresa, Nro. de adherente o curso.</p>
        </div>
    </div>

    <hr>

    <p class="mb-0">
        <i class="icon fa fa-info-circle fa-fw" aria-hidden="true"></i>
        Utilice los filtros que se muestran a continuación para acotar su búsqueda por RUT de usuario, RUT de empresa, Nro. de adherente o curso. Este reporte es de <strong>solo consulta</strong> y sirve para auditoría y trazabilidad de datos pre-migración. Para datos actuales, por favor utilice el reporte ELSA estándar.
    </p>
</div>';
$string['report_instructions_text_elsa_consolidado_v1'] = '<div id="mutualreport_elsareport" class="mutualreport" role="">
    <div class="alert alert-info" role="alert">
        <h5 class="alert-heading mb-0">
            <i class="icon fa fa-info-circle fa-fw" aria-hidden="true"></i>
            <strong>Reporte plataforma actual (Reporte ELSA)</strong>
        </h5>
        <p class="mb-0 mt-2">
            Incluye las capacitaciones registradas a partir del <strong>{$a->date}</strong>.
        </p>
    </div>

    {$a->navlink}
    <hr>

    <div id="mutualreport_accordion">
        <div class="card">
            <div class="card-header bg-light" id="mutualreport_moreinfo1">
                <button class="btn btn-link btn-block text-left" data-toggle="collapse" data-target="#mutualreport_collapse1" aria-expanded="true" aria-controls="mutualreport_collapse1">
                    <i class="icon fa fa-info-circle fa-fw" aria-hidden="true"></i> Guía de interpretación del reporte
                </button>
            </div>

            <div id="mutualreport_collapse1" class="collapse" aria-labelledby="mutualreport_moreinfo1" data-parent="#mutualreport_accordion">
                <div class="card-body">
                    <h5 class="mt-3">Interpretación del "Estado" del Estudiante</h5>
                    <p>La columna "Estado" es el indicador principal del rendimiento del usuario. Se calcula automáticamente según las siguientes reglas, basadas en un <strong>plazo de 30 días desde la matriculación</strong>:</p>

                    <ul class="list-group">

                        <li class="list-group-item">
                            <strong><i class="icon fa fa-check-circle fa-fw text-success" aria-hidden="true"></i> Aprobado</strong>
                            <br>
                            <small class="text-muted">El estudiante ha obtenido una calificación final igual o superior a la calificación mínima para aprobar el curso. Este estado es independiente del tiempo transcurrido.</small>
                        </li>

                        <li class="list-group-item">
                            <strong><i class="icon fa fa-clock-o fa-fw text-info" aria-hidden="true"></i> En Curso</strong>
                            <br>
                            <small class="text-muted">El estudiante se ha matriculado hace <strong>menos de 30 días</strong> y aún no ha alcanzado la calificación aprobatoria.</small>
                        </li>

                        <li class="list-group-item">
                            <strong><i class="icon fa fa-times-circle fa-fw text-danger" aria-hidden="true"></i> Reprobado</strong>
                            <br>
                            <small class="text-muted">El estudiante se matriculó hace <strong>más de 30 días</strong>, posee una calificación final, pero esta es inferior a la mínima para aprobar.</small>
                        </li>

                        <li class="list-group-item">
                            <strong><i class="icon fa fa-ban fa-fw text-warning" aria-hidden="true"></i> Reprobado por Inasistencia</strong>
                            <br>
                            <small class="text-muted">El estudiante se matriculó hace <strong>más de 30 días</strong> y <strong>no posee ninguna calificación final</strong>, indicando inactividad o abandono.</small>
                        </li>
                    </ul>

                    <h5 class="mt-4">Datos Clave del Reporte</h5>
                    <div class="list-group">
                        <div class="list-group-item">
                            <strong><i class="icon fa fa-star fa-fw" aria-hidden="true"></i> Columna "Calificación"</strong>
                            <p class="mb-0 text-muted">Esta columna muestra la calificación final <strong>únicamente si el estado es "Aprobado" o "Reprobado"</strong>. Permanecerá en blanco para los estudiantes "En Curso" o "Reprobados por Inasistencia".</p>
                        </div>

                        <div class="list-group-item">
                            <strong><i class="icon fa fa-filter fa-fw" aria-hidden="true"></i> Filtros y Datos Corporativos</strong>
                            <p class="mb-0 text-muted">El reporte permite filtrar por un rango de fechas de <strong>matriculación</strong>. Además, muestra información de la empresa (Nombre, RUT, Nro. Adherente) y del encargado (Nombre, Email, RUT) asociados a cada usuario para facilitar la gestión y el seguimiento.</p>
                        </div>
                    </div>

                    <hr>

                    <p class="mb-0">
                        <i class="icon fa fa-info-circle fa-fw" aria-hidden="true"></i>
                        Utilice los filtros que se muestran a continuación para acotar su búsqueda por RUT de usuario, RUT de empresa, Nro. de adherente o curso específico.
                    </p>
                </div>
            </div>
        </div>

</div>';
$string['report_instructions_text_elsa_consolidado_v35'] = '<div id="godeep_elsa35_report" class="godeep alert alert-warning" role="alert">

    <div class="alert alert-danger" role="alert">
        <h5 class="alert-heading mb-0">
            <i class="icon fa fa-exclamation-triangle fa-fw" aria-hidden="true"></i>
            <strong>Reporte Histórico 3.5 (Histórico)</strong>
        </h5>
        <p class="mb-0 mt-2">
            Incluye las actividades históricas registradas hasta el <strong>{$a->date}</strong>.
        </p>
    </div>

    {$a->navlink}
    <hr>

    <div id="mutualreport_accordion_v35">
        <div class="card">
            <div class="card-header bg-light" id="mutualreport_moreinfo_v35">
                <button class="btn btn-link btn-block text-left" data-toggle="collapse" data-target="#mutualreport_collapse_v35" aria-expanded="true" aria-controls="mutualreport_collapse_v35">
                    <i class="icon fa fa-info-circle fa-fw" aria-hidden="true"></i> Guía de interpretación del reporte histórico
                </button>
            </div>

            <div id="mutualreport_collapse_v35" class="collapse" aria-labelledby="mutualreport_moreinfo_v35" data-parent="#mutualreport_accordion_v35">
                <div class="card-body">

                    <h5 class="mt-3">Interpretación del "Estado" del Estudiante (Lógica Moodle 3.5)</h5>
                    <p>La columna "Estado" es el indicador principal del rendimiento del usuario. Se calcula automáticamente según las siguientes reglas, basadas en un <strong>plazo de 30 días desde la matriculación</strong>:</p>

                    <ul class="list-group">

                        <li class="list-group-item">
                            <strong><i class="icon fa fa-check-circle fa-fw text-success" aria-hidden="true"></i> Aprobado</strong>
                            <br>
                            <small class="text-muted">El estudiante obtuvo una calificación final igual o superior a la mínima para aprobar el curso.</small>
                        </li>

                        <li class="list-group-item">
                            <strong><i class="icon fa fa-clock-o fa-fw text-info" aria-hidden="true"></i> En Curso</strong>
                            <br>
                            <small class="text-muted">El estudiante se matriculó hace <strong>menos de 30 días</strong> (respecto a la fecha de consulta en la antigua plataforma) y aún no había alcanzado la calificación aprobatoria.</small>
                        </li>

                        <li class="list-group-item">
                            <strong><i class="icon fa fa-times-circle fa-fw text-danger" aria-hidden="true"></i> Reprobado</strong>
                            <br>
                            <small class="text-muted">El estudiante se matriculó hace <strong>más de 30 días</strong>, poseía una calificación final, pero esta era inferior a la mínima para aprobar.</small>
                        </li>

                        <li class="list-group-item">
                            <strong><i class="icon fa fa-ban fa-fw text-warning" aria-hidden="true"></i> Reprobado por Inasistencia</strong>
                            <br>
                            <small class="text-muted">El estudiante se matriculó hace <strong>más de 30 días</strong> y <strong>no poseía ninguna calificación final</strong> (<code>NULL</code>), indicando inactividad o abandono en ese momento.</small>
                        </li>
                    </ul>

                    <h5 class="mt-4">Datos Clave del Reporte Histórico</h5>
                    <div class="list-group">
                        <div class="list-group-item">
                            <strong><i class="icon fa fa-star fa-fw" aria-hidden="true"></i> Columna "Calificación"</strong>
                            <p class="mb-0 text-muted">Muestra la calificación final (truncada) <strong>únicamente si el estado era "Aprobado" o "Reprobado"</strong> en la plataforma anterior. Permanece en blanco para los demás estados.</p>
                        </div>

                        <div class="list-group-item">
                            <strong><i class="icon fa fa-filter fa-fw" aria-hidden="true"></i> Filtros y Datos Corporativos</strong>
                            <p class="mb-0 text-muted">El reporte permite filtrar por un rango de fechas de <strong>matriculación</strong> (siempre anterior al {$a->date}), RUT de usuario, RUT de empresa, Nro. de adherente o curso.</p>
                        </div>
                    </div>

                    <hr>

                    <p class="mb-0">
                        <i class="icon fa fa-info-circle fa-fw" aria-hidden="true"></i>
                        Utilice los filtros que se muestran a continuación para acotar su búsqueda por RUT de usuario, RUT de empresa, Nro. de adherente o curso específico.
                    </p>

                </div>
            </div>
        </div>
    </div>

    
</div>';
$string['report_navigation_to_historical'] = '<div class="alert alert-secondary" role="alert">
    <p class="mb-0">
        <i class="icon fa fa-history fa-fw" aria-hidden="true"></i>
        <strong>Reporte Histórico 3.5 (Histórico):</strong> Incluye las actividades históricas registradas hasta el <strong>{$a->date}</strong>. Para acceder a este reporte histórico, ingresa al siguiente enlace: <a href="{$a->url}" class="alert-link"><strong>Reporte Histórico 3.5 (Histórico)</strong></a>
    </p>
</div>';
$string['report_navigation_to_current'] = '<div class="alert alert-secondary" role="alert">
    <p class="mb-0">
        <i class="icon fa fa-chart-bar fa-fw" aria-hidden="true"></i>
        <strong>Reporte plataforma actual (Reporte ELSA)</strong> Incluye las capacitaciones registradas a partir del <strong>{$a->date}</strong>.” Para volver al reporte principal, ingresa al siguiente enlace: <a href="{$a->url}" class="alert-link"><strong>Reporte plataforma actual (Reporte ELSA)</strong></a>
    </p>
</div>';
$string['filter_date_cutoff_info_45'] = '<strong>Información:</strong> Para este reporte, solo se pueden seleccionar fechas a partir del <strong>{$a->cutoffdate}</strong>. Las fechas anteriores a esta no serán consideradas.';
$string['filter_date_cutoff_info_35'] = '<strong>Información:</strong> Para este reporte histórico, solo se pueden seleccionar fechas hasta el <strong>{$a->cutoffdate}</strong>. Las fechas posteriores a esta no serán consideradas.';
