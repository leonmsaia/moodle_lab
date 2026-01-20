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
 * Format tiles web services defintions
 *
 * @package   format_eabctiles
 * @category  event
 * @copyright 2018 David Watson {@link http://evolutioncode.uk}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'format_eabctiles_set_image' => [
        'classname' => 'format_eabctiles\external\external',
        'methodname' => 'set_image',
        'description' => 'Set tile icon (intended to be used from AJAX)',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => 'moodle/course:update',
    ],
    'format_eabctiles_log_tile_click' => [
        'classname' => 'format_eabctiles\external\external',
        'methodname' => 'log_tile_click',
        'description' => 'Trigger course view event for a section (for log) on section tile click',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => '', // Enrolment check, not capability - see format_eabctiles\external\external.
    ],
    'format_eabctiles_get_icon_set' => [
        'classname' => 'format_eabctiles\external\external',
        'methodname' => 'get_icon_set',
        'description' => 'Return the available icon set (for editing teacher)',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => 'moodle/course:update',
    ],
    'format_eabctiles_set_session_width' => [
        'classname' => 'format_eabctiles\external\external',
        'methodname' => 'set_session_width',
        'description' => 'Set session width of tiles window (so that tiles can be shown with correct width on page load',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => '', // Enrolment check, not capability - see format_eabctiles\external\external.
    ],
    'format_eabctiles_get_section_information' => [
        'classname' => 'format_eabctiles\external\external',
        'methodname' => 'get_section_information',
        'description' => 'Get information for a section including availability info to refresh tile info on progress',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => '', // Enrolment check, not capability - see format_eabctiles\external\external.
    ],
    'format_eabctiles_get_course_mod_info' => [
        'classname' => 'format_eabctiles\external\external',
        'methodname' => 'get_course_mod_info',
        'description' => 'Get information about a course module including type of a resource e.g. PDF or HTML',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => 'mod/[modulename]:view',
    ],
    'format_eabctiles_closeactivity' => [
        'classname'    => 'format_eabctiles\external\closeactivity',
        'methodname'   => 'closeactivity',
        'description'  => 'Cerrar actividad.',
        'type'         => 'read',
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax'          => true,
        'loginrequired' => false,
    ],
    'format_eabctiles_suspendactivity' => [
        'classname'    => 'format_eabctiles\external\suspendactivity',
        'methodname'   => 'suspendactivity',
        'description'  => 'Suspender actividad.',
        'type'         => 'read',
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax'          => true,
        'loginrequired' => false,
    ],
];