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
 * This file contains the forms for duration
 *
 * @package   mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');

/**
 * class for displaying duration form.
 *
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_eabcattendance_duration_form extends moodleform {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {

        $mform    =& $this->_form;

        $cm            = $this->_customdata['cm'];
        $ids           = $this->_customdata['ids'];

        $mform->addElement('header', 'general', get_string('changeduration', 'eabcattendance'));
        $mform->addElement('static', 'count', get_string('countofselected', 'eabcattendance'), count(explode('_', $ids)));

        for ($i = 0; $i <= 23; $i++) {
            $hours[$i] = sprintf("%02d", $i);
        }
        for ($i = 0; $i < 60; $i += 5) {
            $minutes[$i] = sprintf("%02d", $i);
        }
        $durselect[] =& $mform->createElement('select', 'hours', '', $hours);
        $durselect[] =& $mform->createElement('select', 'minutes', '', $minutes, false, true);
        $mform->addGroup($durselect, 'durtime', get_string('newduration', 'eabcattendance'), array(' '), true);

        $mform->addElement('hidden', 'ids', $ids);
        $mform->setType('ids', PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', 'id', $cm->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', mod_eabcattendance_sessions_page_params::ACTION_CHANGE_DURATION);
        $mform->setType('action', PARAM_INT);

        $mform->setDefaults(array('durtime' => array('hours' => 0, 'minutes' => 0)));

        $submitstring = get_string('update', 'eabcattendance');
        $this->add_action_buttons(true, $submitstring);
    }

}
