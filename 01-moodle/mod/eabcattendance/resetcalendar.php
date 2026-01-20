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
 * Reset Calendar events.
 *
 * @package    mod_eabcattendance
 * @copyright  2017 onwards Dan Marsden http://danmarsden.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/mod/eabcattendance/lib.php');
require_once($CFG->dirroot.'/mod/eabcattendance/locallib.php');

$action = optional_param('action', '', PARAM_ALPHA);

admin_externalpage_setup('managemodules');
$context = context_system::instance();

// Check permissions.
require_capability('mod/eabcattendance:viewreports', $context);

$exportfilename = 'eabcattendance-absentee.csv';

$PAGE->set_url('/mod/eabcattendance/resetcalendar.php');

$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('resetcalendar', 'mod_eabcattendance'));
$tabmenu = eabcattendance_print_settings_tabs('resetcalendar');
echo $tabmenu;

if (get_config('eabcattendance', 'enablecalendar')) {
    // Check to see if all sessions that need them have calendar events.
    if ($action == 'create' && confirm_sesskey()) {
        $sessions = $DB->get_recordset('eabcattendance_sessions',  array('caleventid' => 0, 'calendarevent' => 1));
        foreach ($sessions as $session) {
            eabcattendance_create_calendar_event($session);
            if ($session->caleventid) {
                $DB->update_record('eabcattendance_sessions', $session);
            }
        }
        $sessions->close();
        echo $OUTPUT->notification(get_string('eventscreated', 'mod_eabcattendance'), 'notifysuccess');
    } else {
        if ($DB->record_exists('eabcattendance_sessions', array('caleventid' => 0, 'calendarevent' => 1))) {
            $createurl = new moodle_url('/mod/eabcattendance/resetcalendar.php', array('action' => 'create'));
            $returnurl = new moodle_url('/admin/settings.php', array('section' => 'modsettingeabcattendance'));

            echo $OUTPUT->confirm(get_string('resetcaledarcreate', 'mod_eabcattendance'), $createurl, $returnurl);
        } else {
            echo $OUTPUT->box(get_string("noeventstoreset", "mod_eabcattendance"));
        }
    }
} else {
    if ($action == 'delete' && confirm_sesskey()) {
        $caleventids = $DB->get_records_select_menu('eabcattendance_sessions', 'caleventid > 0', array(),
                                                     '', 'caleventid, caleventid as id2');
        $DB->delete_records_list('event', 'id', $caleventids);
        $DB->execute("UPDATE {eabcattendance_sessions} set caleventid = 0");
        echo $OUTPUT->notification(get_string('eventsdeleted', 'mod_eabcattendance'), 'notifysuccess');
    } else {
        // Check to see if there are any events that need to be deleted.
        if ($DB->record_exists_select('eabcattendance_sessions', 'caleventid > 0')) {
            $deleteurl = new moodle_url('/mod/eabcattendance/resetcalendar.php', array('action' => 'delete'));
            $returnurl = new moodle_url('/admin/settings.php', array('section' => 'modsettingeabcattendance'));

            echo $OUTPUT->confirm(get_string('resetcaledardelete', 'mod_eabcattendance'), $deleteurl, $returnurl);
        } else {
            echo $OUTPUT->box(get_string("noeventstoreset", "mod_eabcattendance"));
        }
    }

}

echo $OUTPUT->footer();