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
 * Library of functions and constants for module eabcattendance
 *
 * @package   mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/classes/calendar_helpers.php');

/**
 * Returns the information if the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function eabcattendance_supports($feature) {
    switch($feature) {
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        // Artem Andreev: AFAIK it's not tested.
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        default:
            return null;
    }
}

/**
 * Add default set of statuses to the new eabcattendance.
 *
 * @param int $attid - id of eabcattendance instance.
 */
function eabcatt_add_default_statuses($attid) {
    global $DB;

    $statuses = $DB->get_recordset('eabcattendance_statuses', array('eabcattendanceid' => 0), 'id');
    foreach ($statuses as $st) {
        $rec = $st;
        $rec->eabcattendanceid = $attid;
        $DB->insert_record('eabcattendance_statuses', $rec);
    }
    $statuses->close();
}

/**
 * Add default set of warnings to the new eabcattendance.
 *
 * @param int $id - id of eabcattendance instance.
 */
function eabcattendance_add_default_warnings($id) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/eabcattendance/locallib.php');

    $warnings = $DB->get_recordset('eabcattendance_warning',
        array('idnumber' => 0), 'id');
    foreach ($warnings as $n) {
        $rec = $n;
        $rec->idnumber = $id;
        $DB->insert_record('eabcattendance_warning', $rec);
    }
    $warnings->close();
}

/**
 * Add new eabcattendance instance.
 *
 * @param stdClass $eabcattendance
 * @return bool|int
 */
function eabcattendance_add_instance($eabcattendance) {
    global $DB;

    $eabcattendance->timemodified = time();

    $eabcattendance->id = $DB->insert_record('eabcattendance', $eabcattendance);

    eabcatt_add_default_statuses($eabcattendance->id);

    eabcattendance_add_default_warnings($eabcattendance->id);

    eabcattendance_grade_item_update($eabcattendance);

    return $eabcattendance->id;
}

/**
 * Update existing eabcattendance instance.
 *
 * @param stdClass $eabcattendance
 * @return bool
 */
function eabcattendance_update_instance($eabcattendance) {
    global $DB;

    $eabcattendance->timemodified = time();
    $eabcattendance->id = $eabcattendance->instance;

    if (! $DB->update_record('eabcattendance', $eabcattendance)) {
        return false;
    }

    eabcattendance_grade_item_update($eabcattendance);

    return true;
}

/**
 * Delete existing eabcattendance
 *
 * @param int $id
 * @return bool
 */
function eabcattendance_delete_instance($id) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/mod/eabcattendance/locallib.php');

    if (! $eabcattendance = $DB->get_record('eabcattendance', array('id' => $id))) {
        return false;
    }

    if ($sessids = array_keys($DB->get_records('eabcattendance_sessions', array('eabcattendanceid' => $id), '', 'id'))) {
        if (eabcattendance_existing_calendar_events_ids($sessids)) {
            eabcattendance_delete_calendar_events($sessids);
        }
        $DB->delete_records_list('eabcattendance_log', 'sessionid', $sessids);
        $DB->delete_records('eabcattendance_sessions', array('eabcattendanceid' => $id));
    }
    $DB->delete_records('eabcattendance_statuses', array('eabcattendanceid' => $id));

    $DB->delete_records('eabcattendance_warning', array('idnumber' => $id));

    $DB->delete_records('eabcattendance', array('id' => $id));

    eabcattendance_grade_item_delete($eabcattendance);

    return true;
}

/**
 * Called by course/reset.php
 * @param moodleform $mform form passed by reference
 */
function eabcattendance_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'eabcattendanceheader', get_string('modulename', 'eabcattendance'));

    $mform->addElement('static', 'description', get_string('description', 'eabcattendance'),
                                get_string('resetdescription', 'eabcattendance'));
    $mform->addElement('checkbox', 'reset_eabcattendance_log', get_string('deletelogs', 'eabcattendance'));

    $mform->addElement('checkbox', 'reset_eabcattendance_sessions', get_string('deletesessions', 'eabcattendance'));
    $mform->disabledIf('reset_eabcattendance_sessions', 'reset_eabcattendance_log', 'notchecked');

    $mform->addElement('checkbox', 'reset_eabcattendance_statuses', get_string('resetstatuses', 'eabcattendance'));
    $mform->setAdvanced('reset_eabcattendance_statuses');
    $mform->disabledIf('reset_eabcattendance_statuses', 'reset_eabcattendance_log', 'notchecked');
}

/**
 * Course reset form defaults.
 *
 * @param stdClass $course
 * @return array
 */
