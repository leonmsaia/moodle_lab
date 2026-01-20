<?php

defined('MOODLE_INTERNAL') || die();


$string['pluginname'] = 'ELSA - Users report';
$string['mutualreport:view'] = 'View users report';
$string['finalized'] = 'Completion date';
$string['enroleddate'] = 'Enroled date';
$string['currentgrade'] = 'Current grade';
$string['sendedgrade'] = 'Sended grade';
$string['status'] = 'Status';
$string['courselastaccess'] = 'Course last access';
$string['coursefirstaccess'] = 'Course first access';
$string['sitelastaccess'] = 'Site last access';
$string['companyname'] = 'Company name';
$string['companyrut'] = 'Company Rut';
$string['nroadherente'] = 'Adherente number';
$string['managername'] = 'Manager name';
$string['managerlastname'] = 'Manager last name';
$string['managermail'] = 'Manager mail';
$string['managerrut'] = 'Manager rut';
$string['fromdate'] = 'Enroled from';
$string['todate'] = 'Enroled to';
$string['company'] = 'Company';
$string['rut'] = 'Student Rut';
$string['filter'] = 'Filter';
$string['course'] = 'Course';
$string['rut_company'] = 'Rut empresa';
$string['adherente'] = 'Nro adherente';
$string['grade_approved'] = 'Approved';
$string['grade_failedforabsence'] = 'Failed for absence';
$string['grade_failed'] = 'Failed';
$string['grade_inprogress'] = 'In progress';

// Settings.
$string['migrationdate_heading'] = 'Cut-off date for reports';
$string['migration_year'] = 'Year';
$string['migration_year_desc'] = 'Year for the cut-off date.';
$string['migration_month'] = 'Month';
$string['migration_month_desc'] = 'Month for the cut-off date.';
$string['migration_day'] = 'Day';
$string['migration_day_desc'] = 'Day for the cut-off date.';
$string['migration_hour'] = 'Hour';
$string['migration_hour_desc'] = 'Hour for the cut-off date.';
$string['migration_minute'] = 'Minute';
$string['migration_minute_desc'] = 'Minute for the cut-off date.';
$string['migration_second'] = 'Second';
$string['migration_second_desc'] = 'Second for the cut-off date.';
$string['migrationdate35_heading'] = 'Cut-off date for Historical (3.5) reports';
$string['migration_year35'] = 'Year (3.5)';
$string['migration_month35'] = 'Month (3.5)';
$string['migration_day35'] = 'Day (3.5)';
$string['migration_hour35'] = 'Hour (3.5)';
$string['migration_minute35'] = 'Minute (3.5)';
$string['migration_second35'] = 'Second (3.5)';

