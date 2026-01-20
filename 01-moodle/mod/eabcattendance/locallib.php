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
 * local functions and constants for module eabcattendance
 *
 * @package   mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');
require_once(dirname(__FILE__) . '/renderhelpers.php');

define('EABCATT_VIEW_DAYS', 1);
define('EABCATT_VIEW_WEEKS', 2);
define('EABCATT_VIEW_MONTHS', 3);
define('EABCATT_VIEW_ALLPAST', 4);
define('EABCATT_VIEW_ALL', 5);
define('EABCATT_VIEW_NOTPRESENT', 6);
define('EABCATT_VIEW_SUMMARY', 7);

define('EABCATT_SORT_DEFAULT', 0);
define('EABCATT_SORT_LASTNAME', 1);
define('EABCATT_SORT_FIRSTNAME', 2);

define('EABCATTENDANCE_AUTOMARK_DISABLED', 0);
define('EABCATTENDANCE_AUTOMARK_ALL', 1);
define('EABCATTENDANCE_AUTOMARK_CLOSE', 2);

define('EABCATTENDANCE_SHAREDIP_DISABLED', 0);
define('EABCATTENDANCE_SHAREDIP_MINUTES', 1);
define('EABCATTENDANCE_SHAREDIP_FORCE', 2);

// Max number of sessions available in the warnings set form to trigger warnings.
define('EABCATTENDANCE_MAXWARNAFTER', 100);

/**
 * Get statuses,
 *
 * @param int $attid
 * @param bool $onlyvisible
 * @param int $statusset
 * @return array
 */
function eabcattendance_get_statuses($attid, $onlyvisible=true, $statusset = -1) {
    global $DB;

    // Set selector.
    $params = array('aid' => $attid);
    $setsql = '';
    if ($statusset >= 0) {
        $params['statusset'] = $statusset;
        $setsql = ' AND setnumber = :statusset ';
    }

    if ($onlyvisible) {
        $statuses = $DB->get_records_select('eabcattendance_statuses', "eabcattendanceid = :aid AND visible = 1 AND deleted = 0 $setsql",
                                            $params, 'setnumber ASC, grade DESC');
    } else {
        $statuses = $DB->get_records_select('eabcattendance_statuses', "eabcattendanceid = :aid AND deleted = 0 $setsql",
                                            $params, 'setnumber ASC, grade DESC');
    }

    return $statuses;
}

/**
 * Get the name of the status set.
 *
 * @param int $attid
 * @param int $statusset
 * @param bool $includevalues
 * @return string
 */
function eabcattendance_get_setname($attid, $statusset, $includevalues = true) {
    $statusname = get_string('statusset', 'mod_eabcattendance', $statusset + 1);
    if ($includevalues) {
        $statuses = eabcattendance_get_statuses($attid, true, $statusset);
        $statusesout = array();
        foreach ($statuses as $status) {
            $statusesout[] = $status->acronym;
        }
        if ($statusesout) {
            if (count($statusesout) > 6) {
                $statusesout = array_slice($statusesout, 0, 6);
                $statusesout[] = '...';
            }
            $statusesout = implode(' ', $statusesout);
            $statusname .= ' ('.$statusesout.')';
        }
    }

    return $statusname;
}

/**
 * Get users courses and the relevant eabcattendances.
 *
 * @param int $userid
 * @return array
 */
function eabcattendance_get_user_courses_eabcattendances($userid) {
    global $DB;

    $usercourses = enrol_get_users_courses($userid);

    list($usql, $uparams) = $DB->get_in_or_equal(array_keys($usercourses), SQL_PARAMS_NAMED, 'cid0');

    $sql = "SELECT att.id as attid, att.course as courseid, course.fullname as coursefullname,
                   course.startdate as coursestartdate, att.name as attname, att.grade as attgrade
              FROM {eabcattendance} att
              JOIN {course} course
                   ON att.course = course.id
             WHERE att.course $usql
          ORDER BY coursefullname ASC, attname ASC";

    $params = array_merge($uparams, array('uid' => $userid));

    return $DB->get_records_sql($sql, $params);
}

/**
 * Used to calculate a fraction based on the part and total values
 *
 * @param float $part - part of the total value
 * @param float $total - total value.
 * @return float the calculated fraction.
 */
function eabcattendance_calc_fraction($part, $total) {
    if ($total == 0) {
        return 0;
    } else {
        return $part / $total;
    }
}

/**
 * Check to see if statusid in use to help prevent deletion etc.
 *
 * @param integer $statusid
 */
function eabcattendance_has_logs_for_status($statusid) {
    global $DB;
    return $DB->record_exists('eabcattendance_log', array('statusid' => $statusid));
}

/**
 * Helper function to add sessiondate_selector to add/update forms.
 *
 * @param MoodleQuickForm $mform
 */
