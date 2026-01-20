<?php 
require_once("$CFG->libdir/formslib.php");

class simplehtml_form extends moodleform {
    public function definition() {        
        $submitstring = get_string('submit');
        $mform = $this->_form;
        $grupo=array();
        $grupo[] =& $mform->createElement('text', 'email', get_string('email'),array('required'=>true));
        $mform->setType('email', PARAM_TEXT);                   //Set type of element        
        $mform->addGroup($grupo, 'choosedate', get_string('email'), array(''), false);

        $option = array(
            'startyear' => date('Y') - 1, 
            'stopyear'  => date('Y'),
            'timezone'  => 99,
            'optional'  => false
        );       
        $dateIni = strtotime(date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-1 month" );
        $mform->addElement('date_selector', 'startdate',get_string('fechadesde', 'message_emma'), $option);        
        $mform->addElement('date_selector', 'enddate'  ,get_string('fechahasta', 'message_emma'), $option);
        $mform->setDefault('startdate', $dateIni);
        $mform->addElement('submit', 'submitbutton', $submitstring);
    }

    function validation($data, $files) {
        return array();
    }
}