$string['datefilter_heading'] = 'Date filter settings for reports';
$string['datefilter_heading_desc'] = 'These settings control whether the cut-off date is applied to filter the data shown in the ELSA reports.';
$string['enable_datefilter_admin'] = 'Enable date filter for administrators';
$string['enable_datefilter_admin_desc'] = 'If enabled, administrators will see report data filtered by the cut-off date.';
$string['enable_datefilter_user'] = 'Enable date filter for other users';
$string['enable_datefilter_user_desc'] = 'If enabled, users with report viewing permission (non-admins) will see data filtered by the cut-off date.';
$string['excluded_users_datefilter'] = 'Exclude users from date filter';
$string['excluded_users_datefilter_desc'] = 'Enter a comma-separated list of user IDs that should be excluded from the date filter. These users will always see all data, regardless of the settings above.';
$string['default_dates_v2_heading'] = 'Default date range for ELSA v2 Report';
$string['default_dates_v2_heading_desc'] = 'Configure the default date range for the main ELSA report.';
$string['default_date_from_v2'] = 'Default "from" date (days ago)';
$string['default_date_from_v2_desc'] = 'Number of days in the past to set the default "from" date. For example, 30 for 30 days ago.';
$string['default_date_to_v2'] = 'Default "to" date (days in future)';
$string['default_date_to_v2_desc'] = 'Number of days in the future to set the default "to" date. For example, 1 for tomorrow.';
$string['default_dates_35_heading'] = 'Default date range for Historical Report (3.5)';
$string['default_dates_35_heading_desc'] = 'Configure the default date range for the historical ELSA report.';
$string['default_single_company'] = 'Preselect single company';
$string['default_single_company_desc'] = 'If enabled and a user has access to only one company, it will be selected by default in the report filters.';
$string['default_date_from_35'] = 'Default "from" date (days before cut-off)';
$string['default_date_from_35_desc'] = 'Number of days before the migration cut-off date to set the default "from" date. For example, 30 for 30 days before the cut-off.';
$string['externaldb_heading'] = 'External database';
$string['external_db_mnethostid'] = 'Moodle network host ID';
$string['external_db_mnethostid_desc'] = 'Moodle network host ID for the external database. For example, 1 for the main database.';
$string['reportvisibility_heading'] = 'Report visibility';
$string['reportvisibility_heading_desc'] = 'Configure which reports are available in the action bar menu.';
$string['report_visibility_label'] = 'Visibility for report: {$a}';
$string['report_visibility_label_desc'] = 'Controls who can see the "{$a}" report in the navigation menu.';
$string['report_visibility_users'] = 'Specific users for report: {$a}';
$string['report_visibility_users_desc'] = 'If visibility is set to "Specific users only", enter a comma-separated list of user IDs here.';
$string['report_sort_order_label'] = 'Sort order for report: {$a}';
$string['report_sort_order_label_desc'] = 'Defines the display order in menus (lower numbers appear first).';
$string['visibility_everyone'] = 'Enabled for everyone';
$string['visibility_admins'] = 'Admins only';
$string['visibility_specific'] = 'Specific users only';
$string['visibility_disabled'] = 'Disabled';

// Reportbuilder.
$string['field_id'] = 'ID';
$string['field_rut'] = 'Rut';
$string['field_calificacionenviada'] = 'Grade';
$string['filter_company'] = 'Select company';
$string['filter_company_help'] = 'Search for companies by name, RUT, or adherent number.';
$string['filter_usernamelist'] = 'Usernames list';
$string['filter_companyrutlist'] = 'Company RUT list';
$string['filter_companyrutlist_help'] = 'Enter company RUTs, one per line or separated by comma/space.';
$string['filter_companycontratolist'] = 'Adherent number list';
$string['filter_courseshortnamelist'] = 'Course shortname list';
$string['filter_courseshortnamelist_help'] = 'Enter course shortnames, one per line or separated by comma/space.';
$string['filter_companycontratolist_help'] = 'Enter adherent numbers, one per line or separated by comma/space.';
$string['filter_usernamelist_help'] = 'Write the usernames, one per line';
$string['company_user_entity'] = 'Employee';
$string['company_entity'] = 'Company';
$string['report_elsa_maingroup'] = 'ELSA - Users report';
$string['report_elsa_35group'] = 'ELSA - Temporal users report (Moodle 3.5)';
$string['report_elsa_v1'] = 'ELSA v1';
$string['report_elsa_consolidado_v1'] = 'ELSA Report';
$string['report_elsa_consolidado_v35'] = 'ELSA Report 3.5 (Historical)';
$string['report_elsa_v2'] = 'ELSA v2';
$string['report_elsa_35'] = 'ELSA_35';
$string['report_elsa_v2_heading'] = 'Users report ELSA';
$string['report_elsa_v1_heading'] = 'Users report ELSA';
$string['report_elsa_consolidado_v1_heading'] = 'Consolidated Report of Users (Current Platform)';
$string['report_elsa_consolidado_v35_heading'] = 'Consolidated Report of Users (Historical Platform)';
$string['report_elsa_35_heading'] = 'Historical report of users (ELSA Moodle 3.5)';
$string['download_report_elsa35'] = 'report_elsa_moodle35';
$string['download_report_elsa45'] = 'report_elsa_moodle45';
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
        Use the filters shown below to narrow your search by user RUT, company RUT, adherent number, or specific course.
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
            Solo incluye matrículas registradas hasta el <strong>17 de septiembre de 2025</strong>.
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
            <small class="text-muted">El estudiante se matriculó hace <strong>más de 30 días</strong> y <strong>no poseía ninguna calificación final</strong>, indicando inactividad o abandono en ese momento.</small>
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
            <p class="mb-0 text-muted">The report allows filtering by a range of <strong>enrolment</strong> dates (always before {$a}), user RUT, company RUT, adherent number, or course.</p>
        </div>
    </div>

    <hr>

    <p class="mb-0">
        <i class="icon fa fa-info-circle fa-fw" aria-hidden="true"></i>
        Use the filters shown below to narrow your search by user RUT, company RUT, adherent number, or course. This report is for <strong>query only</strong> and is used for auditing and traceability of pre-migration data. For current data, please use the standard ELSA report.
    </p>
