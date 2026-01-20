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
include_once('../../config.php');

/**
 * @var core_renderer $OUTPUT
 * @var moodle_page $PAGE
 */
global $OUTPUT, $CFG, $PAGE;

require_once($CFG->libdir . '/adminlib.php');
require_once("$CFG->libdir/formslib.php");
require_once('lib.php');

class simplehtml_form extends moodleform {

    //Add elements to form
    public function definition() {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore! 

        $mform->addElement('text', 'username', "username"); // Add elements to your form
        $mform->setType('text', PARAM_NOTAGS);  
        
        $mform->addElement('text', 'courseid', "courseid"); // Add elements to your form
        $mform->setType('text', PARAM_NOTAGS); 
        
        $this->add_action_buttons();
    }

    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }

}

try {

    $html = "";

    $PAGE->set_url(new moodle_url('/local/cron/remove_his_user.php'));
    $PAGE->set_context(context_system::instance());

    echo $OUTPUT->header();

    $mform = new simplehtml_form();

//Form processing and displaying is done here
    if ($mform->is_cancelled()) {
        //Handle form cancel operation, if cancel button is present on form
    } else if ($fromform = $mform->get_data()) {
        remove_his_user($fromform->username, $fromform->courseid);
    } else {
        
    }

    // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.
    //Set default data (if any)
    $mform->set_data($toform);
    //displays the form
    $mform->display();

    echo $OUTPUT->footer();
} catch (coding_exception $e) {
    throw new moodle_exception('errormsg', 'local_cron', '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception('errormsg', 'local_cron', '', $e->getMessage(), $e->debuginfo);
}


