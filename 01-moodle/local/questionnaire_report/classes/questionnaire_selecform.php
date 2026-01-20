<?php 
require_once("$CFG->libdir/formslib.php");

class simplehtml_form extends moodleform {

    public function definition() {
        // @codingStandardsIgnoreLine
        /** @var \moodle_database $DB */
        global $DB;
       
        $option = array(
            'startyear' => date('Y') - 2, 
            'stopyear'  => date('Y') + 2,
            'timezone'  => 99,
            'optional'  => false
        );
        $respuestas = array('response_text' => 'Respuesta texto', 'resp_single' => 'Respuesta simple', 'resp_multiple' => 'Respuesta Multiple', 'response_bool' => 'Respuesta Si y No');
        $mform = $this->_form; 
        $submitstring = get_string('ok');
        $dateg=array();           
        $dateg[] =& $mform->createElement('date_selector', 'startdate','', $option);        
        $dateg[] =& $mform->createElement('date_selector', 'enddate','',$option);       
        $dateg[] =& $mform->createElement('select', 'respuesta', '', $respuestas, false, true);
        $dateg[] =& $mform->createElement('submit', 'submitbutton', $submitstring);
        $mform->addGroup($dateg, 'choosedate', '', array(''), false);        

    }

    function validation($data, $files) {
        return array();
    }
}