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
 * Settings used by the eabctiles course format
 *
 * @package local_rating_item
 * @copyright  2020 José Salgado jose@e-abclearning.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 * */
require_once '../../config.php';

/** @var core_renderer $OUTPUT */
//require_once("$CFG->libdir/grade/grade_item.php");

$courseid = optional_param('courseid', null, PARAM_INT);
$thisurl = new moodle_url('/local/rating_item/view_rating_item.php');
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course, true);

// The rest of your code goes below this.
$context = context_course::instance($courseid);
$PAGE->set_url($thisurl);
$PAGE->set_title(get_string('pluginname', 'local_rating_item'));
$PAGE->set_heading(get_string('pluginname', 'local_rating_item'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('embedded');


try {

    $item1 = get_config('local_rating_item', 'item1_course'. $courseid);
    $item2 = get_config('local_rating_item', 'item2_course'. $courseid);
            
    if(empty($item1) && empty($item2)){
        redirect(new moodle_url('/local/rating_item/manage_rating_item.php?courseid='.$courseid),get_string('notconfigureitems', 'local_rating_item'));
    }        
    $outputrating = new \local_rating_item\form\user_form(null, array('courseid' => $courseid));
    $outputrating_ratint_body = new \local_rating_item\form\grade_item_form(null, array('courseid' => $courseid));

    echo $OUTPUT->header();
    
    ob_start();
    echo $outputrating->display();
    $outputratinghtml = ob_get_contents();
    ob_end_clean();
    ob_start();
    echo $outputrating_ratint_body->display();
    $outputrating_ratint_bodyhtml = ob_get_contents();
    ob_end_clean();
    echo $OUTPUT->render_from_template('local_rating_item/rating_item_view_head', array('outputratinghtml' => $outputratinghtml));
    if (has_capability('format/eabctiles:closegroup',  context_course::instance($courseid), $USER->id)) {
        echo $OUTPUT->single_button(new moodle_url('/course/format/eabctiles/closeactivity.php', array('id' => $courseid)), get_string('endorsuspendactivity', 'format_eabctiles'), 'get', ['target' => '_blank']);
    }
    echo $OUTPUT->render_from_template('local_rating_item/rating_item_view_body', array('outputrating_ratint_bodyhtml' => $outputrating_ratint_bodyhtml));
    
    if ($outputrating_ratint_body->is_cancelled()) {
        
    } else if ($get_data = $outputrating_ratint_body->get_data()) {
        echo local_rating_item\utils\rating_item_utils::save_rating($get_data);
    } 
    echo $OUTPUT->footer();
} catch (coding_exception $e) {
    throw new moodle_exception("errormsg", "local_rating_item", '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception("errormsg", "local_rating_item", '', $e->getMessage(), $e->debuginfo);
}

