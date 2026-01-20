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
 * Defines mobile handlers.
 *
 * @package   mod_eabcattendance
 * @copyright 2018 Dan Marsdenb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_eabcattendance' => [
        'handlers' => [
            'view' => [
                'displaydata' => [
                    'icon' => $CFG->wwwroot . '/mod/eabcattendance/pix/icon.png',
                    'class' => '',
                ],
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_view_activity',
                'styles' => [
                    'url' => '/mod/eabcattendance/mobilestyles.css',
                    'version' => 22
                ]
            ]
        ],
        'lang' => [ // Language strings that are used in all the handlers.
            ['pluginname', 'eabcattendance'],
            ['sessionscompleted', 'eabcattendance'],
            ['pointssessionscompleted', 'eabcattendance'],
            ['percentagesessionscompleted', 'eabcattendance'],
            ['sessionstotal', 'eabcattendance'],
            ['pointsallsessions', 'eabcattendance'],
            ['percentageallsessions', 'eabcattendance'],
            ['maxpossiblepoints', 'eabcattendance'],
            ['maxpossiblepercentage', 'eabcattendance'],
            ['submiteabcattendance', 'eabcattendance'],
            ['strftimeh', 'eabcattendance'],
            ['strftimehm', 'eabcattendance'],
            ['eabcattendancesuccess', 'eabcattendance'],
            ['eabcattendance_no_status', 'eabcattendance'],
            ['eabcattendance_already_submitted', 'eabcattendance'],
            ['somedisabledstatus', 'eabcattendance'],
            ['invalidstatus', 'eabcattendance'],
            ['preventsharederror', 'eabcattendance'],
            ['closed', 'eabcattendance'],
            ['subnetwrong', 'eabcattendance'],
            ['enterpassword', 'eabcattendance'],
            ['incorrectpasswordshort', 'eabcattendance'],
            ['eabcattendancesuccess', 'eabcattendance'],
            ['setallstatuses', 'eabcattendance']
        ],
    ]
];