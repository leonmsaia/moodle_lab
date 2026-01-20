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
 * Manage faq page
 *
 * @package    local_help
 * @copyright 2019 Osvaldo Arriola <osvaldo@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
include_once('../../config.php');

/** @var core_renderer $OUTPUT */
global $OUTPUT, $CFG;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$url = new moodle_url('/local/mutualnotifications/adminsettings.php');

require_once("$CFG->libdir/formslib.php");

require_login($SITE);
$courseid = optional_param('courseid', null, PARAM_INT);

class mutualnotifications_form extends moodleform {

    public $courseid;

    public function __construct($courseid) {
        $this->courseid = $courseid;
        parent::__construct();
    }

    //Add elements to form
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $enrolment = get_config('local_mutualnotifications', "enrolment" . $this->courseid);
        $enrolment = ($enrolment)? $enrolment : 0;
        
        $fiftypercentfromenrolment = get_config('local_mutualnotifications', "fiftypercentfromenrolment" . $this->courseid);
        $fiftypercentfromenrolment = ($fiftypercentfromenrolment)? $fiftypercentfromenrolment : 0;
        
        $seventyfivepercentfromenrolment = get_config('local_mutualnotifications', "seventyfivepercentfromenrolment" . $this->courseid);
        $seventyfivepercentfromenrolment = ($seventyfivepercentfromenrolment)? $seventyfivepercentfromenrolment : 0;
        
        $finished = get_config('local_mutualnotifications', "finished" . $this->courseid);
        $finished = ($finished)? $finished : 0;
        
        $course = get_course($this->courseid);

        $mform->addElement('checkbox', 'enrolment', get_string('enrolment', 'local_mutualnotifications', $course));
        $mform->setDefault('enrolment', $enrolment); 
        
        $mform->addElement('checkbox', 'fiftypercentfromenrolment', get_string('fiftypercentfromenrolment', 'local_mutualnotifications', $course));
        $mform->setDefault('fiftypercentfromenrolment', $fiftypercentfromenrolment); 
        
        $mform->addElement('checkbox', 'seventyfivepercentfromenrolment', get_string('seventyfivepercentfromenrolment', 'local_mutualnotifications', $course));
        $mform->setDefault('seventyfivepercentfromenrolment', $seventyfivepercentfromenrolment); 
        
        $mform->addElement('checkbox', 'finished', get_string('finished', 'local_mutualnotifications', $course));
        $mform->setDefault('finished', $finished); 
        
        $mform->addElement('hidden', 'courseid', $this->courseid);

        $this->add_action_buttons(false);
    }

    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }

}

if (!$courseid) {
    $courseid = 2;
}

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
    $select = new single_select(new moodle_url($CFG->wwwroot . "/local/mutualnotifications/adminsettings.php"), 'courseid', $arraycourses, $courseid);
    $select->label = "Curso";
    echo $OUTPUT->render($select);

    $mform = new mutualnotifications_form($courseid);

    if ($mform->is_cancelled()) {
        
    } else if ($fromform = $mform->get_data()) {
        $renderer = $PAGE->get_renderer("local_mutualnotifications");
        $renderer->save_data_setting($fromform);
    }

    $mform->display();

    echo $OUTPUT->footer();
} catch (coding_exception $e) {
    throw new moodle_exception("errormsg", "local_help", '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception("errormsg", "local_help", '', $e->getMessage(), $e->debuginfo);
}

