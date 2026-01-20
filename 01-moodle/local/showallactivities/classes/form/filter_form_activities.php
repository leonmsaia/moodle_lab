<?php
namespace local_showallactivities\form;

require_once("$CFG->libdir/formslib.php");

use html_writer;

class filter_form_activities extends \moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; // Don't forget the underscore! 
 
        $mform->addElement('text', 'rut', 'Rut');
        $mform->setType('rut', PARAM_RAW);
        
        $mform->addElement('text', 'nombre', 'Nombre');
        $mform->setType('nombre', PARAM_RAW);
 
        $mform->addElement('text', 'empresa', 'Empresa');
        $mform->setType('empresa', PARAM_RAW);
 
        $mform->addElement('text', 'rutempresa', 'Rut de la empresa');
        $mform->setType('rutempresa', PARAM_RAW);
        
        $mform->addElement('text', 'nroempresa', 'N° Adh Empresa ');
        $mform->setType('nroempresa', PARAM_RAW);
        
        $mform->addElement('text', 'curso', 'Curso');
        $mform->setType('curso', PARAM_RAW);
        
        $mform->addElement('html', html_writer::div(get_string('modalidad', 'local_showallactivities'), 'ml-5'));
        $mform->addElement('checkbox', 'modalidadopresencial', get_string('presencial', 'local_showallactivities'));
        $mform->addElement('checkbox', 'modalidadsemipresencial', get_string('semipresencial', 'local_showallactivities'));
        $mform->addElement('checkbox', 'modalidadelearning', 'Elearning');
        $mform->addElement('checkbox', 'modalidadstreaming', 'Streaming');
        $mform->addElement('checkbox', 'modalidadmobile', 'Mobile');

        $options = array(
            'optional' => false
        );
        $mform->addElement('date_selector', 'dateto', 'Fecha desde', $options);
        $mform->setType('dateto', PARAM_RAW);
        $mform->addElement('date_selector', 'datefrom', 'Fecha hasta', $options);
        $mform->setType('datefrom', PARAM_RAW);
        
        $mform->addElement('text', 'evaluacion', get_string('evaluation', 'local_showallactivities'));
        $mform->setType('evaluacion', PARAM_RAW);
        
        $mform->addElement('html', html_writer::div(get_string('state', 'local_showallactivities'), 'ml-5'));
        $mform->addElement('checkbox', 'estadoabierto', get_string('open', 'local_showallactivities'));
        $mform->addElement('checkbox', 'estadocerrado', get_string('close', 'local_showallactivities'));
        
        $this->add_action_buttons(null, get_string('aplicate_filters', 'local_resumencursos'));
        
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}