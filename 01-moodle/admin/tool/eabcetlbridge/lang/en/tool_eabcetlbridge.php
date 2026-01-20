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
 * Plugin strings are defined here.
 *
 * @package     tool_eabcetlbridge
 * @category    lang
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'e-ABC ETL Bridge';
$string['migrationorchestratortask'] = 'e-ABC Migration Orchestrator';
$string['populate_id_mapping_task'] = 'e-ABC Sync Master IDs';
$string['populate_id_mapping_batch_task'] = 'e-ABC Sync Master IDs Batch';
$string['populate_planner_batch_task'] = 'e-ABC Populate Planner Batch';
$string['get_external_grades_and_create_data_batch_task'] = 'e-ABC Get External Grades and Create Data Batch CSV';
$string['update_planners_status_task'] = 'e-ABC Update Planners Status';
$string['migrate_automatic_start_task'] = 'e-ABC Migration Automatic Start';
$string['mark_processed_users_task'] = 'e-ABC Mark Users Processed';
$string['register_users_in_a_file_task'] = 'e-ABC Register Users in a Batch File';
$string['clean_overridden_grades_task'] = 'e-ABC Clean Overridden Grades';
$string['view_logs'] = 'View logs';

// Status strings.
$string['status_disabled'] = 'Disabled';
$string['status_preview'] = 'Preview';
$string['status_pending'] = 'Pending';
$string['status_senttoqueue'] = 'Sent to queue';
$string['status_processing'] = 'Processing';
$string['status_completed'] = 'Completed';
$string['status_failed'] = 'Failed';
$string['type_manual'] = 'Manual';
$string['type_automated'] = 'Automated';
$string['planner_type_user'] = 'User';
$string['planner_type_course'] = 'Course';

// Form strings.
$string['form_strategy'] = 'Migration strategy';
$string['errornouserid'] = 'Could not find a user ID column in the CSV file. Expected a header like: {$a}.';

// Report builder.
$string['entity_config'] = '[eabcetlbridge] Configurations for migration strategy';
$string['entity_batch_file'] = '[eabcetlbridge] Migration batch files';
$string['entity_planner'] = '[eabcetlbridge] Planners';
$string['entity_adhoc_task'] = '[eabcetlbridge] Ad-hoc tasks';
$string['column_id'] = 'ID';
$string['column_name'] = 'Name';
$string['column_shortname'] = 'Short name';
$string['column_strategyclass'] = 'Strategy class';
$string['column_sourcequery'] = 'Source query';
$string['column_mapping'] = 'Mapping';
$string['column_isenabled'] = 'Is enabled?';
$string['column_lastruntime'] = 'Last runtime';
$string['column_usermodified'] = 'Modified by';
$string['column_timecreated'] = 'Created';
$string['column_timemodified'] = 'Modified';
$string['column_timestarted'] = 'Started';
$string['column_status'] = 'Status';
$string['column_component'] = 'Component';
$string['column_filearea'] = 'File area';
$string['column_filename'] = 'Filename';
$string['column_filepath'] = 'File path';
$string['column_type'] = 'Type';
$string['column_delimiter'] = 'Delimiter';
$string['column_encoding'] = 'Encoding';
$string['column_qtylines'] = 'Lines';
$string['column_qtyrecords'] = 'Records';
$string['column_qtyrecordsprocessed'] = 'Processed records';
$string['column_errormessages'] = 'Error messages';
$string['column_objective'] = 'Objective';
$string['column_itemidentifier'] = 'Item identifier';
$string['column_courseid'] = 'Course ID';
$string['column_batchfileid'] = 'Batch file ID';
$string['column_configid'] = 'Configuration ID';
$string['column_classname'] = 'Class name';
$string['column_nextruntime'] = 'Next runtime';
$string['column_faildelay'] = 'Fail delay';
$string['column_attemptsavailable'] = 'Attempts available';
$string['column_customdata'] = 'Custom data';
$string['column_pid'] = 'PID';
