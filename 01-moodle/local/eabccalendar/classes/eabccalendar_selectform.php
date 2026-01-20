<?php 
require_once("$CFG->libdir/formslib.php");

class simplehtml_form extends moodleform {

    public function definition() {
        global $USER;

        $all_cursos =  enrol_get_all_users_courses($USER->id);
        $cursos = [];
        foreach($all_cursos as $curso){
            $cursos[$curso->id] = $curso->fullname;            
        }        
        $mform = $this->_form; 

        $options = array(                                                                                                           
            'multiple' => true,                                                                                                                                              
        );         
        $mform->addElement('select', 'selcurso', get_string('searchcurso', 'local_eabccalendar'), $cursos, $options);
    }

    function validation($data, $files) {
        return array();
    }
}