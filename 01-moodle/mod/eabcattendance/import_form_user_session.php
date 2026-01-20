<?php

/**
 * Import excel user session form
 *
 * @package    mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');

class SimplehtmlFormUploadImportExcelParticipants extends moodleform {
    
    public function definition() {
 
        $mform = $this->_form;
        $filemanageropts = $this->_customdata['filemanageropts'];

        $itemidsesionid = 'attachments_session_'.$this->_customdata['sessionid'];
        
        $mform->addElement('filepicker', $itemidsesionid, get_string('file'), null, $filemanageropts);
        
        $this->add_action_buttons();
    }

    function validation($data, $files) {
        $errors = array();
        if (!empty($files['attachments_session_'.$this->_customdata['sessionid']])) {
            $file = reset($files['attachments_session_'.$this->_customdata['sessionid']]);
            if ($file['type'] !== 'text/csv' && $file['type'] !== 'text/plain') {
                $errors['attachments_session_'.$this->_customdata['sessionid']] = get_string('invalidfiletype', 'mod_eabcattendance');
            }
        }
        return $errors;
    }
}