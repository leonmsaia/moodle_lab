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


include_once('../../config.php');

/**
 * @var core_renderer $OUTPUT
 * @var moodle_page $PAGE
 */
global $OUTPUT, $CFG, $PAGE;

require_once($CFG->libdir . '/adminlib.php');
require_once('lib.php');


require_once("$CFG->libdir/formslib.php");
 
class configyear_certificate_form extends moodleform {
    
    public function __construct($action, $customdata) {
        parent::__construct($action, $customdata);
    }
    //Add elements to form
    public function definition() {
        global $DB;
 
        $mform = $this->_form; // Don't forget the underscore! 
 
        $mform->addElement('hidden', 'courseid', $this->_customdata["courseid"]);
        $mform->setType('courseid', PARAM_RAW);
        
        $mform->addElement('text', 'expiration_year', get_string('yearsexpirationcertificate', 'local_download_cert')); // Add elements to your form
        $mform->setType('expiration_year', PARAM_RAW); 
        
        $sql = $DB->get_record('download_cert_expiration', array('courseid' => $this->_customdata["courseid"]));
        if(!empty($sql)){
            //Set type of element
            $mform->setDefault('expiration_year', $sql->expiration_year);        //Default value
        }
        
        
        $this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        $errros = array();
        if (!preg_match('/^[0-9]*$/', $data["expiration_year"])) {
            $errros["expiration_year"] = get_string('validationformatnumbercertificate', 'local_download_cert');
        }
        if(empty($data["expiration_year"])){
            $errros["expiration_year"] = get_string('notempty', 'local_download_cert');
        }
        return $errros;
    }
}


$courseid          = optional_param('courseid', 0, PARAM_INT);
require_login($courseid);

$course = $DB->get_record('course', array('id' => $courseid));

try {
    
    if (!has_capability('local/download_cert:configyear_certificate',  context_course::instance($courseid), $USER->id)) {
        redirect(new moodle_url('/course/view.php', array('id' => $courseid)), get_string('notaccespageconfigyear', 'local_download_cert'));
    }
    
    $context = context_course::instance($courseid);
    $PAGE->set_url(new moodle_url('/local/download_cert/configyear_certificate.php'));
    $PAGE->set_context($context);

    echo $OUTPUT->header();
    
    $customdata = array('courseid' => $courseid);
    
    $mform = new configyear_certificate_form($CFG->wwwroot . '/local/download_cert/configyear_certificate.php?courseid='.$courseid, $customdata);
 
    //Form processing and displaying is done here
    if ($mform->is_cancelled()) {
        //Handle form cancel operation, if cancel button is present on form
    } else if ($fromform = $mform->get_data()) {
        local_download_cert\download_cert_utils::save_configyear_certificate($fromform);
    }
    
    //displays the form
    $mform->display();
      
    echo $OUTPUT->footer();

} catch (coding_exception $e) {
    throw new moodle_exception('errormsg', 'local_download_cert', '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception('errormsg', 'local_download_cert', '', $e->getMessage(), $e->debuginfo);
}


