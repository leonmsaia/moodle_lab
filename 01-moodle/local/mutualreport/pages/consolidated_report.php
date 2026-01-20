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
 * Consolidated Report Page
 *
 * @package    local_mutualreport
 * @copyright  2025 e-ABC <contacto@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__, 4) . '/config.php');

use local_mutualreport\reportbuilder\local\systemreports\elsa_consolidado_v1 as report;
use local_mutualreport\url;

// Get context.
$context = context_system::instance();

// Capabilities.
require_login();
require_capability('local/mutualreport:view', $context);

// Variables.
global $PAGE, $USER;

// Create report.
/** @var \local_mutualreport\reportbuilder\local\systemreports\elsa_consolidado_v1 $report */
$report = \core_reportbuilder\system_report_factory::create(
    report::class, $context, 'local_mutualreport', 'consolidated', 0,
    [
        'customtimestamp' => optional_param('customtimestamp', 0, PARAM_INT),
    ]
);

// Check if the user has any filter values stored.
$userfilters = $report->get_filter_values();
$config = get_config('local_mutualreport');
$defaultfilters = [];

// If no date filter is set by the user, apply the default date range.
if (
        empty($userfilters['enrolment:timecreated_gte_to_cutoff_date_from']) &&
        empty($userfilters['enrolment:timecreated_gte_to_cutoff_date_to'])
    ) {
    // Get default date range from settings or use fallback.
    $daysfrom = !empty($config->default_date_from_v2) ? (int)$config->default_date_from_v2 : 30;
    $daysto = isset($config->default_date_to_v2) ? (int)$config->default_date_to_v2 : 1;
    $defaultto = time() + ($daysto * DAYSECS);
    $defaultfrom = $defaultto - ($daysfrom * DAYSECS);
    $defaultfilters['enrolment:timecreated_gte_to_cutoff_date_operator'] = \core_reportbuilder\local\filters\date::DATE_RANGE;
    $defaultfilters['enrolment:timecreated_gte_to_cutoff_date_from'] = $defaultfrom;
    $defaultfilters['enrolment:timecreated_gte_to_cutoff_date_to'] = $defaultto;
}

if (empty($userfilters['company:companyselector2_value'])) {
    $defaultfilters['company:companyselector2_operator'] = \core_reportbuilder\local\filters\select::EQUAL_TO;
    $defaultfilters['company:companyselector2_value'] = '';
}

if (!empty($defaultfilters)) {
    $report->set_filter_values(array_merge($userfilters, $defaultfilters));
}

// Page settings.
$url = url::view_report_elsa_consolidado_v1();
$pagetitle = get_string('report_elsa_consolidado_v1', 'local_mutualreport');
$pageheading = get_string('report_elsa_consolidado_v1_heading', 'local_mutualreport');

// Get migration date for the current report.
$hour = isset($config->migration_hour) ? (int)$config->migration_hour : 0;
$minute = isset($config->migration_minute) ? (int)$config->migration_minute : 0;
$second = isset($config->migration_second) ? (int)$config->migration_second : 0;
$month = !empty($config->migration_month) ? (int)$config->migration_month : 1;
$day = !empty($config->migration_day) ? (int)$config->migration_day : 1;
$year = !empty($config->migration_year) ? (int)$config->migration_year : 2025;
$currenttimestamp = mktime($hour, $minute, $second, $month, $day, $year);

// Get migration date for the historical (3.5) report to use in the navlink.
$hour35 = isset($config->migration_hour35) ? (int)$config->migration_hour35 : 23;
$minute35 = isset($config->migration_minute35) ? (int)$config->migration_minute35 : 59;
$second35 = isset($config->migration_second35) ? (int)$config->migration_second35 : 59;
$month35 = !empty($config->migration_month35) ? (int)$config->migration_month35 : 12;
$day35 = !empty($config->migration_day35) ? (int)$config->migration_day35 : 31;
$year35 = !empty($config->migration_year35) ? (int)$config->migration_year35 : 2024;
$historicaltimestamp = mktime($hour35, $minute35, $second35, $month35, $day35, $year35);

$descdata = new \stdClass();
$descdata->date = userdate($currenttimestamp);
$descdata->url = url::view_report_elsa_consolidado_v35();
$navlinkdata = new \stdClass();
$navlinkdata->date = userdate($historicaltimestamp);
$navlinkdata->url = $descdata->url;
$descdata->navlink = get_string('report_navigation_to_historical', 'local_mutualreport', $navlinkdata);
$description = get_string(
    'report_instructions_text_elsa_consolidado_v1', 'local_mutualreport', $descdata
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
    ['actionbaricon' => 'fa-file-text-o']
);
echo $renderer->render($actionbar);
echo $description;
echo $report->output_with_external();
echo $renderer->footer();
