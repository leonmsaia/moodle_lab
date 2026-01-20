<?php 
require_once("$CFG->libdir/formslib.php");

class focalizacion_mail_form extends moodleform {

    public function __construct($action,$customdata) {
        parent::__construct($action,$customdata);
    }

    public function definition() {
        $mform = $this->_form; 
        $fecha = $this->_customdata['fecha'].' hora: '.$this->_customdata['hora'];

        $contenido = 'Estimada(o), '. $this->_customdata["nombrecontacto"].'
Mi nombre es <b>'. $this->_customdata["nombre"].' ' .$this->_customdata["apellido"].'</b>, facilitador de Mutual de Seguridad CChC.

Envío este correo con el propósito de solicitar información para el curso <b>'.$this->_customdata["curso"].'</b>, que me han asignado y que debo realizar en la fecha <b>'.$fecha.'</b>, en las dependencias que han designado.

Por lo anterior, necesito verificar los siguientes datos:
• Público Objetivo
• Dirección
• Referencia para llegar
• Nombre de la persona de contacto en el lugar y su teléfono para contacto
• Cantidad de participantes
• Necesidad de utilización de Elementos de Protección Personal para ingreso a instalaciones
• Cuáles temas del curso a dictar se debe dar mayor énfasis o algún foco en particular a ser tratado de acuerdo a la realidad de la empresa.

Quedando atento a su respuesta, les saluda cordialmente

'.$this->_customdata["nombre"].' ' .$this->_customdata["apellido"];
        
        $mform->addElement('text', 'emailempresa', 'Para');
        $mform->setDefault('emailempresa', $this->_customdata["emailcontacto"]);
        $mform->setType('emailempresa', PARAM_EMAIL);
        //$mform->setDefault('emailempresa', 'email@empresa.com');
        $mform->addRule('emailempresa', 'Email', 'required', null, 'client');

        $mform->addElement('editor', 'contenido', 'contenido', array('rows' => 25, 'columns' => 80),array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true));
        $mform->setType('contenido', PARAM_RAW);
        $mform->setDefault('contenido', array('text' => format_text($contenido)));

        $mform->addElement('hidden', 'sesionid', $this->_customdata["sesionid"]);
        $mform->setType('sesionid', PARAM_RAW);

        $buttonlabel = 'Enviar';
        $this->add_action_buttons(false, $buttonlabel);        
    }

    function validation($data, $files) {
        return array();
    }

}
