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
 * Plugin capabilities are defined here.
 *
 * @package     local_eabcourses
 * @category    courses
 * @copyright   2020 Ysrrael Sánchez <ysrrael@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//defined('MOODLE_INTERNAL') || die();
require_once ('../../config.php');
global $USER, $DB, $CFG, $OUTPUT,$PAGE;
require_once($CFG->dirroot.'/local/eabcourses/locallib.php');


$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/eabcourses/view.php'));
echo $OUTPUT->header();

/*
require_login();
*/ 

$listusers = get_courses_elearning();
echo $listusers;

echo $OUTPUT->footer();


