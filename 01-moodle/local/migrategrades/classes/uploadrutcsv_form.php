<?php

namespace local_migrategrades;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class uploadrutcsv_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'userfile', get_string('file', 'local_migrategrades'), null, array(
            'accepted_types' => array('.csv'),
            'maxbytes' => 0,
        ));
        $mform->addRule('userfile', null, 'required', null, 'client');

        $mform->addElement('select', 'encoding', get_string('encoding', 'local_migrategrades'), \core_text::get_encodings());
        $mform->setDefault('encoding', 'UTF-8');

        $delims = \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('delimiter', 'local_migrategrades'), $delims);
        $mform->setDefault('delimiter_name', 'comma');

        $mform->addElement('static', 'help', '', get_string('companyassign_csv_help', 'local_migrategrades'));

        $this->add_action_buttons(true, get_string('upload', 'local_migrategrades'));
    }
}
