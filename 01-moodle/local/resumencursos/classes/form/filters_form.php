<?php
namespace local_resumencursos\form;

use html_writer;

require_once("$CFG->libdir/formslib.php");

class filters_form extends \moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore! 
 
        $mform->addElement('text', 'curso', 'Curso');
        $mform->setType('curso', PARAM_RAW);
        
        $mform->addElement('text', 'hours', 'Horas');
        $mform->setType('hours', PARAM_RAW);

        $mform->addElement('html', html_writer::div(get_string('modalidad', 'local_showallactivities'), 'ml-5'));
        $mform->addElement('checkbox', 'modalidadopresencial', get_string('presencial', 'local_showallactivities'));
        $mform->addElement('checkbox', 'modalidadsemipresencial', get_string('semipresencial', 'local_showallactivities'));
        //$mform->addElement('checkbox', 'modalidaddistancia', get_string('distancia', 'local_showallactivities'));
        $mform->addElement('checkbox', 'modalidadelearning', 'Elearning');
        $mform->addElement('checkbox', 'modalidadstreaming', 'Streaming');
        $mform->addElement('checkbox', 'modalidadmobile', 'Mobile');
        
        $options = array(
            'optional'  => true
        );
        
        $mform->addElement('date_selector', 'dateto', 'Fecha desde', $options);
        $mform->setType('dateto', PARAM_RAW);
        $mform->addElement('date_selector', 'datefrom', 'Fecha hasta', $options);
        $mform->setType('datefrom', PARAM_RAW);

        $this->add_action_buttons(null, get_string('aplicate_filters', 'local_resumencursos'));

    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}