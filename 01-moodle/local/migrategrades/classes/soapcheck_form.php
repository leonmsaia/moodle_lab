<?php

namespace local_migrategrades;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class soapcheck_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'username', get_string('soapcheck_username', 'local_migrategrades'));
        $mform->setType('username', PARAM_TEXT);
        $mform->addRule('username', null, 'required', null, 'client');

        $this->add_action_buttons(false, get_string('soapcheck_submit', 'local_migrategrades'));
    }
}