function eabcattendance_form_sessiondate_selector (MoodleQuickForm $mform) {

    $mform->addElement('date_selector', 'sessiondate', get_string('sessiondate', 'eabcattendance'));

    for ($i = 0; $i <= 23; $i++) {
        $hours[$i] = sprintf("%02d", $i);
    }
    for ($i = 0; $i < 60; $i += 5) {
        $minutes[$i] = sprintf("%02d", $i);
    }

    $sesendtime = array();
    $sesendtime[] =& $mform->createElement('static', 'from', '', get_string('from', 'eabcattendance'));
    $sesendtime[] =& $mform->createElement('select', 'starthour', get_string('hour', 'form'), $hours, false, true);
    $sesendtime[] =& $mform->createElement('select', 'startminute', get_string('minute', 'form'), $minutes, false, true);
    $sesendtime[] =& $mform->createElement('static', 'to', '', get_string('to', 'eabcattendance'));
    $sesendtime[] =& $mform->createElement('select', 'endhour', get_string('hour', 'form'), $hours, false, true);
    $sesendtime[] =& $mform->createElement('select', 'endminute', get_string('minute', 'form'), $minutes, false, true);
    $mform->addGroup($sesendtime, 'sestime', get_string('time', 'eabcattendance'), array(' '), true);
}

/**
 * Count the number of status sets that exist for this instance.
 *
 * @param int $eabcattendanceid
 * @return int
 */
function eabcattendance_get_max_statusset($eabcattendanceid) {
    global $DB;

    $max = $DB->get_field_sql('SELECT MAX(setnumber) FROM {eabcattendance_statuses} WHERE eabcattendanceid = ? AND deleted = 0',
        array($eabcattendanceid));
    if ($max) {
        return $max;
    }
    return 0;
}

/**
 * Returns the maxpoints for each statusset
 *
 * @param array $statuses
 * @return array
 */
function eabcattendance_get_statusset_maxpoints($statuses) {
    $statussetmaxpoints = array();
    foreach ($statuses as $st) {
        if (!isset($statussetmaxpoints[$st->setnumber])) {
            $statussetmaxpoints[$st->setnumber] = $st->grade;
        }
    }
    return $statussetmaxpoints;
}

/**
 * Update user grades
 *
 * @param mod_eabcattendance_structure|stdClass $eabcattendance
 * @param array $userids
 */
function eabcattendance_update_users_grade($eabcattendance, $userids=array()) {
    global $DB;

    if (empty($eabcattendance->grade)) {
        return false;
    }

    list($course, $cm) = get_course_and_cm_from_instance($eabcattendance->id, 'eabcattendance');

    $summary = new mod_eabcattendance_summary($eabcattendance->id, $userids);

    if (empty($userids)) {
        $context = context_module::instance($cm->id);
        $userids = array_keys(get_enrolled_users($context, 'mod/eabcattendance:canbelisted', 0, 'u.id'));
    }

    if ($eabcattendance->grade < 0) {
        $dbparams = array('id' => -($eabcattendance->grade));
        $scale = $DB->get_record('scale', $dbparams);
        $scalearray = explode(',', $scale->scale);
        $eabcattendancegrade = count($scalearray);
    } else {
        $eabcattendancegrade = $eabcattendance->grade;
    }

    $grades = array();
    foreach ($userids as $userid) {
        $grades[$userid] = new stdClass();
        $grades[$userid]->userid = $userid;

        if ($summary->has_taken_sessions($userid)) {
            $usersummary = $summary->get_taken_sessions_summary_for($userid);
            $grades[$userid]->rawgrade = $usersummary->takensessionspercentage * $eabcattendancegrade;
        } else {
            $grades[$userid]->rawgrade = null;
        }
    }

    return grade_update('mod/eabcattendance', $course->id, 'mod', 'eabcattendance', $eabcattendance->id, 0, $grades);
}

/**
 * Add an eabcattendance status variable
 *
 * @param stdClass $status
 * @return bool
 */
function eabcattendance_add_status($status) {
    global $DB;
    if (empty($status->context)) {
        $status->context = context_system::instance();
    }

    if (!empty($status->acronym) && !empty($status->description)) {
        $status->deleted = 0;
        $status->visible = 1;
        $status->setunmarked = 0;

        $id = $DB->insert_record('eabcattendance_statuses', $status);
        $status->id = $id;

        $event = \mod_eabcattendance\event\status_added::create(array(
            'objectid' => $status->eabcattendanceid,
            'context' => $status->context,
            'other' => array('acronym' => $status->acronym,
                             'description' => $status->description,
                             'grade' => $status->grade)));
        if (!empty($status->cm)) {
            $event->add_record_snapshot('course_modules', $status->cm);
        }
        $event->add_record_snapshot('eabcattendance_statuses', $status);
        $event->trigger();
        return true;
    } else {
        return false;
    }
}

