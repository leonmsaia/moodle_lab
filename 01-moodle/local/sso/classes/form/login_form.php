<?php

namespace local_sso\form;

require_once($CFG->libdir . '/formslib.php');

class login_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'username', 'Usuario');
        $mform->setType('username', PARAM_TEXT);
        $mform->addRule('username', 'Requerido', 'required');

        $mform->addElement('password', 'password', 'Contraseña');
        $mform->setType('password', PARAM_TEXT);
        $mform->addRule('password', 'Requerida', 'required');

        $mform->addElement('submit', 'loginbtn', 'Iniciar sesión');
    }

    //Custom validation should be added here
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if(empty($data['username'])){
            $errors['username'] = get_string('notnull', 'eabcattendance');
        } else {
            if(strlen($data['username']) < 3 ) {
                $errors['username'] = 'Debe tener al menos 3 dígitos';
            }
            // if (\mod_eabcattendance\metodos_comunes::validar_rut($data['username']) == false) {
            //     $errors['username'] = get_string('invalidrutparticipante', 'eabcattendance');
            // }         
        }
        
        return $errors;
    }
}
