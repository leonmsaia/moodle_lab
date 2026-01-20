<?php
namespace local_download_cert\form;

require_once("$CFG->libdir/formslib.php");

class search_form extends \moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;

        $mform = $this->_form;
 
        $mform->addElement('text', 'diploma', 'Ingrese el código del diploma:');
        $mform->setType('diploma', PARAM_RAW);
        $mform->addRule('diploma', 'El código es requerido', 'required', null, 'server');
        
        $mform->addElement('submit', 'submitbutton', 'Validar');

    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}