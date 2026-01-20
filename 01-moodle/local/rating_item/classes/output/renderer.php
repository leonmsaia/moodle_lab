<?php

/**
 * Settings used by the eabctiles course format
 *
 * @package local_rating_item
 * @copyright  2020 José Salgado jose@e-abclearning.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 * */

namespace local_rating_item\output;

use moodle_page;
use coding_exception;
use html_writer;
use region_main_settings_menu;
use local_rating_item\form\user_form;

class renderer extends \renderer_base {
    public function hola() {
        return "hola";
    }
    
    public function select_users() {
        $mform = new \local_rating_item\form\user_form();
        return $mform;
    }
}