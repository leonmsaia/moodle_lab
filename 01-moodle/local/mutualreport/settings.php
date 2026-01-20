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
 * Plugin administration pages are defined here.
 *
 * @package     local_mutualreport
 * @copyright   2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_mutualreport_settings', get_string('pluginname', 'local_mutualreport'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading('local_mutualreport_migrationdate_heading', get_string('migrationdate_heading', 'local_mutualreport'), ''));

    $settings->add(new admin_setting_configtext(
        'local_mutualreport/migration_year',
        get_string('migration_year', 'local_mutualreport'),
        get_string('migration_year_desc', 'local_mutualreport'),
        '2025',
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mutualreport/migration_month',
        get_string('migration_month', 'local_mutualreport'),
        get_string('migration_month_desc', 'local_mutualreport'),
        '1',
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mutualreport/migration_day',
        get_string('migration_day', 'local_mutualreport'),
        get_string('migration_day_desc', 'local_mutualreport'),
        '1',
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mutualreport/migration_hour',
        get_string('migration_hour', 'local_mutualreport'),
        get_string('migration_hour_desc', 'local_mutualreport'),
        '0',
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mutualreport/migration_minute',
        get_string('migration_minute', 'local_mutualreport'),
        get_string('migration_minute_desc', 'local_mutualreport'),
        '0',
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mutualreport/migration_second',
        get_string('migration_second', 'local_mutualreport'),
        get_string('migration_second_desc', 'local_mutualreport'),
        '0',
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading(
        'local_mutualreport_migrationdate35_heading',
        get_string('migrationdate35_heading', 'local_mutualreport'),
        '')
    );

    $settings->add(new admin_setting_configtext(
        'local_mutualreport/migration_year35',
        get_string('migration_year35', 'local_mutualreport'),
        get_string('migration_year_desc', 'local_mutualreport'),
        '2024',
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mutualreport/migration_month35',
        get_string('migration_month35', 'local_mutualreport'),
        get_string('migration_month_desc', 'local_mutualreport'),
        '12',
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mutualreport/migration_day35',
        get_string('migration_day35', 'local_mutualreport'),
        get_string('migration_day_desc', 'local_mutualreport'),
        '31',
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mutualreport/migration_hour35',
        get_string('migration_hour35', 'local_mutualreport'),
        get_string('migration_hour_desc', 'local_mutualreport'),
        '23',
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mutualreport/migration_minute35',
        get_string('migration_minute35', 'local_mutualreport'),
        get_string('migration_minute_desc', 'local_mutualreport'),
        '59',
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_mutualreport/migration_second35',
        get_string('migration_second35', 'local_mutualreport'),
        get_string('migration_second_desc', 'local_mutualreport'),
        '59',
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading('local_mutualreport_datefilter_heading', get_string('datefilter_heading', 'local_mutualreport'), get_string('datefilter_heading_desc', 'local_mutualreport')));

    $settings->add(new admin_setting_configcheckbox(
        'local_mutualreport/enable_datefilter_admin',
        get_string('enable_datefilter_admin', 'local_mutualreport'),
        get_string('enable_datefilter_admin_desc', 'local_mutualreport'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_mutualreport/enable_datefilter_user',
        get_string('enable_datefilter_user', 'local_mutualreport'),
        get_string('enable_datefilter_user_desc', 'local_mutualreport'),
        1
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_mutualreport/excluded_users_datefilter',
        get_string('excluded_users_datefilter', 'local_mutualreport'),
        get_string('excluded_users_datefilter_desc', 'local_mutualreport'),
        ''
    ));

    $settings->add(new admin_setting_heading('local_mutualreport_default_dates_v2_heading', get_string('default_dates_v2_heading', 'local_mutualreport'), get_string('default_dates_v2_heading_desc', 'local_mutualreport')));

    $settings->add(new admin_setting_configtext(
        'local_mutualreport/default_date_from_v2',
        get_string('default_date_from_v2', 'local_mutualreport'),
        get_string('default_date_from_v2_desc', 'local_mutualreport'),
        '30',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_mutualreport/default_date_to_v2',
        get_string('default_date_to_v2', 'local_mutualreport'),
        get_string('default_date_to_v2_desc', 'local_mutualreport'),
        '1',
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading('local_mutualreport_default_dates_35_heading', get_string('default_dates_35_heading', 'local_mutualreport'), get_string('default_dates_35_heading_desc', 'local_mutualreport')));

    $settings->add(new admin_setting_configtext(
        'local_mutualreport/default_date_from_35',
        get_string('default_date_from_35', 'local_mutualreport'),
        get_string('default_date_from_35_desc', 'local_mutualreport'),
        '30',
        PARAM_INT
    ));

    // Default single company setting.
    $settings->add(new admin_setting_configcheckbox(
        'local_mutualreport/default_single_company',
        get_string('default_single_company', 'local_mutualreport'),
        get_string('default_single_company_desc', 'local_mutualreport'),
        0,
        1,
        0
    ));

    $settings->add(new admin_setting_heading(
        'local_mutualreport_externaldb_heading',
        get_string('externaldb_heading', 'local_mutualreport'),
        ''
    ));
    $settings->add(new admin_setting_configtext(
        'local_mutualreport/external_db_mnethostid',
        get_string('external_db_mnethostid', 'local_mutualreport'),
        get_string('external_db_mnethostid_desc', 'local_mutualreport'),
        '1',
        PARAM_INT
    ));

    // --- Report Visibility Settings ---

    $heading = new admin_setting_heading(
        'local_mutualreport_reportvisibility_heading',
        get_string('reportvisibility_heading', 'local_mutualreport'),
        get_string('reportvisibility_heading_desc', 'local_mutualreport')
    );
    $settings->add($heading);

    // Automatically discover and create settings for each report.
    $baseclass = \local_mutualreport\report\report_base::class;
    $component = 'local_mutualreport';
    $namespace = 'report';
    $reportclasses = \local_mutualreport\utils::get_child_classes($baseclass, $component, $namespace);

    // Instantiate and sort reports based on their configured sort order to display settings in order.
    $config = get_config('local_mutualreport');
    $allreports = [];
    foreach ($reportclasses as $fullclassname) {
        $allreports[] = new $fullclassname();
    }
    usort($allreports, function($a, $b) use ($config) {
        $namea = 'sort_order_' . $a->get_name();
        $nameb = 'sort_order_' . $b->get_name();
        $ordera = $config->$namea ?? 99;
        $orderb = $config->$nameb ?? 99;
        return $ordera <=> $orderb;
    });

    $visibilityoptions = [
        'everyone' => get_string('visibility_everyone', 'local_mutualreport'),
        'admins' => get_string('visibility_admins', 'local_mutualreport'),
        'specific' => get_string('visibility_specific', 'local_mutualreport'),
        'disabled' => get_string('visibility_disabled', 'local_mutualreport'),
    ];

    /** @var \local_mutualreport\report\report_base $report */
    foreach ($allreports as $report) {
        $reportname = $report->get_name();
        $reporttitle = $report->get_title();

        $settingnamesortorder = 'local_mutualreport/sort_order_' . $reportname;
        $settingnamevisibility = 'local_mutualreport/visibility_' . $reportname;
        $settingnameusers = 'local_mutualreport/visibility_users_' . $reportname;

        $setting = new admin_setting_configtext(
            $settingnamesortorder,
            get_string('report_sort_order_label', 'local_mutualreport', $reporttitle),
            get_string('report_sort_order_label_desc', 'local_mutualreport'),
            99, // Default value.
            PARAM_INT
        );
        $settings->add($setting);

        $setting = new admin_setting_configselect(
            $settingnamevisibility,
            get_string('report_visibility_label', 'local_mutualreport', $reporttitle),
            get_string('report_visibility_label_desc', 'local_mutualreport', $reporttitle),
            'everyone', // Default value.
            $visibilityoptions
        );
        $settings->add($setting);

        $setting = new admin_setting_configtextarea(
            $settingnameusers,
            get_string('report_visibility_users', 'local_mutualreport', $reporttitle),
            get_string('report_visibility_users_desc', 'local_mutualreport'),
            ''
        );
        $settings->add($setting);
        $settings->hide_if($settingnameusers, $settingnamevisibility, 'neq', 'specific');
    }

}
