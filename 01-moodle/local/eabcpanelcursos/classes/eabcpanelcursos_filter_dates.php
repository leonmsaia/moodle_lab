<?php 
require_once("$CFG->libdir/formslib.php");

class simplehtml_form extends moodleform {

    public function definition() {
        $mform = $this->_form; 

        $option = array(
            'startyear' => '2024', 
            'stopyear'  => date('Y') + 2,
            'timezone'  => 99,
            'step'      => 5,
            'optional'  => false,
            
        );
        
        $submitstring = get_string('ok');
        $dateg=array();
        $dateg[] =& $mform->createElement('date_selector', 'startdate','', $option);        
        $dateg[] =& $mform->createElement('date_selector', 'enddate','',$option);
        $dateg[] =& $mform->createElement('submit', 'submitbutton', $submitstring);        
        $mform->addGroup($dateg, 'choosedate', '', array(''), false);
        
        
    }

    function validation($data, $files) {
        return array();
    }
}