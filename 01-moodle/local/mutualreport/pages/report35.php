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
 * Index Report Page
 *
 * @package    local_mutualreport
 * @copyright  2025 e-ABC <contacto@e-abclearning..com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__, 4) . '/config.php');

use local_mutualreport\reportbuilder\local\systemreports\elsa_with_external_db35 as report;
use local_mutualreport\url;
use local_mutualreport\utils35;

// Get context.
$context = context_system::instance();

// Capabilities.
require_login();
require_capability('local/mutualreport:view', $context);

// Variables.
global $PAGE, $OUTPUT;

$report = null;
$utils = new utils35();
$isvalid = $utils->validate_connection();
$config = get_config('local_mutualreport');

// Get migration date from settings to use as default "to" date.
$hour = !empty($config->migration_hour) ? (int)$config->migration_hour : 23;
$minute = !empty($config->migration_minute) ? (int)$config->migration_minute : 59;
$second = !empty($config->migration_second) ? (int)$config->migration_second : 59;
$month = !empty($config->migration_month) ? (int)$config->migration_month : 9;
$day = !empty($config->migration_day) ? (int)$config->migration_day : 17;
$year = !empty($config->migration_year) ? (int)$config->migration_year : 2025;
$migrationtimestamp = mktime($hour, $minute, $second, $month, $day, $year);


if ($isvalid) {
    // Create report.
    /** @var \local_mutualreport\reportbuilder\local\systemreports\elsa_with_external_db35 $report */
    $report = \core_reportbuilder\system_report_factory::create(
        report::class, $context, 'local_mutualreport', 'index', 0,
        [
            'customtimestamp' => optional_param('customtimestamp', 0, PARAM_INT),
        ]
    );

    // Check if the user has any filter values stored.
    $userfilters = $report->get_filter_values();
    $defaultfilters = [];

    // If no date filter is set by the user, apply the default date range.
    if (
            empty($userfilters['enrolment:timecreated_from']) &&
            empty($userfilters['enrolment:timecreated_to'])
        ) {
        // Get default date range from settings or use fallback.
        $daysfrom = !empty($config->default_date_from_35) ? (int)$config->default_date_from_35 : 30;

        $defaultto = $migrationtimestamp;
        $defaultfrom = $migrationtimestamp - ($daysfrom * DAYSECS);

        $defaultfilters['enrolment:timecreated_operator'] = \core_reportbuilder\local\filters\date::DATE_RANGE;
        $defaultfilters['enrolment:timecreated_from'] = $defaultfrom;
        $defaultfilters['enrolment:timecreated_to'] = $defaultto;
    }

    // If single company setting is enabled, pre-select company if user has only one.
    if (
            !empty($config->default_single_company) &&
            !is_siteadmin($USER) &&
            empty($userfilters['company:companyselector35_values'])
        ) {
        $allowedcompanies = $utils->get_companies_from_username_options($USER->username);
        if (count($allowedcompanies) === 1) {
            $companyid = key($allowedcompanies);
            $defaultfilters['company:companyselector35_values'] = [$companyid];
        }
    }

    if (!empty($defaultfilters)) {
        $report->set_filter_values(array_merge($userfilters, $defaultfilters));
    }
}


// Page settings.
$url = url::view_report_elsa_with_external_db35();
$pagetitle = get_string('report_elsa_35', 'local_mutualreport');
$pageheading = get_string('report_elsa_35_heading', 'local_mutualreport');

$descdata = new \stdClass();
$descdata->date = userdate($migrationtimestamp);
$descdata->url = url::view_report_elsa_v2();
$descdata->navlink = get_string(
    'report_navigation_to_current',
    'local_mutualreport',
    $descdata
);
$description = get_string(
    'report_instructions_text_elsa_consolidado_v35', 'local_mutualreport', $descdata
);

/** @var moodle_page $PAGE */
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagetype('local_mutualreport');
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout('report');

// Display report.
/** @var \local_mutualreport\output\mutualreport_renderer $renderer */
$renderer = $PAGE->get_renderer('local_mutualreport', 'mutualreport');
echo $renderer->header();
$actionbar = new local_mutualreport\output\elsa_action_bar(
    $context,
    $url,
    $pagetitle,
    $pageheading,
    ['actionbaricon' => 'fa-archive']
);
echo $renderer->render($actionbar);
echo $description;
if ($isvalid && $report) {
    echo $report->output_with_external();
}
echo $renderer->footer();