</div>';
$string['report_instructions_text_elsa_consolidado_v1'] = '<div id="mutualreport_elsareport" class="mutualreport" role="">

    <p>
        Este reporte ha sido diseñado para proporcionar una vista detallada de los usuarios, su progreso académico y su información corporativa asociada. Permite al personal docente y administrativo consultar datos clave de matriculación, actividad y, fundamentalmente, el estado actual de cada estudiante en sus cursos.
    </p>

    <div class="alert alert-info" role="alert">
        <h5 class="alert-heading mb-0">
            <i class="icon fa fa-info-circle fa-fw" aria-hidden="true"></i>
            <strong>Atención: Datos de la Plataforma Actual</strong>
        </h5>
        <p class="mb-0 mt-2">
            La información mostrada aquí corresponde a la plataforma actual y se actualiza constantemente.
            Solo incluye matrículas registradas a partir del <strong>{$a}</strong>. Para datos anteriores a esta fecha, por favor utilice el reporte histórico.
        </p>
    </div>

    {$a->navlink}
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
$string['report_instructions_text_elsa_consolidado_v35'] = '<div id="godeep_elsa35_report" class="godeep alert alert-warning" role="alert">

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
            Solo incluye matrículas registradas hasta el <strong>17 de septiembre de 2025</strong>.
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
            <p class="mb-0 text-muted">El reporte permite filtrar por un rango de fechas de <strong>matriculación</strong> (siempre anterior al 17/09/2025), RUT de usuario, RUT de empresa, Nro. de adherente o curso.</p>
        </div>
    </div>

    <hr>

    <p class="mb-0">
        <i class="icon fa fa-info-circle fa-fw" aria-hidden="true"></i>
        Use the filters shown below to narrow your search by user RUT, company RUT, adherent number, or course. This report is for <strong>query only</strong> and is used for auditing and traceability of pre-migration data. For current data, please use the standard ELSA report.
    </p>
</div>';
$string['report_navigation_to_historical'] = '<div class="alert alert-secondary" role="alert">
    <p class="mb-0">
        <i class="icon fa fa-history fa-fw" aria-hidden="true"></i>
        To view historical data (prior to <strong>{$a->date}</strong>), you can access the historical report via the following link: <a href="{$a->url}" class="alert-link"><strong>Historical Report (3.5)</strong></a>.
    </p>
</div>';
$string['report_navigation_to_current'] = '<div class="alert alert-secondary" role="alert">
    <p class="mb-0">
        <i class="icon fa fa-chart-bar fa-fw" aria-hidden="true"></i>
        To view current data (after <strong>{$a->date}</strong>), you can access the main report via the following link: <a href="{$a->url}" class="alert-link"><strong>Current Report</strong></a>.
    </p>
</div>';
$string['filter_date_cutoff_info_45'] = '<strong>Information:</strong> For this report (current version), only dates from <strong>{$a->cutoffdate}</strong> onwards can be selected. Dates prior to this will not be considered.';
$string['filter_date_cutoff_info_35'] = '<strong>Information:</strong> For this historical report, only dates up to <strong>{$a->cutoffdate}</strong> can be selected. Dates after this will not be considered.';
