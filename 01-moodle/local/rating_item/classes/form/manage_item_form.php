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
use \local_rating_item\utils\rating_item_utils;
use moodle_url;

class manage_item_form extends moodleform {

    public $courseid;
    
    public function __construct($courseid, $customdata = null) {
            $this->courseid = $courseid;
            parent::__construct(new \moodle_url("/local/rating_item/manage_rating_item.php?courseid=" . $this->courseid), $customdata);
        }

        //Add elements to form
        public function definition() {
            global $CFG;
            $grade_item = rating_item_utils::get_gradeitems_course($this->courseid);
            $mform = $this->_form; // Don't forget the underscore! 
            $selectitem1 = $mform->addElement('select', 'item1', 'item1', $grade_item);
            $mform->setType('item1', PARAM_RAW);
            if(!empty($this->_customdata['item1'])){
                $mform->setDefault('item1', $this->_customdata['item1']);
            }
            $selectitem2 = $mform->addElement('select', 'item2', 'item2', $grade_item);
            $mform->setType('item2', PARAM_RAW);
            if(!empty($this->_customdata['item2'])){
                $mform->setDefault('item2', $this->_customdata['item2']);
            }
            $this->add_action_buttons();

        }

        //Custom validation should be added here
        function validation($data, $files) {
            $errors = parent::validation($data, $files);
            $errors = array();
            if(empty($data["item1"])){
                $errors["item1"] = get_string('required');
            } else if(empty($data["item2"])){
                $errors["item2"] = get_string('required');
            } else if((!empty($data["item1"]) && !empty($data["item2"])) && ($data["item1"] == $data["item2"]) ){
                $errors["item1"] = "los campos no pueden ser iguales";
            }
            
            echo print_r($data, true);
            
            return $errors;
        }

}
