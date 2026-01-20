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
 * Update form
 *
 * @package    mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');

/**
 * class for displaying update form.
 *
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_eabcattendance_update_form extends moodleform {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {

        global $DB;
        $mform    =& $this->_form;

        $modcontext    = $this->_customdata['modcontext'];
        $sessionid     = $this->_customdata['sessionid'];

        if (!$sess = $DB->get_record('eabcattendance_sessions', array('id' => $sessionid) )) {
            error('No such session in this course');
        }
        $eabcattendancesubnet = $DB->get_field('eabcattendance', 'subnet', array('id' => $sess->eabcattendanceid));
        $defopts = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $modcontext);
        $sess = file_prepare_standard_editor($sess, 'description', $defopts, $modcontext, 'mod_eabcattendance', 'session', $sess->id);
        $sessDirec = file_prepare_standard_editor($sess, 'direction', $defopts, $modcontext, 'mod_eabcattendance', 'session', $sess->id);

        $starttime = $sess->sessdate - usergetmidnight($sess->sessdate);
        $starthour = floor($starttime / HOURSECS);
        $startminute = floor(($starttime - $starthour * HOURSECS) / MINSECS);

        $enddate = $sess->sessdate + $sess->duration;
        $endtime = $enddate - usergetmidnight($enddate);
        $endhour = floor($endtime / HOURSECS);
        $endminute = floor(($endtime - $endhour * HOURSECS) / MINSECS);

        $data = array(
            'sessiondate' => $sess->sessdate,
            'sestime' => array('starthour' => $starthour, 'startminute' => $startminute,
            'endhour' => $endhour, 'endminute' => $endminute),
            'sdescription' => $sess->description_editor,
            'sdirection' => $sessDirec->direction_editor,
            'calendarevent' => $sess->calendarevent,
            'studentscanmark' => $sess->studentscanmark,
            'studentpassword' => $sess->studentpassword,
            'autoassignstatus' => $sess->autoassignstatus,
            'subnet' => $sess->subnet,
            'automark' => $sess->automark,
            'absenteereport' => $sess->absenteereport,
            'automarkcompleted' => 0,
            'preventsharedip' => $sess->preventsharedip,
            'preventsharediptime' => $sess->preventsharediptime,
            'includeqrcode' => $sess->includeqrcode
        );
        if ($sess->subnet == $eabcattendancesubnet) {
            $data['usedefaultsubnet'] = 1;
        } else {
            $data['usedefaultsubnet'] = 0;
        }

        $mform->addElement('header', 'general', get_string('changesession', 'eabcattendance'));

        if ($sess->groupid == 0) {
            $strtype = get_string('commonsession', 'eabcattendance');
        } else {
            $groupname = $DB->get_field('groups', 'name', array('id' => $sess->groupid));
            $strtype = get_string('group') . ': ' . $groupname;
        }
        $mform->addElement('static', 'sessiontypedescription', get_string('sessiontype', 'eabcattendance'), $strtype);

        $olddate = eabcatt_construct_session_full_date_time($sess->sessdate, $sess->duration);
        $mform->addElement('static', 'olddate', get_string('olddate', 'eabcattendance'), $olddate);

        eabcattendance_form_sessiondate_selector($mform);

        // Show which status set is in use.
        $maxstatusset = eabcattendance_get_max_statusset($this->_customdata['att']->id);
        if ($maxstatusset > 0) {
            $mform->addElement('static', 'statussetstring', get_string('usestatusset', 'mod_eabcattendance'),
                eabcattendance_get_setname($this->_customdata['att']->id, $sess->statusset));
        }
        $mform->addElement('hidden', 'statusset', $sess->statusset);
        $mform->setType('statusset', PARAM_INT);

        $mform->addElement('editor', 'sdescription', get_string('description', 'eabcattendance'),
                           array('rows' => 1, 'columns' => 80), $defopts);
        $mform->setType('sdescription', PARAM_RAW);

        $mform->addElement('editor', 'sdirection', get_string('direction', 'eabcattendance'),
                           array('rows' => 1, 'columns' => 80), $defopts);
        $mform->setType('sdirection', PARAM_RAW);

        if (!empty(get_config('eabcattendance', 'enablecalendar'))) {
            $mform->addElement('checkbox', 'calendarevent', '', get_string('calendarevent', 'eabcattendance'));
            $mform->addHelpButton('calendarevent', 'calendarevent', 'eabcattendance');
        } else {
            $mform->addElement('hidden', 'calendarevent', 0);
            $mform->setType('calendarevent', PARAM_INT);
        }

        // If warnings allow selector for reporting.
        if (!empty(get_config('eabcattendance', 'enablewarnings'))) {
            $mform->addElement('checkbox', 'absenteereport', '', get_string('includeabsentee', 'eabcattendance'));
            $mform->addHelpButton('absenteereport', 'includeabsentee', 'eabcattendance');
        }

        // Students can mark own eabcattendance.
        if (!empty(get_config('eabcattendance', 'studentscanmark'))) {
            $mform->addElement('header', 'headerstudentmarking', get_string('studentmarking', 'eabcattendance'), true);
            $mform->setExpanded('headerstudentmarking');

            $mform->addElement('checkbox', 'studentscanmark', '', get_string('studentscanmark', 'eabcattendance'));
            $mform->addHelpButton('studentscanmark', 'studentscanmark', 'eabcattendance');

            $options2 = eabcattendance_get_automarkoptions();

            $mform->addElement('select', 'automark', get_string('automark', 'eabcattendance'), $options2);
            $mform->setType('automark', PARAM_INT);
            $mform->addHelpButton('automark', 'automark', 'eabcattendance');
            $mform->hideif('automark', 'studentscanmark', 'notchecked');

            $mform->addElement('text', 'studentpassword', get_string('studentpassword', 'eabcattendance'));
            $mform->setType('studentpassword', PARAM_TEXT);
            $mform->addHelpButton('studentpassword', 'passwordgrp', 'eabcattendance');
            $mform->hideif('studentpassword', 'studentscanmark', 'notchecked');
            $mform->hideif('studentpassword', 'automark', 'eq', EABCATTENDANCE_AUTOMARK_ALL);
            $mform->hideif('randompassword', 'automark', 'eq', EABCATTENDANCE_AUTOMARK_ALL);
            $mform->addElement('checkbox', 'includeqrcode', '', get_string('includeqrcode', 'eabcattendance'));
            $mform->hideif('includeqrcode', 'studentscanmark', 'notchecked');
            $mform->addElement('checkbox', 'autoassignstatus', '', get_string('autoassignstatus', 'eabcattendance'));
            $mform->addHelpButton('autoassignstatus', 'autoassignstatus', 'eabcattendance');
            $mform->hideif('autoassignstatus', 'studentscanmark', 'notchecked');

            $mgroup = array();
            $mgroup[] = & $mform->createElement('text', 'subnet', get_string('requiresubnet', 'eabcattendance'));
            $mform->setDefault('subnet', $this->_customdata['att']->subnet);
            $mgroup[] = & $mform->createElement('checkbox', 'usedefaultsubnet', get_string('usedefaultsubnet', 'eabcattendance'));
            $mform->setDefault('usedefaultsubnet', 1);
            $mform->setType('subnet', PARAM_TEXT);

            $mform->addGroup($mgroup, 'subnetgrp', get_string('requiresubnet', 'eabcattendance'), array(' '), false);
            $mform->setAdvanced('subnetgrp');
            $mform->addHelpButton('subnetgrp', 'requiresubnet', 'eabcattendance');

            $mform->hideif('subnetgrp', 'studentscanmark', 'notchecked');
            $mform->hideif('subnet', 'usedefaultsubnet', 'checked');

            $mform->addElement('hidden', 'automarkcompleted', '0');
            $mform->settype('automarkcompleted', PARAM_INT);

            $mgroup3 = array();
            $options = eabcattendance_get_sharedipoptions();
            $mgroup3[] = & $mform->createElement('select', 'preventsharedip',
                get_string('preventsharedip', 'eabcattendance'), $options);
            $mgroup3[] = & $mform->createElement('text', 'preventsharediptime',
                get_string('preventsharediptime', 'eabcattendance'), '', 'test');
            $mform->addGroup($mgroup3, 'preventsharedgroup',
                get_string('preventsharedip', 'eabcattendance'), array(' '), false);
            $mform->addHelpButton('preventsharedgroup', 'preventsharedip', 'eabcattendance');
            $mform->setAdvanced('preventsharedgroup');
            $mform->setType('preventsharediptime', PARAM_INT);
            $mform->hideif('preventsharedgroup', 'studentscanmark', 'notchecked');
            $mform->hideIf('preventsharediptime', 'preventsharedip', 'noteq', EABCATTENDANCE_SHAREDIP_MINUTES);
        } else {
            $mform->addElement('hidden', 'studentscanmark', '0');
            $mform->settype('studentscanmark', PARAM_INT);
            $mform->addElement('hidden', 'subnet', '0');
            $mform->settype('subnet', PARAM_TEXT);
            $mform->addElement('hidden', 'automark', '0');
            $mform->settype('automark', PARAM_INT);
            $mform->addElement('hidden', 'automarkcompleted', '0');
            $mform->settype('automarkcompleted', PARAM_INT);
            $mform->addElement('hidden', 'autoassignstatus', '0');
            $mform->setType('autoassignstatus', PARAM_INT);
        }

        $mform->setDefaults($data);
        $this->add_action_buttons(true);
    }

    /**
     * Perform minimal validation on the settings form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        $sesstarttime = $data['sestime']['starthour'] * HOURSECS + $data['sestime']['startminute'] * MINSECS;
        $sesendtime = $data['sestime']['endhour'] * HOURSECS + $data['sestime']['endminute'] * MINSECS;
        if ($sesendtime < $sesstarttime) {
            $errors['sestime'] = get_string('invalidsessionendtime', 'eabcattendance');
        }

        if (!empty($data['studentscanmark']) && $data['automark'] == EABCATTENDANCE_AUTOMARK_CLOSE) {
            $cm            = $this->_customdata['cm'];
            // Check that the selected statusset has a status to use when unmarked.
            $sql = 'SELECT id
            FROM {eabcattendance_statuses}
            WHERE deleted = 0 AND (eabcattendanceid = 0 or eabcattendanceid = ?)
            AND setnumber = ? AND setunmarked = 1';
            $params = array($cm->instance, $data['statusset']);
            if (!$DB->record_exists_sql($sql, $params)) {
                $errors['automark'] = get_string('noabsentstatusset', 'eabcattendance');
            }
        }

        if (!empty($data['studentscanmark']) && !empty($data['preventsharedip']) &&
                empty($data['preventsharediptime'])) {
            $errors['preventsharedgroup'] = get_string('iptimemissing', 'eabcattendance');

        }
        return $errors;
    }
}
