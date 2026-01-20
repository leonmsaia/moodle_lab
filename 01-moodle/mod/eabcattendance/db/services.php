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
 * Web service local plugin eabcattendance external functions and service definitions.
 *
 * @package    mod_eabcattendance
 * @copyright  2015 Caio Bressan Doneda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'mod_wseabcattendance_get_dates_sessions' => array(
        'classname'   => 'mod_wseabcattendance_external',
        'methodname'  => 'get_dates_sessions',
        'classpath'   => 'mod/eabcattendance/externallib.php',
        'description' => 'Method that retrieves dates of sessions of a teacher.',
        'type'        => 'read',
        'ajax'        => true
    ),
    'mod_wseabcattendance_get_courses_with_today_sessions' => array(
        'classname'   => 'mod_wseabcattendance_external',
        'methodname'  => 'get_courses_with_today_sessions',
        'classpath'   => 'mod/eabcattendance/externallib.php',
        'description' => 'Method that retrieves courses with today sessions of a teacher.',
        'type'        => 'read',
        'ajax'        => true
    ),
    'mod_wseabcattendance_get_session' => array(
        'classname'   => 'mod_wseabcattendance_external',
        'methodname'  => 'get_session',
        'classpath'   => 'mod/eabcattendance/externallib.php',
        'description' => 'Method that retrieves the session data',
        'type'        => 'read',
        'ajax'        => true

    ),
    'mod_wseabcattendance_update_user_status' => array(
        'classname'   => 'mod_wseabcattendance_external',
        'methodname'  => 'update_user_status',
        'classpath'   => 'mod/eabcattendance/externallib.php',
        'description' => 'Method that updates the user status in a session.',
        'type'        => 'write',
    ),
    'mod_wseabcattendance_add_attendance' => array(
        'classname'    => 'mod_eabcattendance\external\add_attendance',
        'methodname'   => 'add_attendance',
        'description'  => 'Add a new attendance.',
        'type'         => 'write',
    ),
    'mod_wseabcattendance_add_session' => array(
        'classname'    => 'mod_eabcattendance\external\add_session',
        'methodname'   => 'add_session',
        'description'  => 'Add a new session.',
        'type'         => 'write',
    ),
    'mod_wseabcattendance_create_schedules' => array(
        'classname'    => 'mod_eabcattendance\external\create_schedules',
        'methodname'   => 'create_schedules',
        'description'  => 'Crear planificacion.',
        'type'         => 'write',
    ),
    'mod_wseabcattendance_register_users' => array(
        'classname'    => 'mod_eabcattendance\external\register_users',
        'methodname'   => 'register_users',
        'description'  => 'Crear planificacion.',
        'type'         => 'write',
    ),
);


// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'Eabcattendance' => array(
        'functions' => array(
            'mod_wseabcattendance_get_courses_with_today_sessions',
            'mod_wseabcattendance_get_session',
            'mod_wseabcattendance_update_user_status',
            'mod_wseabcattendance_add_attendance',
            'mod_wseabcattendance_add_session',
            'mod_wseabcattendance_create_schedules',
            'mod_wseabcattendance_register_users',
            'mod_wseabcattendance_get_dates_sessions'
        ),
        'component' => 'mod_eabcattendance',
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'mod_eabcattendance_ws'
    )
);
