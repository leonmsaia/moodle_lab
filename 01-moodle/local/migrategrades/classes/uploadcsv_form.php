<?php

namespace local_migrategrades;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/csvlib.class.php');

class uploadcsv_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('uploadtitle', 'local_migrategrades'));
        $mform->addElement('static', 'csvhelp', '', get_string('csv_help', 'local_migrategrades'));

        $mform->addElement('filepicker', 'userfile', get_string('file', 'local_migrategrades'), null, array('accepted_types' => array('.csv')));
        $mform->addRule('userfile', null, 'required');

        $choices = \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        $mform->setDefault('delimiter_name', 'semicolon');

        $choices = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $this->add_action_buttons(false, get_string('upload', 'local_migrategrades'));
    }
}
