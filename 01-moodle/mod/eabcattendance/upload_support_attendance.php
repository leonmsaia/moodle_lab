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
 * Adding eabcattendance sessions
 *
 * @package    mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/add_form.php');
require_once(dirname(__FILE__).'/update_form.php');
require_once(dirname(__FILE__).'/duration_form.php');
require_once("$CFG->libdir/formslib.php");


$pageparams = new mod_eabcattendance_sessions_page_params();

$id                     = required_param('id', PARAM_INT);
$pageparams->action     = required_param('action', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);

if (optional_param('deletehiddensessions', false, PARAM_TEXT)) {
    $pageparams->action = mod_eabcattendance_sessions_page_params::ACTION_DELETE_HIDDEN;
}

if (empty($pageparams->action)) {
    // The form on manage.php can submit with the "choose" option - this should be fixed in the long term,
    // but in the meantime show a useful error and redirect when it occurs.
    $url = new moodle_url('/mod/eabcattendance/view.php', array('id' => $id));
    redirect($url, get_string('invalidaction', 'mod_eabcattendance'), 2);
}

$cm             = get_coursemodule_from_id('eabcattendance', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$att            = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/eabcattendance:manageeabcattendances', $context);

$att = new mod_eabcattendance_structure($att, $cm, $course, $context, $pageparams);

class simplehtml_form_upload_support extends moodleform {
    
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; // Don't forget the underscore! 
        $filemanageropts = $this->_customdata['filemanageropts'];

        $itemidsesionid = 'attachments_session_'.$this->_customdata['sessionid'];
        
        $mform->addElement('filemanager', $itemidsesionid, get_string('file'), null, $filemanageropts);
        
        $this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}

$url = $att->url_upload_supporta_attendance(array('action' => $pageparams->action, 'sessionid' => $sessionid));
$PAGE->set_url($url);
$PAGE->set_title($course->shortname. ": ".$att->name);
$PAGE->set_heading($course->fullname);
$PAGE->force_settings_menu(true);
$PAGE->set_cacheable(true);
$PAGE->navbar->add($att->name);

$currenttab = eabcattendance_tabs::TAB_SUPPORT_ATTENDANCE;
$formparams = array('course' => $course, 'cm' => $cm, 'modcontext' => $context, 'att' => $att);

$output = $PAGE->get_renderer('mod_eabcattendance');
$tabs = new eabcattendance_tabs($att, $currenttab);
echo $output->header();
echo $output->heading(get_string('eabcattendanceforthecourse', 'eabcattendance').' :: ' .format_string($course->fullname));
echo $output->render($tabs);

if (empty($entry->id)) {
    $entry = new stdClass;
    $entry->id = 0;
}

$filemanageropts = array('subdirs' => 0, 'maxbytes' => '0', 'maxfiles' => 1, 'context' => $context);
$customdata = array('filemanageropts' => $filemanageropts, 'sessionid' => $sessionid);


$mform = new simplehtml_form_upload_support($url, $customdata);

$itemid = $sessionid; // This is used to distinguish between multiple file areas, e.g. different student's assignment submissions, or attachments to different forum posts, in this case we use '0' as there is no relevant id to use
 
$itemidsesionid = 'attachments_session_'.$sessionid;
$draftitemid = file_get_submitted_draft_itemid($itemidsesionid);

file_prepare_draft_area($draftitemid, $context->id, 'mod_eabcattendance', 'attachment', $itemid, $filemanageropts);

$entry = new stdClass();
$entry->$itemidsesionid = $draftitemid; 
$mform->set_data($entry);

if ($mform->is_cancelled()) {
} else if ($fromform = $mform->get_data()) {
    file_save_draft_area_files($draftitemid, $context->id, 'mod_eabcattendance', 'attachment', $itemid, $filemanageropts);
} 
    
$mform->display();

echo $OUTPUT->footer();