/**
 * Remove a status variable from an eabcattendance instance
 *
 * @param stdClass $status
 * @param stdClass $context
 * @param stdClass $cm
 */
function eabcattendance_remove_status($status, $context = null, $cm = null) {
    global $DB;
    if (empty($context)) {
        $context = context_system::instance();
    }
    $DB->set_field('eabcattendance_statuses', 'deleted', 1, array('id' => $status->id));
    $event = \mod_eabcattendance\event\status_removed::create(array(
        'objectid' => $status->id,
        'context' => $context,
        'other' => array(
            'acronym' => $status->acronym,
            'description' => $status->description
        )));
    if (!empty($cm)) {
        $event->add_record_snapshot('course_modules', $cm);
    }
    $event->add_record_snapshot('eabcattendance_statuses', $status);
    $event->trigger();
}

/**
 * Update status variable for a particular Eabcattendance module instance
 *
 * @param stdClass $status
 * @param string $acronym
 * @param string $description
 * @param int $grade
 * @param bool $visible
 * @param stdClass $context
 * @param stdClass $cm
 * @param int $studentavailability
 * @param bool $setunmarked
 * @return array
 */
function eabcattendance_update_status($status, $acronym, $description, $grade, $visible,
                                  $context = null, $cm = null, $studentavailability = null, $setunmarked = false) {
    global $DB;

    if (empty($context)) {
        $context = context_system::instance();
    }

    if (isset($visible)) {
        $status->visible = $visible;
        $updated[] = $visible ? get_string('show') : get_string('hide');
    } else if (empty($acronym) || empty($description)) {
        return array('acronym' => $acronym, 'description' => $description);
    }

    $updated = array();

    if ($acronym) {
        $status->acronym = $acronym;
        $updated[] = $acronym;
    }
    if ($description) {
        $status->description = $description;
        $updated[] = $description;
    }
    if (isset($grade)) {
        $status->grade = $grade;
        $updated[] = $grade;
    }
    if (isset($studentavailability)) {
        if (empty($studentavailability)) {
            if ($studentavailability !== '0') {
                $studentavailability = null;
            }
        }

        $status->studentavailability = $studentavailability;
        $updated[] = $studentavailability;
    }
    if ($setunmarked) {
        $status->setunmarked = 1;
    } else {
        $status->setunmarked = 0;
    }
    $DB->update_record('eabcattendance_statuses', $status);

    $event = \mod_eabcattendance\event\status_updated::create(array(
        'objectid' => $status->eabcattendanceid,
        'context' => $context,
        'other' => array('acronym' => $acronym, 'description' => $description, 'grade' => $grade,
            'updated' => implode(' ', $updated))));
    if (!empty($cm)) {
        $event->add_record_snapshot('course_modules', $cm);
    }
    $event->add_record_snapshot('eabcattendance_statuses', $status);
    $event->trigger();
}

/**
 * Similar to core random_string function but only lowercase letters.
 * designed to make it relatively easy to provide a simple password in class.
 *
 * @param int $length The length of the string to be created.
 * @return string
 */
function eabcattendance_random_string($length=6) {
    $randombytes = random_bytes_emulate($length);
    $pool = 'abcdefghijklmnopqrstuvwxyz';
    $pool .= '0123456789';
    $poollen = strlen($pool);
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $rand = ord($randombytes[$i]);
        $string .= substr($pool, ($rand % ($poollen)), 1);
    }
    return $string;
}

/**
 * Check to see if this session is open for student marking.
 *
 * @param stdclass $sess the session record from eabcattendance_sessions.
 * @param boolean $log - if student cannot mark, generate log event.
 * @return array (boolean, string reason for failure)
 */
