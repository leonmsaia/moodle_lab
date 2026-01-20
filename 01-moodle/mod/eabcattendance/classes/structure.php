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
 * Class definition for mod_eabcattendance_structure
 *
 * @package   mod_eabcattendance
 * @copyright  2016 Dan Marsden http://danmarsden.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/calendar_helpers.php');

/**
 * Main class with all eabcattendance related info.
 *
 * @copyright  2016 Dan Marsden http://danmarsden.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_eabcattendance_structure {
    /** Common sessions */
    const SESSION_COMMON        = 0;
    /** Group sessions */
    const SESSION_GROUP         = 1;

    /** @var stdclass course module record */
    public $cm;

    /** @var stdclass course record */
    public $course;

    /** @var stdclass context object */
    public $context;

    /** @var int eabcattendance instance identifier */
    public $id;

    /** @var string eabcattendance activity name */
    public $name;

    /** @var float number (10, 5) unsigned, the maximum grade for eabcattendance */
    public $grade;

    /** @var int last time eabcattendance was modified - used for global search */
    public $timemodified;

    /** @var string required field for activity modules and searching */
    public $intro;

    /** @var int format of the intro (see above) */
    public $introformat;

    /** @var array current page parameters */
    public $pageparams;

    /** @var string subnets (IP range) for student self selection. */
    public $subnet;

    /** @var string subnets (IP range) for student self selection. */
    public $automark;

    /** @var boolean flag set when automarking is complete. */
    public $automarkcompleted;

    /** @var int Define if extra user details should be shown in reports */
    public $showextrauserdetails;

    /** @var int Define if session details should be shown in reports */
    public $showsessiondetails;

    /** @var int Position for the session detail columns related to summary columns.*/
    public $sessiondetailspos;

    /** @var int groupmode  */
    private $groupmode;

    /** @var  array */
    private $statuses;
    /** @var  array Cache list of all statuses (not just one used by current session). */
    private $allstatuses;

    /** @var array of sessionid. */
    private $sessioninfo = array();

    /** @var float number [0..1], the threshold for student to be shown at low grade report */
    private $lowgradethreshold;


    /**
     * Initializes the eabcattendance API instance using the data from DB
     *
     * Makes deep copy of all passed records properties. Replaces integer $course attribute
     * with a full database record (course should not be stored in instances table anyway).
     *
     * @param stdClass $dbrecord Attandance instance data from {eabcattendance} table
     * @param stdClass $cm       Course module record as returned by {@link get_coursemodule_from_id()}
     * @param stdClass $course   Course record from {course} table
     * @param stdClass $context  The context of the workshop instance
     * @param stdClass $pageparams
     */
    public function __construct(stdClass $dbrecord, stdClass $cm, stdClass $course, stdClass $context=null, $pageparams=null) {
        global $DB;

        foreach ($dbrecord as $field => $value) {
            if (property_exists('mod_eabcattendance_structure', $field)) {
                $this->{$field} = $value;
            } else {
                throw new coding_exception('The eabcattendance table has a field with no property in the eabcattendance class');
            }
        }
        $this->cm           = $cm;
        $this->course       = $course;
        if (is_null($context)) {
            $this->context = context_module::instance($this->cm->id);
        } else {
            $this->context = $context;
        }

        $this->pageparams = $pageparams;

        if (isset($pageparams->showextrauserdetails) && $pageparams->showextrauserdetails != $this->showextrauserdetails) {
            $DB->set_field('eabcattendance', 'showextrauserdetails', $pageparams->showextrauserdetails, array('id' => $this->id));
        }
        if (isset($pageparams->showsessiondetails) && $pageparams->showsessiondetails != $this->showsessiondetails) {
            $DB->set_field('eabcattendance', 'showsessiondetails', $pageparams->showsessiondetails, array('id' => $this->id));
        }
        if (isset($pageparams->sessiondetailspos) && $pageparams->sessiondetailspos != $this->sessiondetailspos) {
            $DB->set_field('eabcattendance', 'sessiondetailspos', $pageparams->sessiondetailspos, array('id' => $this->id));
        }
    }

    /**
     * Get group mode.
     *
     * @return int
     */
    public function get_group_mode() {
        if (is_null($this->groupmode)) {
            $this->groupmode = groups_get_activity_groupmode($this->cm, $this->course);
        }
        return $this->groupmode;
    }

    /**
     * Returns current sessions for this eabcattendance
     *
     * Fetches data from {eabcattendance_sessions}
     *
     * @return array of records or an empty array
     */
    public function get_current_sessions() {
        global $DB;

        $today = time(); // Because we compare with database, we don't need to use usertime().

        $sql = "SELECT *
                  FROM {eabcattendance_sessions}
                 WHERE :time BETWEEN sessdate AND (sessdate + duration)
                   AND eabcattendanceid = :aid";
        $params = array(
            'time'  => $today,
            'aid'   => $this->id);

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Returns today sessions for this eabcattendance
     *
     * Fetches data from {eabcattendance_sessions}
     *
     * @return array of records or an empty array
     */
    public function get_today_sessions() {
        global $DB;

        $start = usergetmidnight(time());
        $end = $start + DAYSECS;

        $sql = "SELECT *
                  FROM {eabcattendance_sessions}
                 WHERE sessdate >= :start AND sessdate < :end
                   AND eabcattendanceid = :aid";
        $params = array(
            'start' => $start,
            'end'   => $end,
            'aid'   => $this->id);

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Returns today sessions suitable for copying eabcattendance log
     *
     * Fetches data from {eabcattendance_sessions}
     * @param stdClass $sess
     * @return array of records or an empty array
     */
    public function get_today_sessions_for_copy($sess) {
        global $DB;

        $start = usergetmidnight($sess->sessdate);

        $sql = "SELECT *
                  FROM {eabcattendance_sessions}
                 WHERE sessdate >= :start AND sessdate <= :end AND
                       (groupid = 0 OR groupid = :groupid) AND
                       lasttaken > 0 AND eabcattendanceid = :aid";
        $params = array(
            'start'     => $start,
            'end'       => $sess->sessdate,
            'groupid'   => $sess->groupid,
            'aid'       => $this->id);

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Returns count of hidden sessions for this eabcattendance
     *
     * Fetches data from {eabcattendance_sessions}
     *
     * @return count of hidden sessions
     */
    public function get_hidden_sessions_count() {
        global $DB;

        $where = "eabcattendanceid = :aid AND sessdate < :csdate";
        $params = array(
            'aid'   => $this->id,
            'csdate' => $this->course->startdate);

        return $DB->count_records_select('eabcattendance_sessions', $where, $params);
    }

    /**
     * Returns the hidden sessions for this eabcattendance
     *
     * Fetches data from {eabcattendance_sessions}
     *
     * @return hidden sessions
     */
    public function get_hidden_sessions() {
        global $DB;

        $where = "eabcattendanceid = :aid AND sessdate < :csdate";
        $params = array(
            'aid'   => $this->id,
            'csdate' => $this->course->startdate);

        return $DB->get_records_select('eabcattendance_sessions', $where, $params);
    }

    /**
     * Get filtered sessions.
     *
     * @return array
     */
    public function get_filtered_sessions() {
        global $DB;

        if ($this->pageparams->startdate && $this->pageparams->enddate) {
            $where = "eabcattendanceid = :aid AND sessdate >= :csdate AND sessdate >= :sdate AND sessdate < :edate";
        } else if ($this->pageparams->enddate) {
            $where = "eabcattendanceid = :aid AND sessdate >= :csdate AND sessdate < :edate";
        } else {
            $where = "eabcattendanceid = :aid AND sessdate >= :csdate";
        }

        if ($this->pageparams->get_current_sesstype() > mod_eabcattendance_page_with_filter_controls::SESSTYPE_ALL) {
            $where .= " AND (groupid = :cgroup OR groupid = 0)";
        }
        $params = array(
            'aid'       => $this->id,
            'csdate'    => $this->course->startdate,
            'sdate'     => $this->pageparams->startdate,
            'edate'     => $this->pageparams->enddate,
            'cgroup'    => $this->pageparams->get_current_sesstype());
        $sessions = $DB->get_records_select('eabcattendance_sessions', $where, $params, 'sessdate asc');
        $statussetmaxpoints = eabcattendance_get_statusset_maxpoints($this->get_statuses(true, true));
        foreach ($sessions as $sess) {
            if (empty($sess->description)) {
                $sess->description = get_string('nodescription', 'eabcattendance');
            } else {
                $sess->description = file_rewrite_pluginfile_urls($sess->description,
                    'pluginfile.php', $this->context->id, 'mod_eabcattendance', 'session', $sess->id);
            }
            $sess->maxpoints = $statussetmaxpoints[$sess->statusset];
        }

        return $sessions;
    }

    /**
     * Get manage url.
     * @param array $params
     * @return moodle_url of manage.php for eabcattendance instance
     */
    public function url_manage($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/manage.php', $params);
    }

    /**
     * Get manage temp users url.
     * @param array $params optional
     * @return moodle_url of tempusers.php for eabcattendance instance
     */
    public function url_managetemp($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/tempusers.php', $params);
    }

    /**
     * Get add users url.
     * @param array $params optional
     * @return moodle_url of adduser.php for eabcattendance instance
     * (06/11/2019 FHS)
     */
    public function url_adduser($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/adduser.php', $params);
    }


    /**
     * Get temp delete url.
     *
     * @param array $params optional
     * @return moodle_url of tempdelete.php for eabcattendance instance
     */
    public function url_tempdelete($params=array()) {
        $params = array_merge(array('id' => $this->cm->id, 'action' => 'delete'), $params);
        return new moodle_url('/mod/eabcattendance/tempedit.php', $params);
    }

    /**
     * Get temp edit url.
     *
     * @param array $params optional
     * @return moodle_url of tempedit.php for eabcattendance instance
     */
    public function url_tempedit($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/tempedit.php', $params);
    }

    /**
     * Get temp merge url
     *
     * @param array $params optional
     * @return moodle_url of tempedit.php for eabcattendance instance
     */
    public function url_tempmerge($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/tempmerge.php', $params);
    }

    /**
     * Get url for sessions.
     * @param array $params
     * @return moodle_url of sessions.php for eabcattendance instance
     */
    public function url_sessions($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/sessions.php', $params);
    }

    /**
     * Get url for upload support attendance.
     * @param array $params
     * @return moodle_url of sessions.php for eabcattendance instance
     */
    public function url_sessions_flags($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/sessions_flags.php', $params);
    }

    /**
     * Get url for flags in attendance.
     * @param array $params
     * @return moodle_url of sessions.php for eabcattendance instance
     */
    public function url_upload_supporta_attendance($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/upload_support_attendance.php', $params);
    }

    /**
     * Get url for flags in attendance.
     * @param array $params
     * @return moodle_url of sessions.php for eabcattendance instance
     */
    public function url_upload_excel_participants_attendance($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/upload_excel_participants_attendance.php', $params);
    }    


    /**
     * Get url for flags in attendance.
     * @param array $params
     * @return moodle_url of sessions.php for eabcattendance instance
     */
    public function url_delete_participants_attendance($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/delete_participants_attendance.php', $params);
    }


    /**
     * Get url for upload support attendance.
     * @param array $params
     * @return moodle_url of sessions.php for eabcattendance instance
     */
    public function url_sessions_download($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/sessions_download.php', $params);
    }
    
    /**
     * Get url for report.
     * @param array $params
     * @return moodle_url of report.php for eabcattendance instance
     */
    public function url_report($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/report.php', $params);
    }

    /**
     * Get url for report.
     * @param array $params
     * @return moodle_url of report.php for eabcattendance instance
     */
    public function url_absentee($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/absentee.php', $params);
    }

    /**
     * Get url for export.
     *
     * @return moodle_url of export.php for eabcattendance instance
     */
    public function url_export() {
        $params = array('id' => $this->cm->id);
        return new moodle_url('/mod/eabcattendance/export.php', $params);
    }

    /**
     * Get preferences url
     * @param array $params
     * @return moodle_url of attsettings.php for eabcattendance instance
     */
    public function url_preferences($params=array()) {
        // Add the statusset params.
        if (isset($this->pageparams->statusset) && !isset($params['statusset'])) {
            $params['statusset'] = $this->pageparams->statusset;
        }
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/preferences.php', $params);
    }

    /**
     * Get preferences url
     * @param array $params
     * @return moodle_url of attsettings.php for eabcattendance instance
     */
    public function url_warnings($params=array()) {
        // Add the statusset params.
        if (isset($this->pageparams->statusset) && !isset($params['statusset'])) {
            $params['statusset'] = $this->pageparams->statusset;
        }
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/warnings.php', $params);
    }

    /**
     * Get take url.
     * @param array $params
     * @return moodle_url of eabcattendances.php for eabcattendance instance
     */
    public function url_take($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/take.php', $params);
    }

    /**
     * Get view url.
     * @param array $params
     * @return moodle_url
     */
    public function url_view($params=array()) {
        $params = array_merge(array('id' => $this->cm->id), $params);
        return new moodle_url('/mod/eabcattendance/view.php', $params);
    }

    /**
     * Add sessions.
     *
     * @param array $sessions
     */
    public function add_sessions($sessions) {
        global $DB;

        foreach ($sessions as $sess) {
            $this->add_session($sess);
        }
    }

    /**
     * @param $sess
     * @return bool|int
     * @throws coding_exception
     * @throws dml_exception
     */
    public function add_session($sess){
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/eabcattendance/locallib.php');
        $sess->eabcattendanceid = $this->id;
        $sess->automarkcompleted = 0;
        if (!isset($sess->automark)) {
            $sess->automark = 0;
        }

        $sess->id = $DB->insert_record('eabcattendance_sessions', $sess);
        $description = file_save_draft_area_files($sess->descriptionitemid, $this->context->id, 'mod_eabcattendance', 'session', $sess->id, array('subdirs' => false, 'maxfiles' => -1, 'maxbytes' => 0), $sess->description);
        $DB->set_field('eabcattendance_sessions', 'description', $description, array('id' => $sess->id));

        $sess->caleventid = 0;
        eabcattendance_create_calendar_event($sess);

        $infoarray = array();
        $infoarray[] = eabcatt_construct_session_full_date_time($sess->sessdate, $sess->duration);

        // Trigger a session added event.
        $event = \mod_eabcattendance\event\session_added::create(array(
                    'objectid' => $this->id,
                    'context' => $this->context,
                    'other' => array('info' => implode(',', $infoarray))
        ));
        $event->add_record_snapshot('course_modules', $this->cm);
        $sess->description = $description;
        $sess->lasttaken = 0;
        $sess->lasttakenby = 0;
        if (!isset($sess->studentscanmark)) {
            $sess->studentscanmark = 0;
        }
        if (!isset($sess->autoassignstatus)) {
            $sess->autoassignstatus = 0;
        }
        if (!isset($sess->studentpassword)) {
            $sess->studentpassword = '';
        }
        if (!isset($sess->subnet)) {
            $sess->subnet = '';
        }

        if (!isset($sess->preventsharedip)) {
            $sess->preventsharedip = 0;
        }

        if (!isset($sess->preventsharediptime)) {
            $sess->preventsharediptime = '';
        }
        if (!isset($sess->includeqrcode)) {
            $sess->includeqrcode = 0;
        }
        $event->add_record_snapshot('eabcattendance_sessions', $sess);
        $event->trigger();

        return $sess->id;
    }

    /**
     * Update session from form.
     *
     * @param stdClass $formdata
     * @param int $sessionid
     */
    public function update_session_from_form_data($formdata, $sessionid) {
        global $DB;

        if (!$sess = $DB->get_record('eabcattendance_sessions', array('id' => $sessionid) )) {
            print_error('No such session in this course');
        }

        $sesstarttime = $formdata->sestime['starthour'] * HOURSECS + $formdata->sestime['startminute'] * MINSECS;
        $sesendtime = $formdata->sestime['endhour'] * HOURSECS + $formdata->sestime['endminute'] * MINSECS;

        $sess->sessdate = $formdata->sessiondate + $sesstarttime;
        $sess->duration = $sesendtime - $sesstarttime;

        $description = file_save_draft_area_files($formdata->sdescription['itemid'],
            $this->context->id, 'mod_eabcattendance', 'session', $sessionid,
            array('subdirs' => false, 'maxfiles' => -1, 'maxbytes' => 0), $formdata->sdescription['text']);
        $sess->description = $description;
        $sess->descriptionformat = $formdata->sdescription['format'];

        /* Direction */
        $direction = file_save_draft_area_files($formdata->sdirection['itemid'],
            $this->context->id, 'mod_eabcattendance', 'session', $sessionid,
            array('subdirs' => false, 'maxfiles' => -1, 'maxbytes' => 0), $formdata->sdirection['text']);
        $sess->direction = $direction;
        $sess->directionformat = $formdata->sdirection['format'];

        $sess->calendarevent = empty($formdata->calendarevent) ? 0 : $formdata->calendarevent;

        $sess->studentscanmark = 0;
        $sess->autoassignstatus = 0;
        $sess->studentpassword = '';
        $sess->subnet = '';
        $sess->automark = 0;
        $sess->automarkcompleted = 0;
        $sess->preventsharedip = 0;
        $sess->preventsharediptime = '';
        $sess->includeqrcode = 0;
        if (!empty(get_config('eabcattendance', 'enablewarnings'))) {
            $sess->absenteereport = empty($formdata->absenteereport) ? 0 : 1;
        }
        if (!empty($formdata->autoassignstatus)) {
            $sess->autoassignstatus = $formdata->autoassignstatus;
        }
        $studentscanmark = get_config('eabcattendance', 'studentscanmark');

        if (!empty($studentscanmark) &&
            !empty($formdata->studentscanmark)) {
            $sess->studentscanmark = $formdata->studentscanmark;
            $sess->studentpassword = $formdata->studentpassword;
            $sess->autoassignstatus = $formdata->autoassignstatus;
            if (!empty($formdata->includeqrcode)) {
                $sess->includeqrcode = $formdata->includeqrcode;
            }
        }
        if (!empty($formdata->usedefaultsubnet)) {
            $sess->subnet = $this->subnet;
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

        $sess->timemodified = time();
        $DB->update_record('eabcattendance_sessions', $sess);

        if (empty($sess->caleventid)) {
             // This shouldn't really happen, but just in case to prevent fatal error.
            eabcattendance_create_calendar_event($sess);
        } else {
            eabcattendance_update_calendar_event($sess);
        }

        $info = eabcatt_construct_session_full_date_time($sess->sessdate, $sess->duration);
        $event = \mod_eabcattendance\event\session_updated::create(array(
            'objectid' => $this->id,
            'context' => $this->context,
            'other' => array('info' => $info, 'sessionid' => $sessionid,
                'action' => mod_eabcattendance_sessions_page_params::ACTION_UPDATE)));
        $event->add_record_snapshot('course_modules', $this->cm);
        $event->add_record_snapshot('eabcattendance_sessions', $sess);
        $event->trigger();
    }

    /**
     * Used to record eabcattendance submitted by the student.
     *
     * @param stdClass $mformdata
     * @return boolean
     */
    public function take_from_student($mformdata) {
        global $DB, $USER;

        $statuses = implode(',', array_keys( (array)$this->get_statuses() ));
        $now = time();

        $record = new stdClass();
        $record->studentid = $USER->id;
        $record->statusid = $mformdata->status;
        $record->statusset = $statuses;
        $record->remarks = get_string('set_by_student', 'mod_eabcattendance');
        $record->sessionid = $mformdata->sessid;
        $record->timetaken = $now;
        $record->takenby = $USER->id;
        $record->ipaddress = getremoteaddr(null);

        $existingeabcattendance = $DB->record_exists('eabcattendance_log',
            array('sessionid' => $mformdata->sessid, 'studentid' => $USER->id));

        if ($existingeabcattendance) {
            // Already recorded do not save.
            return false;
        }

        $logid = $DB->insert_record('eabcattendance_log', $record, false);
        $record->id = $logid;

        // Update the session to show that a register has been taken, or staff may overwrite records.
        $session = $this->get_session_info($mformdata->sessid);
        $session->lasttaken = $now;
        $session->lasttakenby = $USER->id;
        $DB->update_record('eabcattendance_sessions', $session);

        // Update the users grade.
        $this->update_users_grade(array($USER->id));

        /* create url for link in log screen
         * need to set grouptype to 0 to allow take eabcattendance page to be called
         * from report/log page */

        $params = array(
            'sessionid' => $this->pageparams->sessionid,
            'grouptype' => 0);

        // Log the change.
        $event = \mod_eabcattendance\event\eabcattendance_taken_by_student::create(array(
            'objectid' => $this->id,
            'context' => $this->context,
            'other' => $params));
        $event->add_record_snapshot('course_modules', $this->cm);
        $event->add_record_snapshot('eabcattendance_sessions', $session);
        $event->add_record_snapshot('eabcattendance_log', $record);
        $event->trigger();

        return true;
    }

    /**
     * Take eabcattendance from form data.
     *
     * @param stdClass $formdata
     */
    public function take_from_form_data($formdata) {
        global $DB, $USER;
        // TODO: WARNING - $formdata is unclean - comes from direct $_POST - ideally needs a rewrite but we do some cleaning below.
        // This whole function could do with a nice clean up.
        $statuses = implode(',', array_keys( (array)$this->get_statuses() ));
        $now = time();
        $sesslog = array();
        $formdata = (array)$formdata;
        foreach ($formdata as $key => $value) {
            // Look at Remarks field because the user options may not be passed if empty.
            if (substr($key, 0, 7) == 'remarks') {
                $sid = substr($key, 7);
                if (!(is_numeric($sid))) { // Sanity check on $sid.
                    print_error('nonnumericid', 'eabcattendance');
                }
                $sesslog[$sid] = new stdClass();
                $sesslog[$sid]->studentid = $sid; // We check is_numeric on this above.
                if (array_key_exists('user'.$sid, $formdata) && is_numeric($formdata['user' . $sid])) {
                    $sesslog[$sid]->statusid = $formdata['user' . $sid];
                }
                $sesslog[$sid]->statusset = $statuses;
                $sesslog[$sid]->remarks = $value;
                $sesslog[$sid]->sessionid = $this->pageparams->sessionid;
                $sesslog[$sid]->timetaken = $now;
                $sesslog[$sid]->takenby = $USER->id;
            }
        }
        // Get existing session log.
        $dbsesslog = $this->get_session_log($this->pageparams->sessionid);
        foreach ($sesslog as $log) {
            // Don't save a record if no statusid or remark.
            if (!empty($log->statusid) || !empty($log->remarks)) {
                if (array_key_exists($log->studentid, $dbsesslog)) {
                    // Check if anything important has changed before updating record.
                    // Don't update timetaken/takenby records if nothing has changed.
                    if ($dbsesslog[$log->studentid]->remarks <> $log->remarks ||
                        $dbsesslog[$log->studentid]->statusid <> $log->statusid ||
                        $dbsesslog[$log->studentid]->statusset <> $log->statusset) {

                        $log->id = $dbsesslog[$log->studentid]->id;
                        $DB->update_record('eabcattendance_log', $log);
                    }
                } else {
                    $DB->insert_record('eabcattendance_log', $log, false);
                }
                $params = array(
                    'statusset' => $log->statusset,
                    'remarks' => $log->remarks,
                    'sessionid' => $this->pageparams->sessionid,
                    'timetaken' => $now,
                    'statusid' => $log->statusid,
                    'studentid' => $log->studentid,
                    'grouptype' => $this->pageparams->grouptype
                );
                $event = \mod_eabcattendance\event\eabcattendance_taken_by_student_details::create(array(
                            'objectid' => $this->id,
                            'context' => $this->context,
                            'other' => $params));
                $event->trigger();
            }
        }

        $session = $this->get_session_info($this->pageparams->sessionid);
        $session->lasttaken = $now;
        $session->lasttakenby = $USER->id;

        $DB->update_record('eabcattendance_sessions', $session);

        if ($this->grade != 0) {
            $this->update_users_grade(array_keys($sesslog));
        }

        // Create url for link in log screen.
        $params = array(
            'sessionid' => $this->pageparams->sessionid,
            'grouptype' => $this->pageparams->grouptype);
        $event = \mod_eabcattendance\event\eabcattendance_taken::create(array(
            'objectid' => $this->id,
            'context' => $this->context,
            'other' => $params));
        $event->add_record_snapshot('course_modules', $this->cm);
        $event->add_record_snapshot('eabcattendance_sessions', $session);
        $event->trigger();
    }

    /**
     * Get users with enrolment status (Feature request MDL-27591)
     *
     * @param int $groupid
     * @param int $page
     * @return array
     */
    public function get_users($groupid = 0, $page = 1) {
        global $DB;

        $fields = array('username' , 'idnumber' , 'institution' , 'department');
        // Get user identity fields if required - doesn't return original $fields array.
        $extrafields = \core_user\fields::get_identity_fields($this->context);
        $fields = array_merge($fields, $extrafields);

        $userfields = user_picture::fields('u', $fields);

        if (empty($this->pageparams->sort)) {
            $this->pageparams->sort = EABCATT_SORT_DEFAULT;
        }
        if ($this->pageparams->sort == EABCATT_SORT_FIRSTNAME) {
            $orderby = $DB->sql_fullname('u.firstname', 'u.lastname') . ', u.id';
        } else if ($this->pageparams->sort == EABCATT_SORT_LASTNAME) {
            $orderby = 'u.lastname, u.firstname, u.id';
        } else {
            list($orderby, $sortparams) = users_order_by_sql('u');
        }

        if ($page) {
            $usersperpage = $this->pageparams->perpage;
            if (!empty($this->cm->groupingid)) {
                $startusers = ($page - 1) * $usersperpage;
                if ($groupid == 0) {
                    $groups = array_keys(groups_get_all_groups($this->cm->course, 0, $this->cm->groupingid, 'g.id'));
                } else {
                    $groups = $groupid;
                }
                $users = get_users_by_capability($this->context, 'mod/eabcattendance:canbelisted',
                    $userfields.',u.id, u.firstname, u.lastname, u.email',
                    $orderby, $startusers, $usersperpage, $groups,
                    '', false, true);
            } else {
                $startusers = ($page - 1) * $usersperpage;
                $users = get_enrolled_users($this->context, 'mod/eabcattendance:canbelisted', $groupid, $userfields,
                    $orderby, $startusers, $usersperpage);
            }
        } else {
            if (!empty($this->cm->groupingid)) {
                if ($groupid == 0) {
                    $groups = array_keys(groups_get_all_groups($this->cm->course, 0, $this->cm->groupingid, 'g.id'));
                } else {
                    $groups = $groupid;
                }
                $users = get_users_by_capability($this->context, 'mod/eabcattendance:canbelisted',
                    $userfields.',u.id, u.firstname, u.lastname, u.email',
                    $orderby, '', '', $groups,
                    '', false, true);
            } else {
                $users = get_enrolled_users($this->context, 'mod/eabcattendance:canbelisted', $groupid, $userfields, $orderby);
            }
        }

        // Add a flag to each user indicating whether their enrolment is active.
        if (!empty($users)) {
            list($sql, $params) = $DB->get_in_or_equal(array_keys($users), SQL_PARAMS_NAMED, 'usid0');

            // See CONTRIB-4868.
            $mintime = 'MIN(CASE WHEN (ue.timestart > :zerotime) THEN ue.timestart ELSE ue.timecreated END)';
            $maxtime = 'CASE WHEN MIN(ue.timeend) = 0 THEN 0 ELSE MAX(ue.timeend) END';

            // See CONTRIB-3549.
            $sql = "SELECT ue.userid, MIN(ue.status) as status,
                           $mintime AS mintime,
                           $maxtime AS maxtime
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON e.id = ue.enrolid
                     WHERE ue.userid $sql
                           AND e.status = :estatus
                           AND e.courseid = :courseid
                  GROUP BY ue.userid";
            $params += array('zerotime' => 0, 'estatus' => ENROL_INSTANCE_ENABLED, 'courseid' => $this->course->id);
            $enrolments = $DB->get_records_sql($sql, $params);

            foreach ($users as $user) {
                $users[$user->id]->fullname = fullname($user);
                $users[$user->id]->enrolmentstatus = $enrolments[$user->id]->status;
                $users[$user->id]->enrolmentstart = $enrolments[$user->id]->mintime;
                $users[$user->id]->enrolmentend = $enrolments[$user->id]->maxtime;
                $users[$user->id]->type = 'standard'; // Mark as a standard (not a temporary) user.
            }
        }

        // Add the 'temporary' users to this list.
        $tempusers = $DB->get_records('eabcattendance_tempusers', array('courseid' => $this->course->id));
        foreach ($tempusers as $tempuser) {
            $users[$tempuser->studentid] = self::tempuser_to_user($tempuser);
        }

        return $users;
    }

    /**
     * Convert a tempuser record into a user object.
     *
     * @param stdClass $tempuser
     * @return object
     */
    protected static function tempuser_to_user($tempuser) {
        global $CFG;

        $ret = (object)array(
            'id' => $tempuser->studentid,
            'firstname' => $tempuser->fullname,
            'email' => $tempuser->email,
            'username' => '',
            'enrolmentstatus' => 0,
            'enrolmentstart' => 0,
            'enrolmentend' => 0,
            'picture' => 0,
            'type' => 'temporary',
        );
        $allfields = get_all_user_name_fields();
        if (!empty($CFG->showuseridentity)) {
            $allfields = array_merge($allfields, explode(',', $CFG->showuseridentity));
        }

        foreach ($allfields as $namefield) {
            if (!isset($ret->$namefield)) {
                $ret->$namefield = '';
            }
        }

        return $ret;
    }

    /**
     * Get user and include extra info.
     *
     * @param int $userid
     * @return mixed|object
     */
    public function get_user($userid) {
        global $DB;

        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

        // Look for 'temporary' users and return their details from the eabcattendance_tempusers table.
        if ($user->idnumber == 'tempghost') {
            $tempuser = $DB->get_record('eabcattendance_tempusers', array('studentid' => $userid), '*', MUST_EXIST);
            return self::tempuser_to_user($tempuser);
        }

        $user->type = 'standard';

        // See CONTRIB-4868.
        $mintime = 'MIN(CASE WHEN (ue.timestart > :zerotime) THEN ue.timestart ELSE ue.timecreated END)';
        $maxtime = 'CASE WHEN MIN(ue.timeend) = 0 THEN 0 ELSE MAX(ue.timeend) END';

        $sql = "SELECT ue.userid, ue.status,
                       $mintime AS mintime,
                       $maxtime AS maxtime
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE ue.userid = :uid
                       AND e.status = :estatus
                       AND e.courseid = :courseid
              GROUP BY ue.userid, ue.status";
        $params = array('zerotime' => 0, 'uid' => $userid, 'estatus' => ENROL_INSTANCE_ENABLED, 'courseid' => $this->course->id);
        $enrolments = $DB->get_record_sql($sql, $params);
        if (!empty($enrolments)) {
            $user->enrolmentstatus = $enrolments->status;
            $user->enrolmentstart = $enrolments->mintime;
            $user->enrolmentend = $enrolments->maxtime;
        } else {
            $user->enrolmentstatus = '';
            $user->enrolmentstart = 0;
            $user->enrolmentend = 0;
        }

        return $user;
    }

    /**
     * Get possible statuses.
     *
     * @param bool $onlyvisible
     * @param bool $allsets
     * @return array
     */
    public function get_statuses($onlyvisible = true, $allsets = false) {
        if (!isset($this->statuses)) {
            // Get the statuses for the current set only.
            $statusset = 0;
            if (isset($this->pageparams->statusset)) {
                $statusset = $this->pageparams->statusset;
            } else if (isset($this->pageparams->sessionid)) {
                $sessioninfo = $this->get_session_info($this->pageparams->sessionid);
                $statusset = $sessioninfo->statusset;
            }
            $this->statuses = eabcattendance_get_statuses($this->id, $onlyvisible, $statusset);
            $this->allstatuses = eabcattendance_get_statuses($this->id, $onlyvisible);
        }

        // Return all sets, if requested.
        if ($allsets) {
            return $this->allstatuses;
        }
        return $this->statuses;
    }

    /**
     * Get session info.
     * @param int $sessionid
     * @return mixed
     */
    public function get_session_info($sessionid) {
        global $DB;

        if (!array_key_exists($sessionid, $this->sessioninfo)) {
            $this->sessioninfo[$sessionid] = $DB->get_record('eabcattendance_sessions', array('id' => $sessionid));
        }
        if (empty($this->sessioninfo[$sessionid]->description)) {
            $this->sessioninfo[$sessionid]->description = get_string('nodescription', 'eabcattendance');
        } else {
            $this->sessioninfo[$sessionid]->description = file_rewrite_pluginfile_urls($this->sessioninfo[$sessionid]->description,
                'pluginfile.php', $this->context->id, 'mod_eabcattendance', 'session', $this->sessioninfo[$sessionid]->id);
        }
        return $this->sessioninfo[$sessionid];
    }

    /**
     * Get sessions info
     *
     * @param array $sessionids
     * @return array
     */
    public function get_sessions_info($sessionids) {
        global $DB;

        list($sql, $params) = $DB->get_in_or_equal($sessionids);
        $sessions = $DB->get_records_select('eabcattendance_sessions', "id $sql", $params, 'sessdate asc');

        foreach ($sessions as $sess) {
            if (empty($sess->description)) {
                $sess->description = get_string('nodescription', 'eabcattendance');
            } else {
                $sess->description = file_rewrite_pluginfile_urls($sess->description,
                    'pluginfile.php', $this->context->id, 'mod_eabcattendance', 'session', $sess->id);
            }
        }

        return $sessions;
    }

    /**
     * Get log.
     *
     * @param int $sessionid
     * @return array
     */
    public function get_session_log($sessionid) {
        global $DB;

        return $DB->get_records('eabcattendance_log', array('sessionid' => $sessionid), '', 'studentid,statusid,remarks,id,statusset');
    }

    /**
     * Update user grade.
     * @param array $userids
     */
    public function update_users_grade($userids) {
        eabcattendance_update_users_grade($this, $userids);
    }

    /**
     * Get filtered log.
     * @param int $userid
     * @return array
     */
    public function get_user_filtered_sessions_log($userid) {
        global $DB;

        if ($this->pageparams->startdate && $this->pageparams->enddate) {
            $where = "ats.eabcattendanceid = :aid AND ats.sessdate >= :csdate AND
                      ats.sessdate >= :sdate AND ats.sessdate < :edate";
        } else {
            $where = "ats.eabcattendanceid = :aid AND ats.sessdate >= :csdate";
        }
        if ($this->get_group_mode()) {
            $sql = "SELECT ats.id, ats.sessdate, ats.groupid, al.statusid, al.remarks,
                           ats.preventsharediptime, ats.preventsharedip
                  FROM {eabcattendance_sessions} ats
                  JOIN {eabcattendance_log} al ON ats.id = al.sessionid AND al.studentid = :uid
                  LEFT JOIN {groups_members} gm ON gm.userid = al.studentid AND gm.groupid = ats.groupid
                 WHERE $where AND (ats.groupid = 0 or gm.id is NOT NULL)
              ORDER BY ats.sessdate ASC";

            $params = array(
                'uid'       => $userid,
                'aid'       => $this->id,
                'csdate'    => $this->course->startdate,
                'sdate'     => $this->pageparams->startdate,
                'edate'     => $this->pageparams->enddate);

        } else {
            $sql = "SELECT ats.id, ats.sessdate, ats.groupid, al.statusid, al.remarks,
                           ats.preventsharediptime, ats.preventsharedip
                  FROM {eabcattendance_sessions} ats
                  JOIN {eabcattendance_log} al
                    ON ats.id = al.sessionid AND al.studentid = :uid
                 WHERE $where
              ORDER BY ats.sessdate ASC";

            $params = array(
                'uid'       => $userid,
                'aid'       => $this->id,
                'csdate'    => $this->course->startdate,
                'sdate'     => $this->pageparams->startdate,
                'edate'     => $this->pageparams->enddate);
        }
        $sessions = $DB->get_records_sql($sql, $params);

        return $sessions;
    }

    /**
     * Get filtered log extended.
     * @param int $userid
     * @return array
     */
    public function get_user_filtered_sessions_log_extended($userid) {
        global $DB;
        // All taked sessions (including previous groups).

        if ($this->pageparams->startdate && $this->pageparams->enddate) {
            $where = "ats.eabcattendanceid = :aid AND ats.sessdate >= :csdate AND
                      ats.sessdate >= :sdate AND ats.sessdate < :edate";
        } else {
            $where = "ats.eabcattendanceid = :aid AND ats.sessdate >= :csdate";
        }

        // We need to add this concatination so that moodle will use it as the array index that is a string.
        // If the array's index is a number it will not merge entries.
        // It would be better as a UNION query but unfortunatly MS SQL does not seem to support doing a
        // DISTINCT on a the description field.
        $id = $DB->sql_concat(':value', 'ats.id');
        if ($this->get_group_mode()) {
            $sql = "SELECT $id, ats.id, ats.groupid, ats.sessdate, ats.duration, ats.description,
                           al.statusid, al.remarks, ats.studentscanmark, ats.autoassignstatus,
                           ats.preventsharedip, ats.preventsharediptime
                      FROM {eabcattendance_sessions} ats
                RIGHT JOIN {eabcattendance_log} al
                        ON ats.id = al.sessionid AND al.studentid = :uid
                 LEFT JOIN {groups_members} gm ON gm.userid = al.studentid AND gm.groupid = ats.groupid
                     WHERE $where AND (ats.groupid = 0 or gm.id is NOT NULL)
                  ORDER BY ats.sessdate ASC";
        } else {
            $sql = "SELECT $id, ats.id, ats.groupid, ats.sessdate, ats.duration, ats.description, ats.statusset,
                           al.statusid, al.remarks, ats.studentscanmark, ats.autoassignstatus,
                           ats.preventsharedip, ats.preventsharediptime
                      FROM {eabcattendance_sessions} ats
                RIGHT JOIN {eabcattendance_log} al
                        ON ats.id = al.sessionid AND al.studentid = :uid
                     WHERE $where
                  ORDER BY ats.sessdate ASC";
        }

        $params = array(
            'uid'       => $userid,
            'aid'       => $this->id,
            'csdate'    => $this->course->startdate,
            'sdate'     => $this->pageparams->startdate,
            'edate'     => $this->pageparams->enddate,
            'value'     => 'c');
        $sessions = $DB->get_records_sql($sql, $params);

        // All sessions for current groups.

        $groups = array_keys(groups_get_all_groups($this->course->id, $userid));
        $groups[] = 0;
        list($gsql, $gparams) = $DB->get_in_or_equal($groups, SQL_PARAMS_NAMED, 'gid0');

        if ($this->pageparams->startdate && $this->pageparams->enddate) {
            $where = "ats.eabcattendanceid = :aid AND ats.sessdate >= :csdate AND
                      ats.sessdate >= :sdate AND ats.sessdate < :edate AND ats.groupid $gsql";
        } else {
            $where = "ats.eabcattendanceid = :aid AND ats.sessdate >= :csdate AND ats.groupid $gsql";
        }
        $sql = "SELECT $id, ats.id, ats.groupid, ats.sessdate, ats.duration, ats.description, ats.statusset,
                       al.statusid, al.remarks, ats.studentscanmark, ats.autoassignstatus,
                       ats.preventsharedip, ats.preventsharediptime
                  FROM {eabcattendance_sessions} ats
             LEFT JOIN {eabcattendance_log} al
                    ON ats.id = al.sessionid AND al.studentid = :uid
                 WHERE $where
              ORDER BY ats.sessdate ASC";

        $params = array_merge($params, $gparams);
        $sessions = array_merge($sessions, $DB->get_records_sql($sql, $params));

        foreach ($sessions as $sess) {
            if (empty($sess->description)) {
                $sess->description = get_string('nodescription', 'eabcattendance');
            } else {
                $sess->description = file_rewrite_pluginfile_urls($sess->description,
                    'pluginfile.php', $this->context->id, 'mod_eabcattendance', 'session', $sess->id);
            }
        }

        return $sessions;
    }

    /**
     * Delete sessions.
     * @param array $sessionsids
     */
    public function delete_sessions($sessionsids) {
        global $DB;
        if (eabcattendance_existing_calendar_events_ids($sessionsids)) {
            eabcattendance_delete_calendar_events($sessionsids);
        }

        list($sql, $params) = $DB->get_in_or_equal($sessionsids);
        $DB->delete_records_select('eabcattendance_log', "sessionid $sql", $params);
        $DB->delete_records_list('eabcattendance_sessions', 'id', $sessionsids);
        $event = \mod_eabcattendance\event\session_deleted::create(array(
            'objectid' => $this->id,
            'context' => $this->context,
            'other' => array('info' => implode(', ', $sessionsids))));
        $event->add_record_snapshot('course_modules', $this->cm);
        $event->trigger();
    }

    /**
     * Delete user sesion.
     * @param int $userid
     * @param int $groupid
     * @param int $sessionid
     */
    public function delete_user_sesion($studentid, $groupid, $sessionid, $courseid) {
        global $DB,$CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        $endpoint = get_config('eabcattendance', 'endpointdeleteparticipante');

        if(empty($endpoint)) {
            throw new moodle_exception("Debe configurar el Endpoint Eliminar participante");
        }

        $user = $DB->get_record('user', array('id' => $studentid));

        profile_load_data($user);

        $enrol = $DB->get_record_sql('SELECT * FROM {enrol} WHERE courseid = ' . $courseid . ' and enrol = "manual"  ORDER BY id DESC LIMIT 1');
        $enroled = $DB->get_record_sql('SELECT * FROM {user_enrolments} WHERE userid = ' . $user->id . ' and enrolid = '.$enrol->id.' ORDER BY id DESC LIMIT 1');

        $idInscripcionBack = $DB->get_record_sql('SELECT * FROM {inscripciones_back} WHERE id_sesion_moodle = '.$sessionid. ' AND participanteidentificador = "'.$user->username.'" ORDER BY id DESC LIMIT 1');
        echo "<pre>";
        print_r($idInscripcionBack); 
        $idinterno = '';

        if (!empty($idInscripcionBack)){
            $idinterno = $idInscripcionBack->idinterno;
        }
        else{
            $enroledIdn = $DB->get_record_sql('SELECT * FROM {eabcattendance_enrol_idin} WHERE id_usr_enrolment = '.$enroled->id);
            $idinterno = $enroledIdn ? $enroledIdn->id_interno : '';
        }

        echo "ID interno";
        print_r($idinterno); 

        if (empty($idinterno)){
            throw new moodle_exception("Error eliminando, el participante : ". $user->username. " no tiene idInterno de Dynamics");
        }
        
        $guidsessionbd = $DB->get_record('sesion_back', array('id_sesion_moodle' => $sessionid));
        $guid_sesion = $guidsessionbd->idsesion;
        $rut = explode('-', $user->username);
        $data = [
            "Operacion" => "DEL",
            "IdSesion" => $guid_sesion,
            'IdEvento' => $guidsessionbd->idevento,
            "IdRegistroDynamics" => $idinterno,
            "IdRegistroFront" => $sessionid,
            "Participante" => [
                "Apellido1" => $user->lastname,
                "Apellido2" => $user->profile_field_apellidom,
                "Nombre" => $user->firstname,
                "Pasaporte" => null,
                "Rut" => $rut[0],
                "Dv" => substr($user->username, -1),
                "TipoDocumento" => ($user->profile_field_participantetipodocumento == 2) ? 100 : 1
            ]
        ];

        $response = \local_pubsub\metodos_comunes::request($endpoint, $data, 'post');

        $data = json_decode($response['data'], true);

        if ($data['codigo'] != 0 ) {
            throw new moodle_exception("Error eliminando el registro en Dynamics, mensaje recibido: ". $response["data"]);
        } else {
            groups_remove_member($groupid, $studentid);

            list($sesssql, $sessparams) = $DB->get_in_or_equal($sessionid);
            list($usersql, $userparams) = $DB->get_in_or_equal($studentid);

            $params = array_merge($sessparams, $userparams);
            $select = "sessionid {$sesssql} AND studentid {$usersql}";

            $DB->delete_records_select('eabcattendance_log', $select, $params);
        }

    }

    /**
     * Update duration.
     *
     * @param array $sessionsids
     * @param int $duration
     */
    public function update_sessions_duration($sessionsids, $duration) {
        global $DB;

        $now = time();
        $sessions = $DB->get_recordset_list('eabcattendance_sessions', 'id', $sessionsids);
        foreach ($sessions as $sess) {
            $sess->duration = $duration;
            $sess->timemodified = $now;
            $DB->update_record('eabcattendance_sessions', $sess);
            if ($sess->caleventid) {
                eabcattendance_update_calendar_event($sess);
            }
            $event = \mod_eabcattendance\event\session_duration_updated::create(array(
                'objectid' => $this->id,
                'context' => $this->context,
                'other' => array('info' => implode(', ', $sessionsids))));
            $event->add_record_snapshot('course_modules', $this->cm);
            $event->add_record_snapshot('eabcattendance_sessions', $sess);
            $event->trigger();
        }
        $sessions->close();
    }

    /**
     * Check if the email address is already in use by either another temporary user,
     * or a real user.
     *
     * @param string $email the address to check for
     * @param int $tempuserid optional the ID of the temporary user (to avoid matching against themself)
     * @return null|string the error message to display, null if there is no error
     */
    public static function check_existing_email($email, $tempuserid = 0) {
        global $DB;

        if (empty($email)) {
            return null; // Fine to create temporary users without an email address.
        }
        if ($tempuser = $DB->get_record('eabcattendance_tempusers', array('email' => $email), 'id')) {
            if ($tempuser->id != $tempuserid) {
                return get_string('tempexists', 'eabcattendance');
            }
        }
        if ($DB->record_exists('user', array('email' => $email))) {
            return get_string('userexists', 'eabcattendance');
        }

        return null;
    }

    /**
     * Gets the status to use when auto-marking.
     *
     * @param int $time the time the user first accessed the course.
     * @param int $sessionid the related sessionid to check.
     * @return int the statusid to assign to this user.
     */
    public function get_automark_status($time, $sessionid) {
        $statuses = $this->get_statuses();
        // Statuses are returned highest grade first, find the first high grade we can assign to this user.

        // Get status to use when unmarked.
        $session = $this->sessioninfo[$sessionid];
        $duration = $session->duration;
        if (empty($duration)) {
            $duration = get_config('eabcattendance', 'studentscanmarksessiontimeend') * 60;
        }
        if ($time > $session->sessdate + $duration) {
            // This session closed after the users access - use the unmarked state.
            foreach ($statuses as $status) {
                if (!empty($status->setunmarked)) {
                    return $status->id;
                }
            }
        } else {
            foreach ($statuses as $status) {
                if ($status->studentavailability !== '0' &&
                    $this->sessioninfo[$sessionid]->sessdate + ($status->studentavailability * 60) > $time) {

                    // Found first status we could set.
                    return $status->id;
                }
            }
        }
        return;
    }

    /**
     * Gets the lowgrade threshold to use.
     *
     */
    public function get_lowgrade_threshold() {
        if (!isset($this->lowgradethreshold)) {
            $this->lowgradethreshold = 1;

            if ($this->grade > 0) {
                $gradeitem = grade_item::fetch(array('courseid' => $this->course->id, 'itemtype' => 'mod',
                    'itemmodule' => 'eabcattendance', 'iteminstance' => $this->id));
                if ($gradeitem->gradepass > 0 && $gradeitem->grademax != $gradeitem->grademin) {
                    $this->lowgradethreshold = ($gradeitem->gradepass - $gradeitem->grademin) /
                        ($gradeitem->grademax - $gradeitem->grademin);
                }
            }
        }

        return $this->lowgradethreshold;
    }
}
