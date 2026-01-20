<?php
//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
 
class faq_form_comments extends moodleform {
    public $idfaq;
    
    public function __construct($url = null, $idfaq = null) {
        $this->idfaq = $idfaq;
        parent::__construct($url);
    }
    //Add elements to form
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; // Don't forget the underscore! 
        //TODO: Add to string (JSALGADO)
        $mform->addElement('textarea', 'pregunta', get_string('add_question', 'local_help')); // Add elements to your form
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}