function eabcattendance_can_student_mark($sess, $log = true) {
    global $DB, $USER, $OUTPUT;
    $canmark = false;
    $reason = 'closed';
    $attconfig = get_config('eabcattendance');
    if (!empty($attconfig->studentscanmark) && !empty($sess->studentscanmark)) {
        if (empty($attconfig->studentscanmarksessiontime)) {
            $canmark = true;
            $reason = '';
        } else {
            $duration = $sess->duration;
            if (empty($duration)) {
                $duration = $attconfig->studentscanmarksessiontimeend * 60;
            }
            if ($sess->sessdate < time() && time() < ($sess->sessdate + $duration)) {
                $canmark = true;
                $reason = '';
            }
        }
    }
    // Check if another student has marked eabcattendance from this IP address recently.
    if ($canmark && !empty($sess->preventsharedip)) {
        if ($sess->preventsharedip == EABCATTENDANCE_SHAREDIP_MINUTES) {
            $time = time() - ($sess->preventsharediptime * 60);
            $sql = 'sessionid = ? AND studentid <> ? AND timetaken > ? AND ipaddress = ?';
            $params = array($sess->id, $USER->id, $time, getremoteaddr());
            $record = $DB->get_record_select('eabcattendance_log', $sql, $params);
        } else {
            // Assume EABCATTENDANCE_SHAREDIP_FORCED.
            $sql = 'sessionid = ? AND studentid <> ? ipaddress = ?';
            $params = array($sess->id, $USER->id, getremoteaddr());
            $record = $DB->get_record_select('eabcattendance_log', $sql, $params);
        }

        if (!empty($record)) {
            $canmark = false;
            $reason = 'preventsharederror';
            if ($log) {
                // Trigger an ip_shared event.
                $eabcattendanceid = $DB->get_field('eabcattendance_sessions', 'eabcattendanceid', array('id' => $record->sessionid));
                $cm = get_coursemodule_from_instance('eabcattendance', $eabcattendanceid);
                $event = \mod_eabcattendance\event\session_ip_shared::create(array(
                    'objectid' => 0,
                    'context' => \context_module::instance($cm->id),
                    'other' => array(
                        'sessionid' => $record->sessionid,
                        'otheruser' => $record->studentid
                    )
                ));

                $event->trigger();
            }
        }
    }
    return array($canmark, $reason);
}

/**
 * Generate worksheet for Eabcattendance export
 *
 * @param stdclass $data The data for the report
 * @param string $filename The name of the file
 * @param string $format excel|ods
 *
 */
function eabcattendance_exporttotableed($data, $filename, $format) {
    global $CFG;

    if ($format === 'excel') {
        require_once("$CFG->libdir/excellib.class.php");
        $filename .= ".xls";
        $workbook = new MoodleExcelWorkbook("-");
    } else {
        require_once("$CFG->libdir/odslib.class.php");
        $filename .= ".ods";
        $workbook = new MoodleODSWorkbook("-");
    }
    // Sending HTTP headers.
    $workbook->send($filename);
    // Creating the first worksheet.
    $myxls = $workbook->add_worksheet('Eabcattendances');
    // Format types.
    $formatbc = $workbook->add_format();
    $formatbc->set_bold(1);

    $myxls->write(0, 0, get_string('course'), $formatbc);
    $myxls->write(0, 1, $data->course);
    $myxls->write(1, 0, get_string('group'), $formatbc);
    $myxls->write(1, 1, $data->group);

    $i = 3;
    $j = 0;
    foreach ($data->tabhead as $cell) {
        // Merge cells if the heading would be empty (remarks column).
        if (empty($cell)) {
            $myxls->merge_cells($i, $j - 1, $i, $j);
        } else {
            $myxls->write($i, $j, $cell, $formatbc);
        }
        $j++;
    }
    $i++;
    $j = 0;
    foreach ($data->table as $row) {
        foreach ($row as $cell) {
            $myxls->write($i, $j++, $cell);
        }
        $i++;
        $j = 0;
    }
    $workbook->close();
}

/**
 * Generate csv for Eabcattendance export
 *
 * @param stdclass $data The data for the report
 * @param string $filename The name of the file
 *
 */
function eabcattendance_exporttocsv($data, $filename) {
    $filename .= ".txt";

    header("Content-Type: application/download\n");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
    header("Pragma: public");

    echo get_string('course')."\t".$data->course."\n";
    echo get_string('group')."\t".$data->group."\n\n";

    echo implode("\t", $data->tabhead)."\n";
    foreach ($data->table as $row) {
        echo implode("\t", $row)."\n";
    }
}

/**
 * Get session data for form.
 * @param stdClass $formdata moodleform - eabcattendance form.
 * @param mod_eabcattendance_structure $att - used to get eabcattendance level subnet.
 * @return array.
 */
