<?php
namespace local_company\classes\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class company_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;
    $mform->addElement('hidden', 'id');
    $mform->setType('id', PARAM_INT);

    $mform->addElement('text', 'name', get_string('companyname', 'local_company'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('text', 'shortname', get_string('companyshortname', 'local_company'));
            $mform->setType('shortname', PARAM_TEXT);
            $mform->addRule('shortname', null, 'required', null, 'client');

            $mform->addElement('text', 'contrato', get_string('companycontrato', 'local_company'));
            $mform->setType('contrato', PARAM_TEXT);
            $mform->addRule('contrato', null, 'required', null, 'client');

            $mform->addElement('text', 'city', get_string('companycity', 'local_company'));
            $mform->setType('city', PARAM_TEXT);

            $mform->addElement('text', 'rut', get_string('companyrut', 'local_company'));
            $mform->setType('rut', PARAM_TEXT);
            $mform->addRule('rut', null, 'required', null, 'client');

            $this->add_action_buttons(true, get_string('savecompany', 'local_company'));
    }
}
