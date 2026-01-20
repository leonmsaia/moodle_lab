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
 *
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Session Migration Tool';
$string['coursearch'] = 'Course Search';
$string['closedgroupsqueue'] = 'Queue: closed(3.5) groups open(4.5)';
$string['searchcourses'] = 'Search Courses in Moodle 3.5';
$string['search'] = 'Search';
$string['productoid'] = 'Product ID';
$string['shortname'] = 'Shortname';
$string['nocoursesfound'] = 'No courses found with the given criteria.';
$string['err_atleastonefield'] = 'Please provide a Product ID or a Shortname to search.';
$string['syncsessions'] = 'Synchronize closed sessions';
$string['syncsessionsconfirm'] = 'You are about to start the synchronization of closed sessions for this course. This process can be slow as it will validate each session individually. Do you want to continue?';
$string['syncsessionsstarted'] = 'Session synchronization has been initiated in the background.';
$string['logviewer'] = 'Action Log Viewer';
$string['id'] = 'ID';
$string['action'] = 'Action';
$string['targettype'] = 'Target Type';
$string['targetidentifier'] = 'Target Identifier';
$string['status'] = 'Status';
$string['message'] = 'Message';
$string['triggeredby'] = 'Triggered By';
$string['timecreated'] = 'Time Created';
$string['timemodified'] = 'Time Modified';
$string['nologsfound'] = 'No log entries found.';
$string['unknownuser'] = 'Unknown User';

$string['invaliddbconnection'] = 'Could not connect to the Moodle 3.5 database. Please check the connection settings.';
$string['sessionsearch'] = 'Session Search';
$string['searchsessions'] = 'Search Sessions in Moodle 3.5 by GUID';
$string['sessionguids'] = 'Session GUIDs (one per line)';
$string['err_sessionguidsrequired'] = 'Please provide at least one session GUID.';
$string['nosessionsfound'] = 'No sessions found with the given GUIDs.';
$string['migratesession'] = 'Migrate Session';
$string['migratesessionconfirm'] = 'You are about to start the migration of this session. Do you want to continue?';
$string['migratesessionstarted'] = 'Session migration has been initiated in the background.';

// Strings for duplicate search
$string['duplicatesearch'] = 'Duplicate Session Search';
$string['searchbygrupoid'] = 'Search by Group ID';
$string['searchbyidevento'] = 'Search by Event ID';
$string['searchbysessionguid'] = 'Search by Session GUID';
$string['searchtype'] = 'Search Type';
$string['searchvalue'] = 'Search Value';
$string['deletesession'] = 'Delete Session';
$string['deletesessionconfirm'] = 'Are you sure you want to delete the session with GUID: {$a}? This action cannot be undone.';
$string['deletesesionbackconfirm'] = 'Are you sure you want to delete the sesion_back record with ID: {$a}? This action cannot be undone.';
$string['deletesessionstarted'] = 'Session deletion has been initiated in the background.';
$string['details'] = 'Details';
$string['viewdetails'] = 'View details';

$string['migrationbydate'] = 'Migration by Date Range';
$string['startdate'] = 'Start date';
$string['enddate'] = 'End date';
$string['enddatebeforestartdate'] = 'The end date must be after the start date.';
$string['error_enddate_before_startdate'] = 'End date must be after start date';
$string['sessionsfound'] = 'sessions found';
$string['coursesaffected'] = 'Affected Courses (Shortname)';
$string['migratesessionsbydate'] = 'Migrate Sessions by Date';
$string['migratesessionsbydateconfirm'] = 'You are about to migrate {$a->count} sessions between {$a->startdate} and {$a->enddate}. This will affect the following courses: {$a->courses}. Do you want to continue?';
$string['migratesessionsbydatestarted'] = 'Session migration by date has been initiated in the background.';
$string['downloadcourses'] = 'Download Affected Courses (CSV)';

$string['migrationbysessions'] = 'Bulk session migration';
$string['migratesessions'] = 'Migrate sessions';
$string['migrationstarted'] = 'Bulk session migration has been started in the background.';
$string['confirmbulkmigration'] = 'You are about to start the migration for {$a} sessions. This will run in the background. Do you want to continue?';
$string['nosessionguids'] = 'You must enter at least one session GUID.';

// New strings added
$string['filter'] = 'Filter';
$string['migrationbycourseanddate'] = 'Migrate sessions by course and date range';
$string['migratesessionsbycourseanddatestarted'] = 'Session migration started';
$string['migratesessionsbycourseanddate'] = 'Migrate sessions (course + date range)';
$string['migratesessionsbycourseanddateconfirm'] = 'Are you sure you want to migrate {$a->count} sessions for course {$a->shortname} between {$a->startdate} and {$a->enddate}? Affected courses: {$a->courses}';
$string['downloadcourses'] = 'Download courses';
$string['sessionsfound'] = 'sessions found';
$string['coursesaffected'] = 'Courses affected';
$string['nocoursesfound'] = 'No courses found';