function eabcattendance_construct_sessions_data_for_add($formdata, mod_eabcattendance_structure $att) {
    global $CFG;

    $sesstarttime = $formdata->sestime['starthour'] * HOURSECS + $formdata->sestime['startminute'] * MINSECS;
    $sesendtime = $formdata->sestime['endhour'] * HOURSECS + $formdata->sestime['endminute'] * MINSECS;
    $sessiondate = $formdata->sessiondate + $sesstarttime;
    $duration = $sesendtime - $sesstarttime;
    if (empty(get_config('eabcattendance', 'enablewarnings'))) {
        $absenteereport = get_config('eabcattendance', 'absenteereport_default');
    } else {
        $absenteereport = empty($formdata->absenteereport) ? 0 : 1;
    }

    $now = time();

    if (empty(get_config('eabcattendance', 'studentscanmark'))) {
        $formdata->studentscanmark = 0;
    }

    $calendarevent = 0;
    if (isset($formdata->calendarevent)) { // Calendar event should be created.
        $calendarevent = 1;
    }

    $sessions = array();
    if (isset($formdata->addmultiply)) {
        $startdate = $sessiondate;
        $enddate = $formdata->sessionenddate + DAYSECS; // Because enddate in 0:0am.

        if ($enddate < $startdate) {
            return null;
        }

        // Getting first day of week.
        $sdate = $startdate;
        $dinfo = usergetdate($sdate);
        if ($CFG->calendar_startwday === '0') { // Week start from sunday.
            $startweek = $startdate - $dinfo['wday'] * DAYSECS; // Call new variable.
        } else {
            $wday = $dinfo['wday'] === 0 ? 7 : $dinfo['wday'];
            $startweek = $startdate - ($wday - 1) * DAYSECS;
        }

        $wdaydesc = array(0 => 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');

        while ($sdate < $enddate) {
            if ($sdate < $startweek + WEEKSECS) {
                $dinfo = usergetdate($sdate);
                if (isset($formdata->sdays) && array_key_exists($wdaydesc[$dinfo['wday']], $formdata->sdays)) {
                    $sess = new stdClass();
                    $sess->sessdate = make_timestamp($dinfo['year'], $dinfo['mon'], $dinfo['mday'],
                        $formdata->sestime['starthour'], $formdata->sestime['startminute']);
                    $sess->duration = $duration;
                    $sess->descriptionitemid = $formdata->sdescription['itemid'];
                    $sess->description = $formdata->sdescription['text'];
                    $sess->descriptionformat = $formdata->sdescription['format'];

                    $sess->directionitemid = $formdata->sdirection['itemid'];
                    $sess->direction = $formdata->sdirection['text'];
                    $sess->directionformat = $formdata->sdirection['format'];

                    $sess->calendarevent = $calendarevent;
                    $sess->timemodified = $now;
                    $sess->absenteereport = $absenteereport;
                    $sess->studentpassword = '';
                    $sess->includeqrcode = 0;
                    if (isset($formdata->studentscanmark)) { // Students will be able to mark their own eabcattendance.
                        $sess->studentscanmark = 1;
                        if (!empty($formdata->usedefaultsubnet)) {
                            $sess->subnet = $att->subnet;
                        } else {
                            $sess->subnet = $formdata->subnet;
                        }
                        $sess->automark = $formdata->automark;
                        if (isset($formdata->autoassignstatus)) {
                            $sess->autoassignstatus = 1;
                        }
                        $sess->automarkcompleted = 0;
                        if (!empty($formdata->randompassword)) {
                            $sess->studentpassword = eabcattendance_random_string();
                        } else if (!empty($formdata->studentpassword)) {
                            $sess->studentpassword = $formdata->studentpassword;
                        }
                        if (!empty($formdata->includeqrcode)) {
                            $sess->includeqrcode = $formdata->includeqrcode;
                        }
                        if (!empty($formdata->preventsharedip)) {
                            $sess->preventsharedip = $formdata->preventsharedip;
                        }
                        if (!empty($formdata->preventsharediptime)) {
                            $sess->preventsharediptime = $formdata->preventsharediptime;
                        }
                    } else {
                        $sess->subnet = '';
                        $sess->automark = 0;
                        $sess->automarkcompleted = 0;
                        $sess->preventsharedip = 0;
                        $sess->preventsharediptime = '';
                    }
                    $sess->statusset = $formdata->statusset;

                    eabcattendance_fill_groupid($formdata, $sessions, $sess);
                }
                $sdate += DAYSECS;
            } else {
                $startweek += WEEKSECS * $formdata->period;
                $sdate = $startweek;
            }
        }
    } else {
        $sess = new stdClass();
        $sess->sessdate = $sessiondate;
        $sess->duration = $duration;
        $sess->descriptionitemid = $formdata->sdescription['itemid'];
        $sess->description = $formdata->sdescription['text'];
        $sess->descriptionformat = $formdata->sdescription['format'];
        $sess->directionitemid = $formdata->sdirection['itemid'];
        $sess->direction = $formdata->sdirection['text'];
        $sess->directionformat = $formdata->sdirection['format'];
        $sess->calendarevent = $calendarevent;
        $sess->timemodified = $now;
        $sess->studentscanmark = 0;
        $sess->autoassignstatus = 0;
        $sess->subnet = '';
        $sess->studentpassword = '';
        $sess->automark = 0;
        $sess->automarkcompleted = 0;
        $sess->absenteereport = $absenteereport;
        $sess->includeqrcode = 0;

        if (isset($formdata->studentscanmark) && !empty($formdata->studentscanmark)) {
            // Students will be able to mark their own eabcattendance.
            $sess->studentscanmark = 1;
            if (isset($formdata->autoassignstatus) && !empty($formdata->autoassignstatus)) {
                $sess->autoassignstatus = 1;
            }
            if (!empty($formdata->randompassword)) {
                $sess->studentpassword = eabcattendance_random_string();
            } else if (!empty($formdata->studentpassword)) {
                $sess->studentpassword = $formdata->studentpassword;
            }
            if (!empty($formdata->includeqrcode)) {
                $sess->includeqrcode = $formdata->includeqrcode;
            }
            if (!empty($formdata->usedefaultsubnet)) {
                $sess->subnet = $att->subnet;
            } else {
                $sess->subnet = $formdata->subnet;
            }

            if (!empty($formdata->automark)) {
                $sess->automark = $formdata->automark;
            }
            if (!empty($formdata->preventsharedip)) {
                $sess->preventsharedip = $formdata->preventsharedip;
            }
            if (!empty($formdata->preventsharediptime)) {
                $sess->preventsharediptime = $formdata->preventsharediptime;
            }
        }
        $sess->statusset = $formdata->statusset;

        eabcattendance_fill_groupid($formdata, $sessions, $sess);
    }

    return $sessions;
}

/**
 * Helper function for eabcattendance_construct_sessions_data_for_add().
 *
 * @param stdClass $formdata
 * @param stdClass $sessions
 * @param stdClass $sess
 */
function eabcattendance_fill_groupid($formdata, &$sessions, $sess) {
    if ($formdata->sessiontype == mod_eabcattendance_structure::SESSION_COMMON) {
        $sess = clone $sess;
        $sess->groupid = 0;
        $sessions[] = $sess;
    } else {
        foreach ($formdata->groups as $groupid) {
            $sess = clone $sess;
            $sess->groupid = $groupid;
            $sessions[] = $sess;
        }
    }
}

/**
 * Generates a summary of points for the courses selected.
 *
 * @param array $courseids optional list of courses to return
 * @param string $orderby - optional order by param
 * @return stdClass
 */
function eabcattendance_course_users_points($courseids = array(), $orderby = '') {
    global $DB;

    $where = '';
    $params = array();
    $where .= ' AND ats.sessdate < :enddate ';
    $params['enddate'] = time();

    $joingroup = 'LEFT JOIN {groups_members} gm ON (gm.userid = atl.studentid AND gm.groupid = ats.groupid)';
    $where .= ' AND (ats.groupid = 0 or gm.id is NOT NULL)';

    if (!empty($courseids)) {
        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $where .= ' AND c.id ' . $insql;
        $params = array_merge($params, $inparams);
    }

    $sql = "SELECT courseid, coursename, sum(points) / sum(maxpoints) as percentage FROM (
SELECT a.id, a.course as courseid, c.fullname as coursename, atl.studentid AS userid, COUNT(DISTINCT ats.id) AS numtakensessions,
                        SUM(stg.grade) AS points, SUM(stm.maxgrade) AS maxpoints
                   FROM {eabcattendance_sessions} ats
                   JOIN {eabcattendance} a ON a.id = ats.eabcattendanceid
                   JOIN {course} c ON c.id = a.course
                   JOIN {eabcattendance_log} atl ON (atl.sessionid = ats.id)
                   JOIN {eabcattendance_statuses} stg ON (stg.id = atl.statusid AND stg.deleted = 0 AND stg.visible = 1)
                   JOIN (SELECT eabcattendanceid, setnumber, MAX(grade) AS maxgrade
                           FROM {eabcattendance_statuses}
                          WHERE deleted = 0
                            AND visible = 1
                         GROUP BY eabcattendanceid, setnumber) stm
                     ON (stm.setnumber = ats.statusset AND stm.eabcattendanceid = ats.eabcattendanceid)
                  {$joingroup}
                  WHERE ats.sessdate >= c.startdate
                    AND ats.lasttaken != 0
                    {$where}
                GROUP BY a.id, a.course, c.fullname, atl.studentid
                ) p GROUP by courseid, coursename {$orderby}";

    return $DB->get_records_sql($sql, $params);
}

