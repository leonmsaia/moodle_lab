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
 * delete faq instance
 *
 * @package    local_help
 * @copyright 2019 Osvaldo Arriola <osvaldo@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use local_help\faq;

include_once('../../config.php');

/**
 * @var core_renderer $OUTPUT
 * @var moodle_page $PAGE
 */
global $OUTPUT, $CFG, $PAGE;

$id = optional_param('id', 0, PARAM_INT);

require_once($CFG->libdir . '/adminlib.php');
$renderer = $PAGE->get_renderer('local_help');
try {
    $comments = $renderer->get_comments($id);
    
    echo html_writer::start_tag("div", array("class" => "containert-comment"));
    foreach ($comments as $comment){
     $user = $renderer->get_user_comment($comment->userid);
        $data = new stdClass();
        $data->pix = $OUTPUT->user_picture($user, array('size'=>30));
        $data->name = $user->firstname . ' ' . $user->lastname;
        $data->timecomment = date("d/m/Y", $comment->timecreated);
        $data->comment = $comment->comment;
        echo $OUTPUT->render_from_template('local_help/comment_faq', $data);
    }
    echo html_writer::end_tag("div");
    
    
    require_once($CFG->dirroot . '/local/help/classes/form/faq_form_comments.php');
    $mform = new faq_form_comments($CFG->wwwroot . '/local/help/faqview.php', $id);

    $mform->add_action_buttons();
    $mform->display();
    
} catch (coding_exception $e) {
    throw new moodle_exception('errormsg', 'local_help', '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception('errormsg', 'local_help', '', $e->getMessage(), $e->debuginfo);
}