function eabcattendance_reset_course_form_defaults($course) {
    return array('reset_eabcattendance_log' => 0, 'reset_eabcattendance_statuses' => 0, 'reset_eabcattendance_sessions' => 0);
}

/**
 * Reset user data within eabcattendance.
 *
 * @param stdClass $data
 * @return array
 */
function eabcattendance_reset_userdata($data) {
    global $DB;

    $status = array();

    $attids = array_keys($DB->get_records('eabcattendance', array('course' => $data->courseid), '', 'id'));

    if (!empty($data->reset_eabcattendance_log)) {
        $sess = $DB->get_records_list('eabcattendance_sessions', 'eabcattendanceid', $attids, '', 'id');
        if (!empty($sess)) {
            list($sql, $params) = $DB->get_in_or_equal(array_keys($sess));
            $DB->delete_records_select('eabcattendance_log', "sessionid $sql", $params);
            list($sql, $params) = $DB->get_in_or_equal($attids);
            $DB->set_field_select('eabcattendance_sessions', 'lasttaken', 0, "eabcattendanceid $sql", $params);
            if (empty($data->reset_eabcattendance_sessions)) {
                // If sessions are being retained, clear automarkcompleted value.
                $DB->set_field_select('eabcattendance_sessions', 'automarkcompleted', 0, "eabcattendanceid $sql", $params);
            }

            $status[] = array(
                'component' => get_string('modulenameplural', 'eabcattendance'),
                'item' => get_string('eabcattendancedata', 'eabcattendance'),
                'error' => false
            );
        }
    }

    if (!empty($data->reset_eabcattendance_statuses)) {
        $DB->delete_records_list('eabcattendance_statuses', 'eabcattendanceid', $attids);
        foreach ($attids as $attid) {
            eabcatt_add_default_statuses($attid);
        }

        $status[] = array(
            'component' => get_string('modulenameplural', 'eabcattendance'),
            'item' => get_string('sessions', 'eabcattendance'),
            'error' => false
        );
    }

    if (!empty($data->reset_eabcattendance_sessions)) {
        $sessionsids = array_keys($DB->get_records_list('eabcattendance_sessions', 'eabcattendanceid', $attids, '', 'id'));
        if (eabcattendance_existing_calendar_events_ids($sessionsids)) {
            eabcattendance_delete_calendar_events($sessionsids);
        }
        $DB->delete_records_list('eabcattendance_sessions', 'eabcattendanceid', $attids);

        $status[] = array(
            'component' => get_string('modulenameplural', 'eabcattendance'),
            'item' => get_string('statuses', 'eabcattendance'),
            'error' => false
        );
    }

    return $status;
}
/**
 * Return a small object with summary information about what a
 *  user has done with a given particular instance of this module
 *  Used for user activity reports.
 *  $return->time = the time they did it
 *  $return->info = a short text description
 *
 * @param stdClass $course - full course record.
 * @param stdClass $user - full user record
 * @param stdClass $mod
 * @param stdClass $eabcattendance
 * @return stdClass.
 */
function eabcattendance_user_outline($course, $user, $mod, $eabcattendance) {
    global $CFG;
    require_once(dirname(__FILE__) . '/locallib.php');
    require_once($CFG->libdir.'/gradelib.php');

    $grades = grade_get_grades($course->id, 'mod', 'eabcattendance', $eabcattendance->id, $user->id);

    $result = new stdClass();
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        $result->time = $grade->dategraded;
    } else {
        $result->time = 0;
    }
    if (has_capability('mod/eabcattendance:canbelisted', $mod->context, $user->id)) {
        $summary = new mod_eabcattendance_summary($eabcattendance->id, $user->id);
        $usersummary = $summary->get_all_sessions_summary_for($user->id);

        $result->info = $usersummary->pointsallsessions;
    }

    return $result;
}
/**
 * Print a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $eabcattendance
 */
function eabcattendance_user_complete($course, $user, $mod, $eabcattendance) {
    global $CFG;

    require_once(dirname(__FILE__) . '/renderhelpers.php');
    require_once($CFG->libdir.'/gradelib.php');

    if (has_capability('mod/eabcattendance:canbelisted', $mod->context, $user->id)) {
        echo eabcatt_construct_full_user_stat_html_table($eabcattendance, $user);
    }
}

/**
 * Dummy function - must exist to allow quick editing of module name.
 *
 * @param stdClass $eabcattendance
 * @param int $userid
 * @param bool $nullifnone
 */
