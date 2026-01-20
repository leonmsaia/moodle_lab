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
 * Log viewer for the session migration tool.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . './../../../../config.php');

global $PAGE;

require_once($CFG->libdir.'/adminlib.php');
require_admin();

admin_externalpage_setup('sessionmigrate_log');

$PAGE->set_url(new moodle_url('/admin/tool/sessionmigrate/pages/log.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('logviewer', 'tool_sessionmigrate'));
$PAGE->set_heading(get_string('logviewer', 'tool_sessionmigrate'));

/** @var \tool_sessionmigrate\output\renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sessionmigrate');

echo $renderer->header();
echo $renderer->secondary_navigation();

echo $renderer->heading(get_string('logviewer', 'tool_sessionmigrate'));

$table = new tool_sessionmigrate\log\table('sessionmigrate-log-table');
$table->out(20, true);

echo $renderer->footer();
