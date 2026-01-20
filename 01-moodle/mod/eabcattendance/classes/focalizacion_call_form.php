<?php 
require_once("$CFG->libdir/formslib.php");

class focalizacion_call_form extends moodleform {    

    public function __construct($action,$customdata) {
        parent::__construct($action,$customdata);
    }

    public function definition() {
        global $DB;
        
        $focalizacion = $DB->get_record('focalizacion',array('sesionid'=>$this->_customdata['sesionid'], 'instructorid'=>$this->_customdata['instructorid']));        
        $default = ($focalizacion) ? $focalizacion->callemp : 0;

        $mform = $this->_form;         
        $radioarray=array();        
        $radioarray[] = $mform->createElement('radio', 'yesno', '', get_string('yes'), 1);
        $radioarray[] = $mform->createElement('radio', 'yesno', '', get_string('no'), 0);
        $mform->addGroup($radioarray, 'radioar', '', array(' '), false);
        $mform->setDefault('yesno', $default);
            
        $buttonlabel = 'Enviar';
        $this->add_action_buttons(false, $buttonlabel);        
    }

    function validation($data, $files) {
        return array();
    }
}