function eabcattendance_update_grades($eabcattendance, $userid=0, $nullifnone=true) {
    // We need this function to exist so that quick editing of module name is passed to gradebook.
}
/**
 * Create grade item for given eabcattendance
 *
 * @param stdClass $eabcattendance object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function eabcattendance_grade_item_update($eabcattendance, $grades=null) {
    global $CFG, $DB;

    require_once('locallib.php');

    if (!function_exists('grade_update')) { // Workaround for buggy PHP versions.
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (!isset($eabcattendance->courseid)) {
        $eabcattendance->courseid = $eabcattendance->course;
    }
    if (!$DB->get_record('course', array('id' => $eabcattendance->course))) {
        error("Course is misconfigured");
    }

    if (!empty($eabcattendance->cmidnumber)) {
        $params = array('itemname' => $eabcattendance->name, 'idnumber' => $eabcattendance->cmidnumber);
    } else {
        // MDL-14303.
        $params = array('itemname' => $eabcattendance->name);
    }

    if ($eabcattendance->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $eabcattendance->grade;
        $params['grademin']  = 0;
    } else if ($eabcattendance->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$eabcattendance->grade;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/eabcattendance', $eabcattendance->courseid, 'mod', 'eabcattendance', $eabcattendance->id, 0, $grades, $params);
}

/**
 * Delete grade item for given eabcattendance
 *
 * @param object $eabcattendance object
 * @return object eabcattendance
 */
function eabcattendance_grade_item_delete($eabcattendance) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($eabcattendance->courseid)) {
        $eabcattendance->courseid = $eabcattendance->course;
    }

    return grade_update('mod/eabcattendance', $eabcattendance->courseid, 'mod', 'eabcattendance',
                        $eabcattendance->id, 0, null, array('deleted' => 1));
}

/**
 * This function returns if a scale is being used by one eabcattendance
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See book, glossary or journal modules
 * as reference.
 *
 * @param int $eabcattendanceid
 * @param int $scaleid
 * @return boolean True if the scale is used by any eabcattendance
 */
function eabcattendance_scale_used ($eabcattendanceid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of eabcattendance
 *
 * This is used to find out if scale used anywhere
 *
 * @param int $scaleid
 * @return bool true if the scale is used by any book
 */
function eabcattendance_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Serves the eabcattendance sessions descriptions files.
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function eabcattendance_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    if (!$DB->record_exists('eabcattendance', array('id' => $cm->instance))) {
        return false;
    }

    // Session area is served by pluginfile.php.
    $fileareas = array('session');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $sessid = (int)array_shift($args);
    if (!$DB->record_exists('eabcattendance_sessions', array('id' => $sessid))) {
        return false;
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_eabcattendance/$filearea/$sessid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, true);
}

/**
 * Print tabs on eabcattendance settings page.
 *
 * @param string $selected - current selected tab.
 */
function eabcattendance_print_settings_tabs($selected = 'settings') {
    global $CFG;
    // Print tabs for different settings pages.
    $tabs = array();
    $tabs[] = new tabobject('settings', $CFG->wwwroot.'/admin/settings.php?section=modsettingeabcattendance',
        get_string('settings', 'eabcattendance'), get_string('settings'), false);

    $tabs[] = new tabobject('defaultstatus', $CFG->wwwroot.'/mod/eabcattendance/defaultstatus.php',
        get_string('defaultstatus', 'eabcattendance'), get_string('defaultstatus', 'eabcattendance'), false);

    if (get_config('eabcattendance', 'enablewarnings')) {
        $tabs[] = new tabobject('defaultwarnings', $CFG->wwwroot . '/mod/eabcattendance/warnings.php',
            get_string('defaultwarnings', 'eabcattendance'), get_string('defaultwarnings', 'eabcattendance'), false);
    }

    $tabs[] = new tabobject('coursesummary', $CFG->wwwroot.'/mod/eabcattendance/coursesummary.php',
        get_string('coursesummary', 'eabcattendance'), get_string('coursesummary', 'eabcattendance'), false);

    if (get_config('eabcattendance', 'enablewarnings')) {
        $tabs[] = new tabobject('absentee', $CFG->wwwroot . '/mod/eabcattendance/absentee.php',
            get_string('absenteereport', 'eabcattendance'), get_string('absenteereport', 'eabcattendance'), false);
    }

    $tabs[] = new tabobject('resetcalendar', $CFG->wwwroot.'/mod/eabcattendance/resetcalendar.php',
        get_string('resetcalendar', 'eabcattendance'), get_string('resetcalendar', 'eabcattendance'), false);

    $tabs[] = new tabobject('importsessions', $CFG->wwwroot . '/mod/eabcattendance/import/sessions.php',
        get_string('importsessions', 'eabcattendance'), get_string('importsessions', 'eabcattendance'), false);

    ob_start();
    print_tabs(array($tabs), $selected);
    $tabmenu = ob_get_contents();
    ob_end_clean();

    return $tabmenu;
}