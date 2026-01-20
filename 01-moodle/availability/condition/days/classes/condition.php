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
 * Days condition.
 *
 * @package     availability_days
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright   2016 Valery Fremaux (http://www.mylearingfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_days;

defined('MOODLE_INTERNAL') || die();

/**
 * days from course start condition.
 *
 * @package availability_days
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var int Time (Unix epoch seconds) for condition. */
    private $daysfromstart;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
public function __construct($structure) {
    if (isset($structure->d) && is_numeric(str_replace(',', '.', $structure->d))) {
        $this->daysfromstart = (int) floatval(str_replace(',', '.', $structure->d));
    } else {
        $this->daysfromstart = 10;
    }
}

    /**
     * Saves the condition attributes.
     */
    public function save() {
        return (object) array('type' => 'days', 'd' => $this->daysfromstart);
    }

    /**
     * Checks the target is available
     * @param bool $not
     * @param \core_availability\info $info
     * @param bool $grabthelot
     * @param int $userid
     * @return boolean
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        return $this->is_available_for_all($not, $info->get_course()->id);
    }

    /**
     * Checks the target is globally available
     * @param bool $not
     * @return boolean
     */
    public function is_available_for_all($not = false, $idcourse = false) {
        global $DB, $USER;

        $referencedate = $this->get_reference_date($idcourse);

        // Check condition.
        $now = $this->get_time();
        $finaldateTime = (($this->daysfromstart * DAYSECS) + $referencedate);

        //convierto la fecha para buscar el dia, mes y año
        $finaldateStr = userdate($finaldateTime, '%Y-%m-%d');
        //despues de encontrear la fecha la fecha le agrego 23:59:59 
        //para que sea el final del dia y lo convierto a timestamp
        $finaldate = strtotime($finaldateStr . 'T23:59:59z');
        
        if(\local_mutual\front\utils::is_course_elearning($idcourse) == true){
            $send = $DB->get_records('format_eabctiles_send_course', array('courseid' => $idcourse, 'userid' => $USER->id));
            if(!empty($send)) {
                return false;
            }
        }

        if ((intval($now) >= intval($referencedate)) && (intval($now) <= intval($finaldate))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the reference date to calculate shift from
     * @return integer date
     */
    protected function get_reference_date($idcourse = false) {
        global $COURSE, $USER, $DB;
        if (!empty($idcourse)) {
            $idcourse = $idcourse;
            $course = $DB->get_record("course", array("id" => $idcourse));
        } else {
            $idcourse = $idcourse;
            $course = $COURSE;
        }
        $config = get_config('availability_days');

        if ($idcourse) {
            if (empty($config->referencedate) || $config->referencedate == 'coursestartdate') {
                // Calculate from course start date.
                $referencedate = $course->startdate;
            } else {
                // Calculate from lowest active enrol date of the user.
                $sql = '
                SELECT
                    ue.userid,
                    ue.timecreated as minenroltime, ue.timestart as timestart
                FROM
                    {user_enrolments} ue,
                    {enrol} e
                WHERE
                    ue.enrolid = e.id AND
                    ue.status = 0 AND
                    e.status = 0 AND
                    e.courseid = ? AND
                    ue.userid = ?
                GROUP BY
                    ue.userid
            ';
                $lowest = $DB->get_record_sql($sql, array($course->id, $USER->id));
                if (!$lowest) {
                    $referencedate = $course->startdate;
                } else {
                    // This should not happen but some role assigned users NON enrolled might fall into that case.
                    if ($lowest->timestart == 0)
                        $referencedate = $lowest->minenroltime;
                    else
                        $referencedate = $lowest->timestart;
                }
            }
            return $referencedate;
        }
    }

    /**
     * Gets a condition description for printing
     * @param bool $full
     * @param bool $not
     * @param \core_availability\info $info
     * @return boolean
     */
    public function get_description($full, $not, \core_availability\info $info) {
        return $this->get_either_description($not, false, $info->get_course()->id );
    }

    /**
     * Gets a condition description for printing
     * @param bool $full
     * @param bool $not
     * @param \core_availability\info $info
     * @return string
     */
    public function get_standalone_description($full, $not, \core_availability\info $info) {
        return $this->get_either_description($not, true, $info->get_course()->id);
    }

    /**
     * Shows the description using the different lang strings for the standalone
     * version or the full one.
     *
     * @param bool $not True if NOT is in force
     * @param bool $standalone True to use standalone lang strings
     * @return string
     */
    protected function get_either_description($not, $standalone, $idcourse = null) {

        global $PAGE, $DB, $USER;
        $satag = $standalone ? 'short_' : 'full_';
        $desc = '';
        if(\local_mutual\front\utils::is_course_elearning($idcourse) == true){
            $send = $DB->get_record('format_eabctiles_send_course', array('courseid' => $idcourse, 'userid' => $USER->id));
            if(!empty($send)){
                $grade_user_course = \local_sendgrade\utils::get_grade_user_course($idcourse, $USER->id);
                $grade_item_course = \local_sendgrade\utils::get_grade_item($idcourse, 'course');
                $grade = $grade_user_course;

                if($grade  >= $grade_item_course->gradepass){
                    $desc = 'Cuestionario no disponible. Usted ha finalizado correctamente el curso con nota ' . number_format($grade_user_course, 2);
                } else {
                    $desc = 'Cuestionario no disponible. Usted ha finalizado el curso con nota ' . number_format($grade_user_course, 2);
                }
            } else {
                $desc = get_string($satag . 'days', 'availability_days', self::show_days($this->daysfromstart, false, $idcourse));
            }
        } else {
            $desc = get_string($satag . 'days', 'availability_days', self::show_days($this->daysfromstart, false, $idcourse));
        }

        return $desc;
    }


    /**
     * @return int
     */
    protected function get_debug_string() {
        return $this->daysfromstart;
    }

    /**
     * Gets time. This function is implemented here rather than calling time()
     * so that it can be overridden in unit tests. (Would really be nice if
     * Moodle had a generic way of doing that, but it doesn't.)
     *
     * @return int Current time (seconds since epoch)
     */
    public function get_time() {
        return time();
    }

    /**
     * Shows a time either as a date or a full date and time, according to
     * user's timezone.
     *
     * @param int $days the relative days shift from course start
     * @param bool $dateonly If true, uses date only
     * @param bool $until If true, and if using date only, shows previous date
     * @return string Date
     */
    protected function show_days($days, $dateonly = false, $idcourse = false) {
        global $COURSE;
        $time = $this->get_reference_date($idcourse) + ($days * DAYSECS);
        return '+' . $days . ' (' . userdate($time, get_string($dateonly ? 'strftimedate' : 'strftimedatetime', 'langconfig')) . ')';
    }

    /**
     * What needs to be done after course restore.
     *
     * @param int $restoreid
     * @param int $courseid
     * @param \base_logger $logger
     * @param string $name
     * @return boolean
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) {
        return true;
    }

}
