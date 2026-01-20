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

namespace local_mutualreport\reportbuilder\local\systemreports;

use stdClass;
use context_system;
use lang_string;
use moodle_url;
use pix_icon;
use core_reportbuilder\local\report\action;
use core_reportbuilder\system_report;
use core_reportbuilder\local\helpers\database;
use local_mutualreport\reportbuilder\local\entities\access;
use local_mutualreport\reportbuilder\local\entities\user;
use local_mutualreport\reportbuilder\local\entities\course;
use local_mutualreport\reportbuilder\local\entities\employee;
use local_mutualreport\reportbuilder\local\entities\company;
use local_mutualreport\reportbuilder\local\entities\enrolment;
use local_mutualreport\reportbuilder\local\entities\completion;
use local_mutualreport\utils;

/**
 * Report elsa2
 *
 * @package    local_mutualreport
 * @copyright  2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class elsa_v2 extends system_report
{

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     *
     * @return void
     */
    protected function initialise(): void
    {

        global $USER, $DB, $CFG;

        // Main entity.
        $userentity = new user();
        $useralias = $userentity->get_table_alias('user');
        $coursealias = $userentity->get_table_alias('course');
        $this->set_main_table('user', $useralias);
        $this->add_entity($userentity);

        // Join enrolments entity.
        $enrolmententity = new enrolment();
        $userenrolalias = $enrolmententity->get_table_alias('user_enrolments');
        $enrolalias = $enrolmententity->get_table_alias('enrol');
        $enrolmententity->add_join(
            "JOIN {user_enrolments} {$userenrolalias} ON {$userenrolalias}.userid = {$useralias}.id"
        );
        $enrolmententity->add_join(
            "JOIN {enrol} {$enrolalias} ON {$enrolalias}.id = {$userenrolalias}.enrolid"
        );
        $this->add_entity($enrolmententity);

        // Join course entity.
        $courseentity = new course();
        $courseentity->set_table_alias('course', $coursealias);
        $courseentity->add_joins($enrolmententity->get_joins());
        $courseentity->add_join(
            "JOIN {course} {$coursealias} ON {$coursealias}.id = {$enrolalias}.courseid"
        );
        $this->add_entity($courseentity);

        // Join employee user entity.
        $employeeentity = new employee();
        $employeealias = $employeeentity->get_table_alias('company_users');
        $employeeentity->add_join(
            "JOIN {company_users} {$employeealias} ON {$employeealias}.userid = {$useralias}.id"
        );
        $this->add_entity($employeeentity);

        // Join company entity.
        $companyentity = new company();
        $companyalias = $companyentity->get_table_alias('company');
        $companyentity->add_joins($employeeentity->get_joins());
        $companyentity->add_join(
            "LEFT JOIN {company} {$companyalias} ON {$companyalias}.id = {$employeealias}.companyid"
        );
        $this->add_entity($companyentity);

        // Join completion entity.
        $completionentity = new completion();
        $completionalias = $completionentity->get_table_alias('course_completions');
        $gradeitemalias = $completionentity->get_table_alias('grade_items');
        $gradealias = $completionentity->get_table_alias('grade_grades');
        $completionentity->add_join(
            "LEFT JOIN {course_completions} {$completionalias}
                       ON {$completionalias}.userid = {$useralias}.id
                       AND {$completionalias}.course = {$coursealias}.id"
        );
        $completionentity->add_join("
                LEFT JOIN {grade_items} {$gradeitemalias}
                          ON {$coursealias}.id = {$gradeitemalias}.courseid
                             AND {$gradeitemalias}.itemtype = 'course'"
        );
        $completionentity->add_join(
            "
                LEFT JOIN {grade_grades} {$gradealias}
                          ON {$useralias}.id = {$gradealias}.userid
                             AND {$gradeitemalias}.id = {$gradealias}.itemid"
        );
        $completionentity->set_table_alias('user', $useralias);
        $completionentity->set_table_alias('course', $coursealias);
        $completionentity->set_table_alias('user_enrolments', $userenrolalias);
        $completionentity->set_table_alias('enrol', $enrolalias);
        $this->add_entity($completionentity);

        // Join last access entity.
        $lastaccessentity = new access();
        $lastaccessalias = $lastaccessentity->get_table_alias('user_lastaccess');
        $lastaccessentity->add_join(
            "LEFT JOIN {user_lastaccess} {$lastaccessalias}
                       ON {$lastaccessalias}.userid = {$useralias}.id
                       AND {$lastaccessalias}.courseid = {$coursealias}.id"
        );
        $lastaccessentity->set_table_alias('user', $useralias);
        $this->add_entity($lastaccessentity);

        // Any columns required by actions should be defined here to ensure they're always available.
        $basefields = [];
        $basefields[] = "{$useralias}.username AS username";
        $basefields[] = "{$coursealias}.shortname AS courseshortname";
        $this->add_base_fields(implode(',', $basefields));

        // Add a base condition to all queries on this report to use the mnethostid index.
        $mnetparam = database::generate_param_name('mnethostid');
        $this->add_base_condition_sql(
            "{$useralias}.mnethostid = :{$mnetparam}",
            [$mnetparam => $CFG->mnet_localhost_id]
        );

        // Add date condition based on settings.
        $config = get_config('local_mutualreport');

        $excludedusersraw = !empty($config->excluded_users_datefilter) ? $config->excluded_users_datefilter : '';
        $excludedusers = array_map('trim', explode(',', $excludedusersraw));
        $isexcluded = in_array($USER->id, $excludedusers);

        $applyfilter = (is_siteadmin() && !empty($config->enable_datefilter_admin)) ||
                       (!is_siteadmin() && !empty($config->enable_datefilter_user));

        $applyfilter = $applyfilter && !$isexcluded;

        if ($applyfilter) {
            // Get migration date from settings or use default.
            $hour = !empty($config->migration_hour) ? (int)$config->migration_hour : 23;
            $minute = !empty($config->migration_minute) ? (int)$config->migration_minute : 59;
            $second = !empty($config->migration_second) ? (int)$config->migration_second : 59;
            $month = !empty($config->migration_month) ? (int)$config->migration_month : 9;
            $day = !empty($config->migration_day) ? (int)$config->migration_day : 17;
            $year = !empty($config->migration_year) ? (int)$config->migration_year : 2025;
            $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

            $migrationdate = database::generate_param_name();
            $this->add_base_condition_sql(
                "{$userenrolalias}.timecreated >= :{$migrationdate}",
                [$migrationdate => $timestamp]
            );
        }

        // Add company filter based on user capabilities.
        if (!is_siteadmin($USER)) {
            $allowedcompanies = utils::get_companies_from_userid_options($USER->id);
            if (!empty($allowedcompanies)) {
                $companyids = array_keys($allowedcompanies);
                [$insql, $inparams] = $DB->get_in_or_equal($companyids, SQL_PARAMS_NAMED, 'companyid');
                [$insql, $inparams] = database::sql_replace_parameters(
                    $insql,
                    $inparams,
                    function (string $param) use (&$paramnamemap): string {
                        if (isset($paramnamemap[$param])) {
                            return $paramnamemap[$param];
                        }
                        $newname = database::generate_param_name("_" . $param);
                        $paramnamemap[$param] = $newname;
                        return $newname;
                    }
                );
                $this->add_base_condition_sql("{$employeealias}.companyid {$insql}", $inparams);
            } else {
                // If user has no companies, add a condition that will return no results.
                $withoutcompany = database::generate_param_name();
                $this->add_base_condition_sql(
                    "{$employeealias}.companyid = :{$withoutcompany}",
                    [$withoutcompany => 0]
                );
            }
        }

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        // Set if report can be downloaded.
        $downloadname = get_string('download_report_elsa_consolidado', 'local_mutualreport') . '_' . time();
        $this->set_downloadable(true, $downloadname);
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool
    {
        return has_capability('local/mutualreport:view', $this->get_context());
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier. If custom columns are needed just for this report, they can be defined here.
     *
     * @return void
     */
    public function add_columns(): void {
        $columns = [
            'user:eabcusernamewithlink',
            'user:firstname',
            'user:lastname',
            'course:coursefullnamewithlink',
            'enrolment:timecreated2',
            'completion:completadoenviado',
            'completion:calificacionenviada',
            'access:timeaccess2',
            'user:lastaccess2',
            'completion:estado',
            'company:name',
            'company:rut',
            'company:contrato',
            'user:nombreencargado',
            'user:mailencargado',
            'user:rutencargado',
        ];
        $this->add_columns_from_entities($columns);

        // Change names for columns.
        $this->get_column('course:coursefullnamewithlink')->set_title(new lang_string('course'));
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     *
     * @return void
     */
    protected function add_filters(): void {
        $filters = [
            'enrolment:timecreated_gte_to_cutoff_date',
            'company:companyselector2',
            'user:username',
            'course:courseselector',
            'company:rut',
            'company:contrato',
            'user:usernamelist',
            'course:courseshortnamelist',
            'company:companyrutlist',
            'company:companycontratolist',
        ];
        $this->add_filters_from_entities($filters);
        $this->get_filter('user:username')->set_header(new lang_string('rut', 'local_mutualreport'));
    }

    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     *
     * Note the use of ":id" placeholder which will be substituted according to actual values in the row
     */
    protected function add_actions(): void {}

    /**
     * Custom output the report
     *
     * @uses \core_reportbuilder\output\renderer::render_system_report()
     *
     * @return string
     */
    public function output_with_external(): string {
        global $PAGE;

        /** @var \local_mutualreport\output\consolidatedreport_renderer $renderer */
        $renderer = $PAGE->get_renderer('local_mutualreport', 'consolidatedreport');
        $report = new \local_mutualreport\output\eabc_consolidated_report(
            $this->get_report_persistent(),
            $this,
            $this->get_parameters()
        );

        return $renderer->render($report);
    }

    /**
     * Returns the specific output class needed for this report.
     *
     * @return string
     */
    public function get_output_class(): string {
        return \local_mutualreport\output\eabc_consolidated_report::class;
    }

    /**
     * Returns the name of the renderer needed for this report.
     *
     * @return string
     */
    public function get_renderer_name(): string {
        return 'consolidatedreport';
    }
}
