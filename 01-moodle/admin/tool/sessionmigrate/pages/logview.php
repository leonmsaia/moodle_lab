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
 * Single log view page.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . './../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();
require_capability('tool/sessionmigrate:viewlog', context_system::instance());

$PAGE->set_url(new moodle_url('/admin/tool/sessionmigrate/pages/logview.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('logviewer', 'tool_sessionmigrate'));
$PAGE->set_heading(get_string('logviewer', 'tool_sessionmigrate'));

$renderer = $PAGE->get_renderer('tool_sessionmigrate');

echo $renderer->header();
echo $renderer->secondary_navigation();

global $DB;
$log = $DB->get_record('tool_sessionmigrate_log', ['id' => $id], '*', MUST_EXIST);

$detailshtml = '';
if (!empty($log->details)) {
    $decoded = json_decode($log->details, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $detailshtml = html_writer::tag('pre', s($pretty), ['style' => 'white-space:pre-wrap']);
    } else {
        $detailshtml = html_writer::tag('pre', s($log->details), ['style' => 'white-space:pre-wrap']);
    }
}

// Summary card.
$summary = html_writer::start_div('card mb-4');
$summary .= html_writer::tag('div', get_string('logviewer', 'tool_sessionmigrate') . ' #' . $log->id, ['class' => 'card-header']);
$summary .= html_writer::start_div('card-body');
$summary .= html_writer::start_tag('dl', ['class' => 'row mb-0']);

$summary .= html_writer::tag('dt', get_string('action', 'tool_sessionmigrate'), ['class' => 'col-sm-3 text-muted']);
$summary .= html_writer::tag('dd', s($log->action), ['class' => 'col-sm-9']);

$summary .= html_writer::tag('dt', get_string('targettype', 'tool_sessionmigrate'), ['class' => 'col-sm-3 text-muted']);
$summary .= html_writer::tag('dd', s($log->targettype), ['class' => 'col-sm-9']);

$summary .= html_writer::tag('dt', get_string('targetidentifier', 'tool_sessionmigrate'), ['class' => 'col-sm-3 text-muted']);
$summary .= html_writer::tag('dd', s($log->targetidentifier), ['class' => 'col-sm-9']);

$summary .= html_writer::tag('dt', get_string('status', 'tool_sessionmigrate'), ['class' => 'col-sm-3 text-muted']);
$summary .= html_writer::tag('dd', s($log->status), ['class' => 'col-sm-9']);

$summary .= html_writer::tag('dt', get_string('message', 'tool_sessionmigrate'), ['class' => 'col-sm-3 text-muted']);
$summary .= html_writer::tag('dd', format_text($log->message, FORMAT_PLAIN), ['class' => 'col-sm-9']);

$summary .= html_writer::tag('dt', get_string('triggeredby', 'tool_sessionmigrate'), ['class' => 'col-sm-3 text-muted']);
$summary .= html_writer::tag('dd', fullname(core_user::get_user($log->userid)), ['class' => 'col-sm-9']);

$summary .= html_writer::tag('dt', get_string('timecreated', 'tool_sessionmigrate'), ['class' => 'col-sm-3 text-muted']);
$summary .= html_writer::tag('dd', userdate($log->timecreated), ['class' => 'col-sm-9']);

$summary .= html_writer::tag('dt', get_string('timemodified', 'tool_sessionmigrate'), ['class' => 'col-sm-3 text-muted']);
$summary .= html_writer::tag('dd', userdate($log->timemodified), ['class' => 'col-sm-9']);

$summary .= html_writer::end_tag('dl');
$summary .= html_writer::end_div();
$summary .= html_writer::end_div();

echo $summary;

// Details card.
if (!empty($detailshtml)) {
    $detailscard = html_writer::start_div('card');
    $detailscard .= html_writer::tag('div', get_string('details', 'tool_sessionmigrate'), ['class' => 'card-header']);
    $detailscard .= html_writer::start_div('card-body');
    $detailscard .= $detailshtml; // pre-wrapped, full width container.
    $detailscard .= html_writer::end_div();
    $detailscard .= html_writer::end_div();
    echo $detailscard;
}

echo $renderer->footer();