/**
 * Generates a list of users flagged absent.
 *
 * @param array $courseids optional list of courses to return
 * @param string $orderby how to order results.
 * @param bool $allfornotify get notification list for scheduled task.
 * @return stdClass
 */
function eabcattendance_get_users_to_notify($courseids = array(), $orderby = '', $allfornotify = false) {
    global $DB;

    $joingroup = 'LEFT JOIN {groups_members} gm ON (gm.userid = atl.studentid AND gm.groupid = ats.groupid)';
    $where = ' AND (ats.groupid = 0 or gm.id is NOT NULL)';
    $having = '';
    $params = array();

    if (!empty($courseids)) {
        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $where .= ' AND c.id ' . $insql;
        $params = array_merge($params, $inparams);
    }
    if ($allfornotify) {
        // Exclude warnings that have already sent the max num.
        $having .= ' AND n.maxwarn > COUNT(DISTINCT ns.id) ';
    }

    $unames = get_all_user_name_fields(true);
    $unames2 = get_all_user_name_fields(true, 'u');

    $idfield = $DB->sql_concat('cm.id', 'atl.studentid', 'n.id');
    $sql = "SELECT {$idfield} as uniqueid, a.id as aid, {$unames2}, a.name as aname, cm.id as cmid, c.id as courseid,
                    c.fullname as coursename, atl.studentid AS userid, n.id as notifyid, n.warningpercent, n.emailsubject,
                    n.emailcontent, n.emailcontentformat, n.emailuser, n.thirdpartyemails, n.warnafter, n.maxwarn,
                     COUNT(DISTINCT ats.id) AS numtakensessions, SUM(stg.grade) AS points, SUM(stm.maxgrade) AS maxpoints,
                      COUNT(DISTINCT ns.id) as nscount, MAX(ns.timesent) as timesent,
                      SUM(stg.grade) / SUM(stm.maxgrade) AS percent
                   FROM {eabcattendance_sessions} ats
                   JOIN {eabcattendance} a ON a.id = ats.eabcattendanceid
                   JOIN {course_modules} cm ON cm.instance = a.id
                   JOIN {course} c on c.id = cm.course
                   JOIN {modules} md ON md.id = cm.module AND md.name = 'eabcattendance'
                   JOIN {eabcattendance_log} atl ON (atl.sessionid = ats.id)
                   JOIN {user} u ON (u.id = atl.studentid)
                   JOIN {eabcattendance_statuses} stg ON (stg.id = atl.statusid AND stg.deleted = 0 AND stg.visible = 1)
                   JOIN {eabcattendance_warning} n ON n.idnumber = a.id
                   LEFT JOIN {eabcattendance_warning_done} ns ON ns.notifyid = n.id AND ns.userid = atl.studentid
                   JOIN (SELECT eabcattendanceid, setnumber, MAX(grade) AS maxgrade
                           FROM {eabcattendance_statuses}
                          WHERE deleted = 0
                            AND visible = 1
                         GROUP BY eabcattendanceid, setnumber) stm
                     ON (stm.setnumber = ats.statusset AND stm.eabcattendanceid = ats.eabcattendanceid)
                  {$joingroup}
                  WHERE ats.absenteereport = 1 {$where}
                GROUP BY uniqueid, a.id, a.name, a.course, c.fullname, atl.studentid, n.id, n.warningpercent,
                         n.emailsubject, n.emailcontent, n.emailcontentformat, n.warnafter, n.maxwarn,
                         n.emailuser, n.thirdpartyemails, cm.id, c.id, {$unames2}, ns.userid
                HAVING n.warnafter <= COUNT(DISTINCT ats.id) AND n.warningpercent > ((SUM(stg.grade) / SUM(stm.maxgrade)) * 100)
                {$having}
                      {$orderby}";

    if (!$allfornotify) {
        $idfield = $DB->sql_concat('cmid', 'userid');
        // Only show one record per eabcattendance for teacher reports.
        $sql = "SELECT DISTINCT {$idfield} as id, {$unames}, aid, cmid, courseid, aname, coursename, userid,
                        numtakensessions, percent, MAX(timesent) as timesent
              FROM ({$sql}) as m
         GROUP BY id, aid, cmid, courseid, aname, userid, numtakensessions,
                  percent, coursename, {$unames} {$orderby}";
    }

    return $DB->get_records_sql($sql, $params);

}

