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

class user_form extends moodleform {
    
    public function definition() {
        $mform = $this->_form;
        $enroles = rating_item_utils::get_users_to_rating($this->_customdata['courseid']);
         $mform->addElement('autocomplete2', 'selcursos', "", $enroles, array());
    }
}

$GLOBALS['_HTML_QuickForm_default_renderer'] = new \MoodleQuickForm_Renderer();

\MoodleQuickForm::registerElementType('autocomplete2', "$CFG->dirroot/local/rating_item/autocomplete2.php", 'MoodleQuickForm_autocomplete2');

