<?php 
require_once("$CFG->libdir/formslib.php");

class simplehtml_form extends moodleform {
    protected $_form; // Define the $_form property

    public function definition() {
        global $DB;
        $mform = $this->_form; 

        $option = array(
            'startyear' => date('Y') - 2, 
            'stopyear'  => date('Y'),
            'timezone'  => 99,
            'optional'  => false
        );       
        $dateIni = strtotime(date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-1 day" );
                
        $sql = "SELECT * FROM mdl_facilitador_back ORDER BY nombre ASC";
        $facilitadores = $DB->get_records_sql($sql);
        $instructores = [];
        $instructores[0] = 'Todos';
        foreach($facilitadores as $facilitador){
            $instructores[$facilitador->id] = $facilitador->nombre.' '.$facilitador->apellidopaterno.' '.$facilitador->apellidomaterno;
        }  
        $submitstring = get_string('ok');
        $dateg=array();
        $dateg[] =& $mform->createElement('date_selector', 'startdate','', $option);
        $dateg[] =& $mform->createElement('date_selector', 'enddate','',$option);
        $mform->setDefault('startdate', $dateIni);

        $dateg[] =& $mform->createElement('submit', 'submitbutton', $submitstring);
        $mform->addGroup($dateg, 'choosedate', '', array(''), false);

        $rangos = [
            '0' => 'Todos',
            '1' => 'Igual a cero',
            '2' => 'De 1 a 25',
            '3' => 'De 26 a 50',
            '4' => 'De 51 a 75',
            '5' => 'De 76 a 100',
        ];

        $mform->addElement('select', 'selinstructor', 'Seleccione un facilitador para filtrar', $instructores);
        $mform->addElement('select', 'rangos', 'Seleccione un rango de total cumplimiento', $rangos);
                
    }

    function validation($data, $files) {
        return array();
    }

    function get_data() {
        return parent::get_data();
    }

    function display() {
        return parent::display();
    }
    
}
