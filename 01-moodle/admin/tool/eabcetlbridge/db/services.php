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
 * Core external functions and service definitions.
 *
 * The functions and services defined on this file are
 * processed and registered into the Moodle DB after any
 * install or upgrade operation. All plugins support this.
 *
 * For more information, take a look to the documentation available:
 *     - Webservices API: {@link http://docs.moodle.org/dev/Web_services_API}
 *     - External API: {@link http://docs.moodle.org/dev/External_functions_API}
 *     - Upgrade API: {@link http://docs.moodle.org/dev/Upgrade_API}
 *
 * @package     tool_eabcetlbridge
 * @category    webservice
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'tool_eabcetlbridge_receive_user_grades' => array(
        'classname' => 'tool_eabcetlbridge\external\receive_user_grades',
        'methodname' => 'execute',
        'description' => 'e-ABC Receive User Grades',
        'type' => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'tool_eabcetlbridge_sync_user_grades' => array(
        'classname' => 'tool_eabcetlbridge\external\sync_user_grades',
        'methodname' => 'execute',
        'description' => 'e-ABC Sync User Grades',
        'type' => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'tool_eabcetlbridge_clean_overridden_grades' => array(
        'classname' => 'tool_eabcetlbridge\external\clean_overridden_grades',
        'methodname' => 'execute',
        'description' => 'e-ABC Clean Overridden Grades',
        'type' => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    )
    ,
    'tool_eabcetlbridge_clean_overridden_grades2' => array(
        'classname' => 'tool_eabcetlbridge\external\clean_overridden_grades',
        'methodname' => 'execute',
        'description' => 'e-ABC Clean Overridden Grades',
        'type' => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'tool_eabcetlbridge_regrade_final_grades' => array(
        'classname' => 'tool_eabcetlbridge\external\regrade_final_grades',
        'methodname' => 'execute',
        'description' => 'e-ABC Regrade final grades for a course.',
        'type' => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'tool_eabcetlbridge_get_pending_users_for_grade_sync' => array(
        'classname' => 'tool_eabcetlbridge\external\get_pending_users_for_grade_sync',
        'methodname' => 'execute',
        'description' => 'e-ABC Sync Pending Users For Grade',
        'type' => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'tool_eabcetlbridge_count_pending_users_for_grade_sync' => array(
        'classname' => 'tool_eabcetlbridge\external\count_pending_users_for_grade_sync',
        'methodname' => 'execute',
        'description' => 'e-ABC Count Pending Users For Grade Sync',
        'type' => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'tool_eabcetlbridge_fix_user_grade_inconsistencies' => array(
        'classname' => 'tool_eabcetlbridge\external\fix_user_grade_inconsistencies',
        'methodname' => 'execute',
        'description' => 'e-ABC Fix User Grade Inconsistencies',
        'type' => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'tool_eabcetlbridge_get_newly_enrolled_users' => array(
        'classname' => 'tool_eabcetlbridge\external\get_newly_enrolled_users',
        'methodname' => 'execute',
        'description' => 'e-ABC Get Newly Enrolled Users',
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'tool_eabcetlbridge_sync_user_completion' => array(
        'classname' => 'tool_eabcetlbridge\external\sync_user_completion',
        'methodname' => 'execute',
        'description' => 'e-ABC Sync User Completion',
        'type' => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'tool_eabcetlbridge_sync_user_data' => array(
        'classname' => 'tool_eabcetlbridge\external\sync_user_data',
        'methodname' => 'execute',
        'description' => 'e-ABC Sync User Data (Grades, Completion, etc.)',
        'type' => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
);