/**
 * Template variables into place in supplied email content.
 *
 * @param object $record db record of details
 * @return array - the content of the fields after templating.
 */
function eabcattendance_template_variables($record) {
    $templatevars = array(
        '/%coursename%/' => $record->coursename,
        '/%courseid%/' => $record->courseid,
        '/%userfirstname%/' => $record->firstname,
        '/%userlastname%/' => $record->lastname,
        '/%userid%/' => $record->userid,
        '/%warningpercent%/' => $record->warningpercent,
        '/%eabcattendancename%/' => $record->aname,
        '/%cmid%/' => $record->cmid,
        '/%numtakensessions%/' => $record->numtakensessions,
        '/%points%/' => $record->points,
        '/%maxpoints%/' => $record->maxpoints,
        '/%percent%/' => $record->percent,
    );
    $extrauserfields = get_all_user_name_fields();
    foreach ($extrauserfields as $extra) {
        $templatevars['/%'.$extra.'%/'] = $record->$extra;
    }
    $patterns = array_keys($templatevars); // The placeholders which are to be replaced.
    $replacements = array_values($templatevars); // The values which are to be templated in for the placeholders.
    // Array to describe which fields in reengagement object should have a template replacement.
    $replacementfields = array('emailsubject', 'emailcontent');

    // Replace %variable% with relevant value everywhere it occurs in reengagement->field.
    foreach ($replacementfields as $field) {
        $record->$field = preg_replace($patterns, $replacements, $record->$field);
    }
    return $record;
}

