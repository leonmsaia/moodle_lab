<?php

/**
 * Settings used by the eabctiles course format
 *
 * @package local_rating_item
 * @copyright  2020 José Salgado jose@e-abclearning.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 * */

namespace local_rating_item\form;

require_once("$CFG->libdir/formslib.php");

use moodleform;

class grade_item_form extends moodleform {
    
    public function __construct($action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true,
                                $ajaxformdata=null){
        parent::__construct(parent::__construct(new \moodle_url("/local/rating_item/manage_rating_item.php?courseid=" . $this->_customdata['courseid'])), $customdata, $method, $target, $attributes);
    }

    public function definition() {
        global $OUTPUT;
        $mform = $this->_form;
        
        $mform->addElement('hidden', 'userid', '', array('id' => 'userid'));
        $mform->setType('userid', PARAM_RAW);
        
        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid'], array('id' => 'courseid'));
        $mform->setType('courseid', PARAM_RAW);
        
        $grade = $mform->addElement('text', 'grade', 'grade');
        $mform->setType('grade', PARAM_RAW);
        
        $editor = $mform->addElement('editor', 'feedback', 'feedback');
        $mform->setType('feedback', PARAM_RAW);
        
        $this->add_action_buttons();
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if(!empty($data['grade'])) {
            if(preg_match("/\d{1}/", $data['grade']) === 0) {
                $errors["grade"] = "Debe tener al menos 10 digitos";
            }
        }
        return $errors;
    }

}

