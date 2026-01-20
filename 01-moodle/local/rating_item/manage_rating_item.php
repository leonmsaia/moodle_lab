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
require_once("$CFG->libdir/formslib.php");
require_once("$CFG->libdir/grade/grade_item.php");

$courseid = optional_param('courseid', null, PARAM_INT);
$thisurl = new moodle_url('/local/rating_item/manage_rating_item.php');
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course, true);

// The rest of your code goes below this.
$context = context_course::instance($courseid);
$PAGE->set_url($thisurl);
$PAGE->set_title(get_string('pluginname', 'local_rating_item'));
$PAGE->set_heading(get_string('pluginname', 'local_rating_item'));
$PAGE->set_context($context);

try {


    echo $OUTPUT->header();

    $arraycourses = array();
    $courses = get_courses("all", "c.sortorder ASC", "c.id, c.fullname");
    foreach ($courses as $course) {
        if ($course->id == 1) {
            
        } else {
            $arraycourses[$course->id] = $course->fullname;
        }
    }
    $select = new single_select(new moodle_url($CFG->wwwroot . "/local/rating_item/manage_rating_item.php"), 'courseid', $arraycourses, $courseid);
    $select->label = get_string('course');
    if (!empty($courseid)) {
        $select->disabled = true;
    }
    echo $OUTPUT->render($select);

    $item1 = get_config('local_rating_item', 'item1_course'.$courseid);
    $item2 = get_config('local_rating_item', 'item2_course'.$courseid);
    
    $mform = new \local_rating_item\form\manage_item_form($courseid, array('item1' => $item1 , 'item2' => $item2));
   
    if ($mform->is_cancelled()) {
        //Handle form cancel operation, if cancel button is present on form
    } else if ($data = $mform->get_data()) {
        set_config('item1_course'.$courseid, $data->item1, 'local_rating_item');
        set_config('item2_course'.$courseid, $data->item2, 'local_rating_item');
    }
    
    echo $mform->display();
    
    echo $OUTPUT->footer();
} catch (coding_exception $e) {
    throw new moodle_exception("errormsg", "local_rating_item", '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception("errormsg", "local_rating_item", '', $e->getMessage(), $e->debuginfo);
}

