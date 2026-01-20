<?php

namespace local_mutualreport;

use coding_exception;
use dml_exception;
use moodle_database;
use core_component;
use core\url as moodle_url;

class utils
{
    /**
     * @return array
     * @throws coding_exception
     */
    public static function get_headers()
    {
        return [
            "Rut",
            get_string('name'),
            get_string('lastname'),
            get_string('course'),
            get_string('enroleddate', 'local_mutualreport'),
            get_string('finalized', 'local_mutualreport'),
            'Calificación',
            get_string('courselastaccess', 'local_mutualreport'),
            get_string('sitelastaccess', 'local_mutualreport'),
            get_string('status', 'local_mutualreport'),
            get_string('companyname', 'local_mutualreport'),
            get_string('companyrut', 'local_mutualreport'),
            get_string('nroadherente', 'local_mutualreport'),
            get_string('managername', 'local_mutualreport'),
            get_string('managermail', 'local_mutualreport'),
            get_string('managerrut', 'local_mutualreport'),
        ];
    }

    public static function get_columns()
    {
        return [
            "rut",
            "nombre",
            "apellido",
            "curso",
            "fechamatriculacion",
            "completadoenviado",
            "calificacionenviada",
            "ultimoaccesocurso",
            "ultimoaccesositio",
            "estado",
            "nombreempresa",
            "rutempresa",
            "nroadherente",
            "nombreencargado",
            "mailencargado",
            "rutencargado"
        ];
    }

    public static function get_fields_sql()
    {
        return "
       mue.id,
       u.username                                           as rut,
       u.id as userid,
       u.firstname                                          as nombre,
       u.lastname                                           as apellido,
       c.fullname                                           as curso,
       c.id as courseid,
       if(
            cc.timecompleted is not null, 
            from_unixtime(cc.timecompleted, '%d-%m-%Y %H:%i:%s'), 
            from_unixtime(gg.timemodified, '%d-%m-%Y %H:%i:%s')
       ) as completadoenviado,
       from_unixtime(cc.timecompleted, '%d-%m-%Y %H:%i:%s') as completado,
       from_unixtime(mue.timecreated, '%d-%m-%Y %H:%i:%s')  as fechamatriculacion,
       mue.timecreated,
       gg.finalgrade                                        as calificacionactual,
       if(
            gg.finalgrade >= gi.gradepass, 
            'Aprobado',
            if(
                from_unixtime(mue.timecreated + 60*60*24*30) < now() and gg.finalgrade is null,
                'Reprobado por inasistencia',
                if(
                    from_unixtime(mue.timecreated + 60*60*24*30) < now() and gg.finalgrade is not null,
                    'Reprobado',
                    'En curso'
                ) 
            ) 
        )    as estado,
        if(
            gg.finalgrade >= gi.gradepass, 
            truncate(gg.finalgrade, 0),
            if(
                from_unixtime(mue.timecreated + 60*60*24*30) < now() and gg.finalgrade is null,
                '',
                if(
                    from_unixtime(mue.timecreated + 60*60*24*30) < now() and gg.finalgrade is not null,
                    truncate(gg.finalgrade, 0),
                    ''
                ) 
            ) 
        )    as calificacionenviada,
       from_unixtime(mul.timeaccess, '%d-%m-%Y %H:%i:%s')   as ultimoaccesocurso,
       if(u.firstaccess=0,'-',from_unixtime(u.firstaccess, '%d-%m-%Y %H:%i:%s'))    as primeraccesocurso,
       if(u.lastaccess=0,'-',from_unixtime(u.lastaccess, '%d-%m-%Y %H:%i:%s'))     as ultimoaccesositio,
       com.name                                             as nombreempresa,
       com.rut                                              as rutempresa,
       com.contrato                                         as nroadherente,
       ifnull(
               (
                   select concat(iebl.responsablenombre, ' ',iebl.responsableapellido1, ' ', iebl.responsableapellido2)
                   from {inscripcion_elearning_back} iebl
                   where iebl.id_user_moodle = u.id and iebl.id_curso_moodle = c.id
                    order by iebl.id desc
                   limit 1
               ),
               (
                   CONCAT(
                           (
                               SELECT uid.data
                               from {user_info_data} AS uid
                                        JOIN {user_info_field} AS uif
                                             ON (uid.fieldid = uif.id AND uif.shortname = 'contactonombres')
                               WHERE uid.userid = u.id
                                 and uid.fieldid = uif.id
                               limit 1
                           )
                       , ' ',
                           (
                               SELECT uid.data
                               from {user_info_data} AS uid
                                        JOIN {user_info_field} AS uif
                                             ON (uid.fieldid = uif.id AND uif.shortname = 'contactoapellidopaterno')
                               WHERE uid.userid = u.id
                                 and uid.fieldid = uif.id
                               limit 1
                           )
                       )
                   )
           ) AS nombreencargado,
       ifnull(
           (
                select iebl.responsableemail
               from {inscripcion_elearning_back} iebl
               where iebl.id_user_moodle = u.id and iebl.id_curso_moodle = c.id
                order by iebl.id desc
               limit 1
           ),
           (
               SELECT uid.data
               from {user_info_data} AS uid
                        JOIN {user_info_field} AS uif ON (uid.fieldid = uif.id AND uif.shortname = 'contactoemail')
               WHERE uid.userid = u.id
                 and uid.fieldid = uif.id
               limit 1
           )
       ) AS mailencargado,
       ifnull(
            (
                select concat(iebl.responsablerut, '-',iebl.responsabledv)
               from {inscripcion_elearning_back} iebl
               where iebl.id_user_moodle = u.id and iebl.id_curso_moodle = c.id
                order by iebl.id desc
               limit 1
            ),
            (
                SELECT uid.data
               FROM {user_info_data} AS uid
                        JOIN {user_info_field} AS uif ON (uid.fieldid = uif.id AND uif.shortname = 'cintactoiddoc')
               WHERE uid.userid = u.id
                 and uid.fieldid = uif.id
               limit 1
            )
       ) AS rutencargado";
    }

