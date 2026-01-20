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
 * Test Execute all tasks.
 * Only for development purposes.
 *
 * @package   local_godeep
 * @copyright 2024 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Core variables used:
 * @var moodle_page $PAGE
 * @var core_renderer $OUTPUT
 * @var stdClass $COURSE
 */

require_once(dirname(__FILE__, 5) . '/config.php');

// Capabilities.
require_login(null, false);
require_capability('moodle/site:config', context_system::instance());

// Variables.
global $PAGE, $OUTPUT, $USER;

// Url of current page.
$baseurl = new moodle_url('/admin/tool/eabcetlbridge/pages/test_get_pending_users_for_grade_sync.php');

// Page settings.
$pagetitle = " Test";
$PAGE->set_context(context_system::instance());
$PAGE->set_url($baseurl);
$PAGE->set_pagetype('eabcetlbridge');
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout('report');

/**
 * Function used to handle mtrace by outputting the text to normal browser window.
 *
 * @param string $message Message to output
 * @param string $eol End of line character
 */
function tool_task_mtrace_wrapper($message, $eol) {
    echo s($message . $eol);
}

echo '<pre>';


use tool_eabcetlbridge\external\get_pending_users_for_grade_sync;

$result = get_pending_users_for_grade_sync::execute(
);

var_dump($result);exit;

echo '</pre>';