/**
 * Find highest available status for a user.
 *
 * @param mod_eabcattendance_structure $att eabcattendance structure
 * @param stdclass $attforsession eabcattendance_session record.
 * @return bool/int
 */
function eabcattendance_session_get_highest_status(mod_eabcattendance_structure $att, $attforsession) {
    // Find the status to set here.
    $statuses = $att->get_statuses();
    $highestavailablegrade = 0;
    $highestavailablestatus = new stdClass();
    foreach ($statuses as $status) {
        if ($status->studentavailability === '0') {
            // This status is never available to students.
            continue;
        }
        if (!empty($status->studentavailability)) {
            $toolateforstatus = (($attforsession->sessdate + ($status->studentavailability * 60)) < time());
            if ($toolateforstatus) {
                continue;
            }
        }
        // This status is available to the student.
        if ($status->grade > $highestavailablegrade) {
            // This is the most favourable grade so far; save it.
            $highestavailablegrade = $status->grade;
            $highestavailablestatus = $status;
        }
    }
    if (empty($highestavailablestatus)) {
        return false;
    }
    return $highestavailablestatus->id;
}

/**
 * Get available automark options.
 *
 * @return array
 */
function eabcattendance_get_automarkoptions() {
    $options = array();
    $options[EABCATTENDANCE_AUTOMARK_DISABLED] = get_string('noautomark', 'eabcattendance');
    if (strpos(get_config('tool_log', 'enabled_stores'), 'logstore_standard') !== false) {
        $options[EABCATTENDANCE_AUTOMARK_ALL] = get_string('automarkall', 'eabcattendance');
    }
    $options[EABCATTENDANCE_AUTOMARK_CLOSE] = get_string('automarkclose', 'eabcattendance');
    return $options;
}

/**
 * Get available sharedip options.
 *
 * @return array
 */
function eabcattendance_get_sharedipoptions() {
    $options = array();
    $options[EABCATTENDANCE_SHAREDIP_DISABLED] = get_string('no');
    $options[EABCATTENDANCE_SHAREDIP_FORCE] = get_string('yes');
    $options[EABCATTENDANCE_SHAREDIP_MINUTES] = get_string('setperiod', 'eabcattendance');

    return $options;
}

/**
 * Used to print simple time - 1am instead of 1:00am.
 *
 * @param int $time - unix timestamp.
 */
function eabcattendance_strftimehm($time) {
    $mins = userdate($time, '%M');

    if ($mins == '00') {
        $format = get_string('strftimeh', 'eabcattendance');
    } else {
        $format = get_string('strftimehm', 'eabcattendance');
    }

    $userdate = userdate($time, $format);

    // Some Lang packs use %p to suffix with AM/PM but not all strftime support this.
    // Check if %p is in use and make sure it's being respected.
    if (stripos($format, '%p')) {
        // Check if $userdate did something with %p by checking userdate against the same format without %p.
        $formatwithoutp = str_ireplace('%p', '', $format);
        if (userdate($time, $formatwithoutp) == $userdate) {
            // The date is the same with and without %p - we have a problem.
            if (userdate($time, '%H') > 11) {
                $userdate .= 'pm';
            } else {
                $userdate .= 'am';
            }
        }
        // Some locales and O/S don't respect correct intended case of %p vs %P
        // This can cause problems with behat which expects AM vs am.
        if (strpos($format, '%p')) { // Should be upper case according to PHP spec.
            $userdate = str_replace('am', 'AM', $userdate);
            $userdate = str_replace('pm', 'PM', $userdate);
        }
    }

    return $userdate;
}

/**
 * Used to print simple time - 1am instead of 1:00am.
 *
 * @param int $datetime - unix timestamp.
 * @param int $duration - number of seconds.
 */
function eabcattendance_construct_session_time($datetime, $duration) {
    $starttime = eabcattendance_strftimehm($datetime);
    $endtime = eabcattendance_strftimehm($datetime + $duration);

    return $starttime . ($duration > 0 ? ' - ' . $endtime : '');
}

/**
 * Used to print session time.
 *
 * @param int $datetime - unix timestamp.
 * @param int $duration - number of seconds duration.
 * @return string.
 */
function eabcatt_construct_session_full_date_time($datetime, $duration) {
    $sessinfo = userdate($datetime, get_string('strftimedmyw', 'eabcattendance'));
    $sessinfo .= ' '.eabcattendance_construct_session_time($datetime, $duration);

    return $sessinfo;
}