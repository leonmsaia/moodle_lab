<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * editfaq_form class
 *
 * @package    local_help
 * @copyright 2019 Osvaldo Arriola <osvaldo@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_help\form;

use moodle_exception;
use moodleform;

defined('MOODLE_INTERNAL') || die();

class editfaq extends moodleform {
    /**
     * Define the form.
     * @throws moodle_exception
     */
    public function definition() {
        $mform = $this->_form;
        $custom = $this->_customdata;

        // Title.
        $mform->addElement('text', 'title', get_string('title', 'local_help'));
        $mform->setType('title', PARAM_RAW);
        $mform->addRule('title', get_string('required'), 'required');

        // Content.
        $mform->addElement('textarea', 'content', get_string('content', 'local_help'), ["rows" => 10, "cols" => 50]);
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', get_string('required'), 'required');

        if(!empty($custom["faq"])) {
            $mform->setDefault('title', $custom["faq"]->title);
            $mform->setDefault('content', $custom["faq"]->content);

            // ID.
            $mform->addElement('hidden', 'id');
            $mform->setConstant('id', $custom['faq']->id);
            $mform->setType('id', PARAM_INT);
        }


        $this->add_action_buttons();
    }
}