<?php
//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class faq_form_setting extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; // Don't forget the underscore! 
        //TODO: Add to string (JSALGADO)
        $mform->addElement('hidden', 'status', 'a');
        $mform->addElement('checkbox', 'activepluginhelp', get_string('active_help', 'local_help'), get_string('active_help', 'local_help'), array(), array(1, 0)); // Add elements to your form
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}