    public static function get_from_sql()
    {
        return "{user} u
        join {user_enrolments} mue on mue.userid = u.id
        join {enrol} e on e.id = mue.enrolid
        join {course} c on c.id = e.courseid
        join {company_users} mcu on mcu.userid = u.id
        left join {company} com on mcu.companyid = com.id
        left join {grade_items} gi on gi.courseid = c.id
        left join {grade_grades} gg on gg.itemid = gi.id and gg.userid = u.id
        left join {course_completions} cc on cc.course = c.id and cc.userid = u.id
        left join {user_lastaccess} mul on u.id = mul.userid and mul.courseid = c.id";
    }

    /**
     * @param $params
     * @return string
     * @throws dml_exception
     */
    public static function get_where_sql($params)
    {
        global $USER;
        $companies = self::get_companies_from_userid($USER->id);

        if(!empty($params['company'])) {
            $companies = array_filter($companies, function($company) use ($params) {
                return $company->id == $params['company'];
            });
        }

        $companies = array_map(function ($company) {
            return $company->id;
        }, $companies);

        if(!empty($companies)) {
            $companyids = "'".implode("','", $companies)."'";
        } else {
            // se establece un valor fuera de rango para que el sql no se rompa si no tiene empresas asignadas
            $companyids = "'-'";
        }

        // Get migration date from settings or use default.
        $config = get_config('local_mutualreport');
        $hour = !empty($config->migration_hour) ? (int)$config->migration_hour : 23;
        $minute = !empty($config->migration_minute) ? (int)$config->migration_minute : 59;
        $second = !empty($config->migration_second) ? (int)$config->migration_second : 59;
        $month = !empty($config->migration_month) ? (int)$config->migration_month : 9;
        $day = !empty($config->migration_day) ? (int)$config->migration_day : 17;
        $year = !empty($config->migration_year) ? (int)$config->migration_year : 2025;
        $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

        // Allow site admin to override via URL parameter.
        $customtimestamp = optional_param('customtimestamp', 0, PARAM_INT);
        if ($customtimestamp > 0 && is_siteadmin()) {
            $timestamp = $customtimestamp;
        }

        $sql = sprintf("gi.itemtype = 'course'
        and mue.timecreated >= %d
        and mue.timecreated > %d and mue.timecreated < %d
        and mcu.companyid
          in (
              %s
            )", $timestamp, $params['timefrom'], $params['timeto'], $companyids);

        if (!empty($params['firstletter'])) {
            $firstletterlower = strtolower($params["firstletter"]);
            $sql .= sprintf(" and (u.firstname like '%s%%' or u.firstname like '%s%%')", $params['firstletter'], $firstletterlower);
        }

        if (!empty($params['lastletter'])) {
            $lastletterlower = strtolower($params["lastletter"]);
            $sql .= sprintf(" and (u.lastname like '%s%%' or u.lastname like '%s%%')", $params['lastletter'], $lastletterlower);
        }

        if (!empty($params['rut'])) {
            $rut = strtolower($params["rut"]);
            $sql .= sprintf(" and u.username like '%%%s%%'", $rut);
        }

        if (!empty($params['rut_company'])) {
            $rutCompany = strtolower($params["rut_company"]);
            $sql .= sprintf(" and com.rut like '%%%s%%'", $rutCompany);
        }

        if (!empty($params['adherente'])) {
            $adherente = strtolower($params["adherente"]);
            $sql .= sprintf(" and com.contrato = '%s'", $adherente);
        }


        if (!empty($params['course'])) {
            $sql .= sprintf(" and c.id = %d", $params['course']);
        }

        return $sql;
    }

    /**
     * @param $userid
     * @return array
     * @throws dml_exception
     */
    public static function get_companies_from_userid($userid)
    {
        global $SESSION, $DB;

        if (is_siteadmin()){
            $sql = 'select id, name from {company} order by name asc';
            $SESSION->companies = $DB->get_records_sql($sql);
        }else{

            $sql = sprintf('select distinct hc.companyid
                    from {holding_companies} hc
                            join {holding_users} mhu on mhu.holdingid = hc.holdingid
                    where mhu.userid = %d
                    union
                    distinct
                    select distinct mcu2.companyid
                    from {company_users} mcu2
                    where mcu2.userid = %d', $userid, $userid);

            if (empty($SESSION->companies)) {
                /** @var moodle_database $DB */
                $companies =  $DB->get_records_sql($sql);
                if(!empty($companies)) {
                    $companies = array_map(function ($company) {
                        return $company->companyid;
                    }, $companies);
                    $sql = sprintf('select * from {company} where id in ( %s )', implode(",", $companies));
                    $SESSION->companies = $DB->get_records_sql($sql);
                } else {
                    $SESSION->companies =[];
                }

            }
        }

        return $SESSION->companies;
    }

    /**
     * Get companies from user ID with option to include all companies.
     *
     * @param int $userid The user ID.
     * @param bool $includeall Whether to include all companies in the result.
     * @return array An associative array with the company ID as the key and the company name as the value.
     */
    public static function get_companies_from_userid_options($userid, $includeall = false) {
        global $DB;

        $companies = [];

        if (is_siteadmin()) {
            // Los administradores pueden ver todas las empresas.
            $companies = $DB->get_records_menu('company', null, 'name ASC', 'id, name');
        } else {
            // Para otros usuarios, obtenemos las empresas a las que tienen acceso.
            $sql = "SELECT DISTINCT c.id, c.name
                      FROM {company} c
                      JOIN (
                          -- Empresas a través de holdings.
                          SELECT hc.companyid
                            FROM {holding_companies} hc
                            JOIN {holding_users} hu ON hu.holdingid = hc.holdingid
                           WHERE hu.userid = :userid1
                          UNION
                          -- Empresas asignadas directamente.
                          SELECT cu.companyid
                            FROM {company_users} cu
                           WHERE cu.userid = :userid2
                      ) AS usercompanies ON usercompanies.companyid = c.id
                  ORDER BY c.name ASC";

            $params = [
                'userid1' => $userid,
                'userid2' => $userid
            ];
            $records = $DB->get_records_sql($sql, $params);

            if ($records) {
                foreach ($records as $record) {
                    $companies[$record->id] = $record->name;
                }
            }
        }

        if ($includeall) {
            $alloption = ['' => get_string('all')];
            $companies = $alloption + $companies;
        }

        return $companies;
    }

    /**
     * Search companies for a user, with search capabilities.
     *
     * @param int $userid The user ID.
     * @param string $query The search query.
     * @return array An array of company objects with id and name.
     * @throws dml_exception
     */
    public static function search_companies_for_user(int $userid, string $query = ''): array {
        global $DB;

        $params = [];
        $searchcondition = '';

        if (!empty($query)) {
            $searchsql = $DB->sql_like('c.name', ':name', false, false) .
                ' OR ' . $DB->sql_like('c.rut', ':rut', false, false) .
                ' OR ' . $DB->sql_like('c.contrato', ':contrato', false, false);
            $searchcondition = "AND ($searchsql)";
            $params['name'] = '%' . $query . '%';
            $params['rut'] = '%' . $query . '%';
            $params['contrato'] = '%' . $query . '%';
        }

        if (is_siteadmin($userid)) {
            $sql = "SELECT c.id, c.name
                      FROM {company} c
                     WHERE 1=1 $searchcondition
                  ORDER BY c.name ASC";
        } else {
            $sql = "SELECT DISTINCT c.id, c.name
                      FROM {company} c
                      JOIN (
                          -- Empresas a través de holdings.
                          SELECT hc.companyid
                            FROM {holding_companies} hc
                            JOIN {holding_users} hu ON hu.holdingid = hc.holdingid
                           WHERE hu.userid = :userid1
                          UNION
                          -- Empresas asignadas directamente.
                          SELECT cu.companyid
                            FROM {company_users} cu
                           WHERE cu.userid = :userid2
                      ) AS usercompanies ON usercompanies.companyid = c.id
                     WHERE 1=1 $searchcondition
                  ORDER BY c.name ASC";

            $params['userid1'] = $userid;
            $params['userid2'] = $userid;
        }

        $companies = $DB->get_records_sql($sql, $params);

        return array_values($companies);
    }

    /**
     * Get child classes in a component matching the provided namespace.
     *
     * It checks that the class exists.
     *
     * e.g. get_component_classes_in_namespace('mod_forum', 'event')
     *
     * @param string|null $component A valid moodle component (frankenstyle) or null if searching all components
     * @param string $namespace Namespace from the component name or empty string if all $component classes.
     * @return array The full class name as key and the class path as value, empty array if $component is `null`
     * and $namespace is empty.
     */
    public static function get_child_classes($baseclass, $component = null, $namespace = '') {

        $childclasses = [];

        $classes = core_component::get_component_classes_in_namespace($component, $namespace);

        foreach ($classes as $classname => $classpath) {
            if (is_subclass_of($classname, $baseclass)) {
                $childclasses[] = $classname;
            }
        }

        return $childclasses;

    }

    /**
     * Extracts and returns the plugin names from a list of fully qualified class names.
     *
     * This function takes an array of fully qualified class names, splits each class name
     * by the namespace separator, and collects the first component of each split, which is
     * assumed to be the plugin name.
     *
     * @param array $classes An array of fully qualified class names.
     * @return array An array of plugin names extracted from the class names.
     */
    public static function get_pluginnames_from_classes($classes) {
        $names = array();
        foreach ($classes as $class) {
            $partes = explode('\\', $class);
            $names[] = $partes[0];
        }
        return $names;
    }

    /**
     * Check if a report should be visible for the current user based on settings.
     *
     * @param string $reportname The machine name of the report.
     * @return bool
     */
    public static function is_report_visible(string $reportname): bool {
        global $USER;

        $config = get_config('local_mutualreport');
        $visibilitysetting = 'visibility_' . $reportname;
        $usersetting = 'visibility_users_' . $reportname;

        $visibility = $config->$visibilitysetting ?? 'disabled';

        switch ($visibility) {
            case 'everyone':
                return true;

            case 'admins':
                return is_siteadmin();

            case 'specific':
                if (empty($config->$usersetting)) {
                    return false;
                }
                $allowedusers = array_map('trim', explode(',', $config->$usersetting));
                return in_array($USER->id, $allowedusers);

            case 'disabled':
            default:
                return false;
        }
    }

    /**
     * Gets the URL of the first report visible to the current user.
     *
     * @return moodle_url|null The URL of the first visible report, or null if none are visible.
     */
    public static function get_first_visible_report_url(): ?moodle_url {
        $baseclass = \local_mutualreport\report\report_base::class;
        $component = 'local_mutualreport';
        $namespace = 'report';
        $reportclasses = self::get_child_classes($baseclass, $component, $namespace);

        $allreports = [];
        foreach ($reportclasses as $fullclassname) {
            $allreports[] = new $fullclassname();
        }

        // Sort reports by the defined sort order.
        $config = get_config('local_mutualreport');
        usort($allreports, function($a, $b) use ($config) {
            $namea = 'sort_order_' . $a->get_name();
            $nameb = 'sort_order_' . $b->get_name();
            $ordera = $config->$namea ?? 99;
            $orderb = $config->$nameb ?? 99;

            return $ordera <=> $orderb;
        });

        /** @var \local_mutualreport\report\report_base $report */
        foreach ($allreports as $report) {
            if (self::is_report_visible($report->get_name())) {
                // Found the first visible report, return its URL.
                return $report->get_url();
            }
        }

        // No reports are visible to this user.
        return null;
    }
}
