<?php

namespace local_migrategrades;

defined('MOODLE_INTERNAL') || die();

use stdClass;

class migrator {
    /** @var old_moodle_db */
    private $olddb;

    public function __construct(old_moodle_db $olddb) {
        $this->olddb = $olddb;
    }

    private function get_old_user_by_username(string $username) {
        $p = $this->olddb->prefix();
        return $this->olddb->get_one(
            "SELECT id, username, firstname, lastname, email, city, country, lang, timezone FROM {$p}user WHERE username = ? AND deleted = 0",
            array($username)
        );
    }

    private function ensure_new_user_exists(array $olduser) : int {
        global $DB, $CFG;

        $existing = $DB->get_record('user', array('username' => $olduser['username']), 'id', IGNORE_MISSING);
        if ($existing) {
            return (int)$existing->id;
        }

        require_once($CFG->dirroot . '/user/lib.php');

        $u = new stdClass();
        $u->username = $olduser['username'];
        $u->auth = 'manual';
        $u->confirmed = 1;
        $u->mnethostid = $CFG->mnet_localhost_id;
        $u->firstname = !empty($olduser['firstname']) ? $olduser['firstname'] : 'Usuario';
        $u->lastname = !empty($olduser['lastname']) ? $olduser['lastname'] : $olduser['username'];

        $email = !empty($olduser['email']) ? $olduser['email'] : '';
        if (empty($email) || $DB->record_exists('user', array('email' => $email))) {
            $email = $olduser['username'] . '.' . time() . '@invalid.local';
        }
        $u->email = $email;

        if (!empty($olduser['city'])) {
            $u->city = $olduser['city'];
        }
        if (!empty($olduser['country'])) {
            $u->country = $olduser['country'];
        }
        if (!empty($olduser['lang'])) {
            $u->lang = $olduser['lang'];
        }
        if (!empty($olduser['timezone'])) {
            $u->timezone = $olduser['timezone'];
        }

        $u->password = random_string(12);

        $newuserid = user_create_user($u);
        // Optional: force password change on next login.
        set_user_preference('auth_forcepasswordchange', 1, $newuserid);
        return (int)$newuserid;
    }

    private function ensure_new_enrolment(int $newcourseid, int $newuserid, int $timestart = 0) : void {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/lib/enrollib.php');

        $instances = $DB->get_records('enrol', array(
            'courseid' => $newcourseid,
            'status' => ENROL_INSTANCE_ENABLED,
            'enrol' => 'manual',
        ), 'sortorder,id');

        if (empty($instances)) {
            throw new \moodle_exception('dberror', 'error', '', null, 'No existe la instancia de matriculación manual para courseid=' . $newcourseid);
        }
        $instance = reset($instances);

        $already = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $newuserid), 'id', IGNORE_MISSING);
        if ($already) {
            return;
        }

        $roleid = (int)$DB->get_field('role', 'id', array('shortname' => 'student'), IGNORE_MISSING);
        if (empty($roleid)) {
            $roleid = 5;
        }

        $enrol = enrol_get_plugin('manual');
        if (!$enrol) {
            throw new \moodle_exception('dberror', 'error', '', null, 'Plugin enrol manual no disponible');
        }

        $enrol->enrol_user($instance, $newuserid, $roleid, $timestart ?: 0);
    }

    private function migrate_inscripcion_elearning_back(int $olduserid, int $oldcourseid, int $newuserid, int $newcourseid) : bool {
        global $DB;

        $p = $this->olddb->prefix();
        $row = $this->olddb->get_one(
            "SELECT * FROM {$p}inscripcion_elearning_back WHERE id_user_moodle = ? AND id_curso_moodle = ? ORDER BY id DESC LIMIT 1",
            array($olduserid, $oldcourseid)
        );
        if (!$row) {
            return false;
        }

        if (empty($row['participanteidregistroparticip'])) {
            return false;
        }

        $existing = $DB->get_record('inscripcion_elearning_back', array('participanteidregistroparticip' => $row['participanteidregistroparticip']), '*', IGNORE_MISSING);

        $rec = new stdClass();
        foreach ($row as $k => $v) {
            if ($k === 'id') {
                continue;
            }
            $rec->{$k} = $v;
        }
        $rec->id_user_moodle = $newuserid;
        $rec->id_curso_moodle = $newcourseid;

        $today = date('Y-m-d H:i:s');
        if ($existing) {
            $rec->id = $existing->id;
            $rec->updatedat = $today;
            $DB->update_record('inscripcion_elearning_back', $rec);
        } else {
            $rec->createdat = $today;
            $DB->insert_record('inscripcion_elearning_back', $rec);
        }

        // Also add a log row like pubsub does.
        $log = new stdClass();
        $log->id_curso_moodle = $newcourseid;
        $log->id_user_moodle = $newuserid;
        $log->participanteproductid = $row['participanteproductid'] ?? null;
        $log->participanteidregistroparticip = $row['participanteidregistroparticip'];
        $log->created_at = $today;
        $DB->insert_record('inscripcion_elearning_log', $log);

        return true;
    }

    private function get_old_completion_dates(int $olduserid, int $oldcourseid) : array {
        $p = $this->olddb->prefix();

        $cc = $this->olddb->get_one(
            "SELECT timeenrolled, timestarted, timecompleted FROM {$p}course_completions WHERE userid = ? AND course = ? ORDER BY id DESC LIMIT 1",
            array($olduserid, $oldcourseid)
        );

        $timeenrolled = 0;
        $timestarted = 0;
        $timecompleted = 0;

        if ($cc) {
            $timeenrolled = (int)($cc['timeenrolled'] ?? 0);
            $timestarted = (int)($cc['timestarted'] ?? 0);
            $timecompleted = (int)($cc['timecompleted'] ?? 0);
        }

        if ($timeenrolled <= 0) {
            $ue = $this->olddb->get_one(
                "SELECT ue.timecreated FROM {$p}user_enrolments ue JOIN {$p}enrol e ON e.id = ue.enrolid WHERE ue.userid = ? AND e.courseid = ? ORDER BY ue.timecreated ASC LIMIT 1",
                array($olduserid, $oldcourseid)
            );
            if ($ue) {
                $timeenrolled = (int)($ue['timecreated'] ?? 0);
            }
        }

        return array(
            'timeenrolled' => $timeenrolled,
            'timestarted' => $timestarted,
            'timecompleted' => $timecompleted,
        );
    }

    private function upsert_new_completion_dates(int $newuserid, int $newcourseid, int $timeenrolled, int $timestarted, int $timecompleted) : void {
        global $DB;

        $existing = $DB->get_record('course_completions', array('userid' => $newuserid, 'course' => $newcourseid), '*', IGNORE_MISSING);
        if ($existing) {
            $upd = new stdClass();
            $upd->id = $existing->id;
            $upd->timeenrolled = $timeenrolled;
            $upd->timestarted = $timestarted;
            $upd->timecompleted = $timecompleted;
            if (property_exists($existing, 'reaggregate')) {
                $upd->reaggregate = 0;
            }
            $DB->update_record('course_completions', $upd);
        } else {
            $ins = new stdClass();
            $ins->userid = $newuserid;
            $ins->course = $newcourseid;
            $ins->timeenrolled = $timeenrolled;
            $ins->timestarted = $timestarted;
            $ins->timecompleted = $timecompleted;
            $DB->insert_record('course_completions', $ins);
        }
    }

        private function get_old_best_grade_row(int $olditemid, int $olduserid) {
                $p = $this->olddb->prefix();

            // Only consider grade history from configured date onwards.
            $fromstr = get_config('local_migrategrades', 'old_grade_history_from');
            if (empty($fromstr)) {
                $fromstr = '2025-01-01 00:00:00';
            }
            $historyfrom = strtotime((string)$fromstr);
            if ($historyfrom === false) {
                $historyfrom = strtotime('2025-01-01 00:00:00');
            }

                return $this->olddb->get_one(
                        "SELECT finalgrade, timecreated, timemodified
                             FROM (
                                         SELECT gg.finalgrade AS finalgrade, gg.timecreated AS timecreated, gg.timemodified AS timemodified
                                             FROM {$p}grade_grades gg
                                            WHERE gg.itemid = ? AND gg.userid = ? AND gg.finalgrade IS NOT NULL
                                         UNION ALL
                                         SELECT ggh.finalgrade AS finalgrade, 0 AS timecreated, ggh.timemodified AS timemodified
                                             FROM {$p}grade_grades_history ggh
                          WHERE ggh.itemid = ? AND ggh.userid = ? AND ggh.finalgrade IS NOT NULL AND ggh.timemodified >= ?
                                        ) t
                     ORDER BY finalgrade DESC, COALESCE(timemodified, 0) DESC, timecreated DESC
                            LIMIT 1",
                array($olditemid, $olduserid, $olditemid, $olduserid, $historyfrom)
                );
        }

        private function grade_row_time($row) : int {
            if (is_array($row)) {
                $tm = (int)($row['timemodified'] ?? 0);
                $tc = (int)($row['timecreated'] ?? 0);
                return max($tm, $tc);
            }
            if (is_object($row)) {
                $tm = (int)($row->timemodified ?? 0);
                $tc = (int)($row->timecreated ?? 0);
                return max($tm, $tc);
            }
            return 0;
        }

        private function get_new_best_grade_row(int $newitemid, int $newuserid) {
                global $DB;

                return $DB->get_record_sql(
                        "SELECT finalgrade, timecreated, timemodified
                             FROM (
                                         SELECT gg.finalgrade AS finalgrade, gg.timecreated AS timecreated, gg.timemodified AS timemodified
                                             FROM {grade_grades} gg
                                            WHERE gg.itemid = ? AND gg.userid = ? AND gg.finalgrade IS NOT NULL
                                         UNION ALL
                                         SELECT ggh.finalgrade AS finalgrade, 0 AS timecreated, ggh.timemodified AS timemodified
                                             FROM {grade_grades_history} ggh
                                            WHERE ggh.itemid = ? AND ggh.userid = ? AND ggh.finalgrade IS NOT NULL
                                        ) t
                     ORDER BY finalgrade DESC, COALESCE(timemodified, 0) DESC, timecreated DESC",
                        array($newitemid, $newuserid, $newitemid, $newuserid),
                        IGNORE_MISSING
                );
        }

    private function item_key(string $itemmodule, string $itemname) : string {
        return \core_text::strtolower(trim($itemmodule)) . '|' . \core_text::strtolower(trim($itemname));
    }

    private function get_old_mod_grade_items(int $oldcourseid) : array {
        $p = $this->olddb->prefix();

        $rows = $this->olddb->get_all(
            "SELECT id, itemmodule, itemname FROM {$p}grade_items WHERE courseid = ? AND itemtype = 'mod' AND itemmodule IN ('quiz','assign') AND itemname IS NOT NULL AND itemname <> ''",
            array($oldcourseid)
        );

        $out = array();
        foreach ($rows as $r) {
            $module = (string)($r['itemmodule'] ?? '');
            $name = (string)($r['itemname'] ?? '');
            if ($module === '' || $name === '') {
                continue;
            }
            $out[$this->item_key($module, $name)] = array(
                'id' => (int)$r['id'],
                'itemmodule' => $module,
                'itemname' => $name,
            );
        }
        return $out;
    }

    private function get_new_mod_grade_items(int $newcourseid) : array {
        global $DB;

        $records = $DB->get_records_sql(
            "SELECT id, itemmodule, itemname FROM {grade_items} WHERE courseid = ? AND itemtype = 'mod' AND itemmodule IN ('quiz','assign') AND itemname IS NOT NULL AND itemname <> ''",
            array($newcourseid)
        );

        $out = array();
        foreach ($records as $rec) {
            $module = (string)($rec->itemmodule ?? '');
            $name = (string)($rec->itemname ?? '');
            if ($module === '' || $name === '') {
                continue;
            }
            $out[$this->item_key($module, $name)] = (object)array(
                'id' => (int)$rec->id,
                'itemmodule' => $module,
                'itemname' => $name,
            );
        }
        return $out;
    }

    private function migrate_mod_items(int $olduserid, int $oldcourseid, int $newuserid, int $newcourseid, int $usermodified) : array {
        global $DB;

        $updated = 0;
        $skipped = 0;
        $missingnewitem = 0;
        $details = array();

        $olditems = $this->get_old_mod_grade_items($oldcourseid);
        if (empty($olditems)) {
            return array('updated' => 0, 'skipped' => 0, 'missingnewitem' => 0, 'details' => array());
        }

        $newitems = $this->get_new_mod_grade_items($newcourseid);
        if (empty($newitems)) {
            return array('updated' => 0, 'skipped' => 0, 'missingnewitem' => count($olditems), 'details' => array());
        }

        foreach ($olditems as $key => $olditem) {
            if (empty($newitems[$key])) {
                $missingnewitem++;
                continue;
            }
            $newitem = $newitems[$key];

            $oldgraderow = $this->get_old_best_grade_row((int)$olditem['id'], (int)$olduserid);
            if (!$oldgraderow || $oldgraderow['finalgrade'] === null) {
                $skipped++;
                continue;
            }

            $oldfinal = (float)$oldgraderow['finalgrade'];
            $oldtimecreated = (int)($oldgraderow['timecreated'] ?? 0);
            $oldtimemodified = (int)($oldgraderow['timemodified'] ?? 0);
            $oldts = max($oldtimemodified, $oldtimecreated);

            $newgrade = $DB->get_record('grade_grades', array('itemid' => (int)$newitem->id, 'userid' => (int)$newuserid), 'id,finalgrade,timecreated,timemodified', IGNORE_MISSING);
            $newbest = $this->get_new_best_grade_row((int)$newitem->id, (int)$newuserid);
            $newfinal = ($newbest && $newbest->finalgrade !== null) ? (float)$newbest->finalgrade : null;
            $newts = $this->grade_row_time($newbest);

            // Old must be higher to overwrite. If equal, overwrite only when old attempt is newer/equal.
            if ($newfinal !== null && $oldfinal < $newfinal) {
                $skipped++;
                continue;
            }

            if ($newfinal !== null && $oldfinal == $newfinal && $newts > 0 && $newts > $oldts) {
                $skipped++;
                continue;
            }

            if ($newgrade) {
                $upd = new stdClass();
                $upd->id = (int)$newgrade->id;
                $upd->rawgrade = $oldfinal;
                $upd->finalgrade = $oldfinal;
                $upd->overridden = 1;
                $upd->usermodified = (int)$usermodified;
                $upd->timemodified = $oldtimemodified ?: time();
                $DB->update_record('grade_grades', $upd);
            } else {
                $ins = new stdClass();
                $ins->itemid = (int)$newitem->id;
                $ins->userid = (int)$newuserid;
                $ins->rawgrade = $oldfinal;
                $ins->finalgrade = $oldfinal;
                $ins->overridden = 1;
                $ins->usermodified = (int)$usermodified;
                $ins->timecreated = $oldtimecreated ?: time();
                $ins->timemodified = $oldtimemodified ?: $ins->timecreated;
                $DB->insert_record('grade_grades', $ins);
            }

            $updated++;
            $details[] = array(
                'itemmodule' => (string)$olditem['itemmodule'],
                'itemname' => (string)$olditem['itemname'],
                'old' => $oldfinal,
                'new' => $newfinal,
            );
        }

        return array(
            'updated' => $updated,
            'skipped' => $skipped,
            'missingnewitem' => $missingnewitem,
            'details' => $details,
        );
    }

    public function migrate_one(string $username, string $shortname, int $usermodified) : array {
        global $DB;

        $username = trim($username);
        $shortname = trim($shortname);

        if ($username === '' || $shortname === '') {
            return array('status' => 'error', 'winner' => 'none', 'message' => 'username/shortname vacío');
        }

        $newcourse = $DB->get_record('course', array('shortname' => $shortname), 'id,shortname', IGNORE_MISSING);
        if (!$newcourse) {
            return array('status' => 'error', 'winner' => 'none', 'message' => get_string('missing_course_new', 'local_migrategrades'));
        }

        $newitem = $DB->get_record('grade_items', array('courseid' => $newcourse->id, 'itemtype' => 'course'), 'id', IGNORE_MISSING);
        if (!$newitem) {
            return array('status' => 'error', 'winner' => 'none', 'message' => get_string('missing_gradeitem_new', 'local_migrategrades'));
        }

        // Load old course/user if available.
        $olduser = $this->get_old_user_by_username($username);
        $p = $this->olddb->prefix();
        $oldcourserow = $this->olddb->get_one("SELECT id FROM {$p}course WHERE shortname = ?", array($shortname));

        // Ensure new user exists; if missing, try to create it from old.
        $newuser = $DB->get_record('user', array('username' => $username), 'id,username', IGNORE_MISSING);
        $usercreated = false;
        if (!$newuser) {
            if (!$olduser) {
                return array(
                    'status' => 'error',
                    'winner' => 'none',
                    'newcourseid' => (int)$newcourse->id,
                    'message' => get_string('missing_user_new', 'local_migrategrades'),
                );
            }
            $newuserid = $this->ensure_new_user_exists($olduser);
            $newuser = (object)array('id' => $newuserid, 'username' => $username);
            $usercreated = true;
        }

        $newcourseid = (int)$newcourse->id;
        $newuserid = (int)$newuser->id;

        $olduserid = $olduser ? (int)$olduser['id'] : 0;
        $oldcourseid = $oldcourserow ? (int)$oldcourserow['id'] : 0;

        // If we created the user, try to enrol + migrate inscripcion_elearning_back.
        if ($usercreated && $oldcourseid > 0) {
            $olddates = $this->get_old_completion_dates($olduserid, $oldcourseid);
            $this->ensure_new_enrolment($newcourseid, $newuserid, (int)$olddates['timeenrolled']);
            $this->migrate_inscripcion_elearning_back($olduserid, $oldcourseid, $newuserid, $newcourseid);
        } else if ($usercreated) {
            // Still enrol if possible even without old course mapping.
            $this->ensure_new_enrolment($newcourseid, $newuserid, 0);
        }

        $newgrade = $DB->get_record('grade_grades', array('itemid' => $newitem->id, 'userid' => $newuser->id), 'id,finalgrade,timecreated,timemodified', IGNORE_MISSING);
        $newbest = $this->get_new_best_grade_row((int)$newitem->id, (int)$newuserid);
        $newfinal = ($newbest && $newbest->finalgrade !== null) ? (float)$newbest->finalgrade : null;

        $messages = array();
        $didupdateanything = false;
        $winner = ($newfinal !== null ? 'new' : 'none');
        $applied_timeenrolled = null;
        $applied_timecompleted = null;

        // If there's no course/user in old, we cannot migrate from old; treat as skipped.
        if ($olduserid <= 0 || $oldcourseid <= 0) {
            return array(
                'status' => 'skipped',
                'winner' => $winner,
                'newuserid' => $newuserid,
                'newcourseid' => $newcourseid,
                'message' => ($olduserid <= 0 ? get_string('missing_user_old', 'local_migrategrades') : get_string('missing_course_old', 'local_migrategrades')),
                'old' => null,
                'new' => $newfinal,
            );
        }

        $olditemrow = $this->olddb->get_one("SELECT id FROM {$p}grade_items WHERE courseid = ? AND itemtype = 'course' ORDER BY id ASC LIMIT 1", array($oldcourseid));
        if (!$olditemrow) {
            return array(
                'status' => 'skipped',
                'winner' => ($newfinal !== null ? 'new' : 'none'),
                'newuserid' => $newuserid,
                'newcourseid' => $newcourseid,
                'message' => get_string('missing_gradeitem_old', 'local_migrategrades'),
                'old' => null,
                'new' => $newfinal,
            );
        }
        $olditemid = (int)$olditemrow['id'];

        $oldgraderow = $this->get_old_best_grade_row($olditemid, $olduserid);
        // Always ensure enrolment (helps gradebook and completion consistency).
        $olddates = $this->get_old_completion_dates($olduserid, $oldcourseid);
        $this->ensure_new_enrolment($newcourseid, $newuserid, (int)$olddates['timeenrolled']);

        $oldfinal = null;
        $oldtimecreated = 0;
        $oldtimemodified = 0;
        if ($oldgraderow && $oldgraderow['finalgrade'] !== null) {
            $oldfinal = (float)$oldgraderow['finalgrade'];
            $oldtimecreated = (int)($oldgraderow['timecreated'] ?? 0);
            $oldtimemodified = (int)($oldgraderow['timemodified'] ?? 0);
        }

        // Migrate course final grade (if available) using the same rule: higher wins; tie -> old.
        $courseupdated = false;
        if ($oldfinal === null) {
            $messages[] = get_string('missing_grade_old', 'local_migrategrades');
        } else {
            $oldts = max($oldtimemodified, $oldtimecreated);
            $newts = $this->grade_row_time($newbest);

            // Decide winner by highest grade; if equal, take the latest attempt.
            if ($newfinal !== null && $oldfinal < $newfinal) {
                $winner = 'new';
                $messages[] = get_string('grade_not_higher', 'local_migrategrades');
            } else if ($newfinal !== null && $oldfinal == $newfinal && $newts > 0 && $newts > $oldts) {
                $winner = 'new';
                $messages[] = 'Nota igual: se mantiene la última nota (nueva).';
            } else {
                // Old wins (greater or equal, or equal and old is latest): copy enrolment/completion dates from old to new.
            $this->upsert_new_completion_dates(
                (int)$newuser->id,
                (int)$newcourse->id,
                (int)$olddates['timeenrolled'],
                (int)$olddates['timestarted'],
                (int)$olddates['timecompleted']
            );
            $applied_timeenrolled = (int)$olddates['timeenrolled'];
            $applied_timecompleted = (int)$olddates['timecompleted'];

            if ($newgrade) {
                $upd = new stdClass();
                $upd->id = (int)$newgrade->id;
                $upd->rawgrade = $oldfinal;
                $upd->finalgrade = $oldfinal;
                $upd->overridden = 1;
                $upd->usermodified = (int)$usermodified;
                $upd->timemodified = $oldtimemodified ?: time();
                $DB->update_record('grade_grades', $upd);
            } else {
                $ins = new stdClass();
                $ins->itemid = (int)$newitem->id;
                $ins->userid = (int)$newuser->id;
                $ins->rawgrade = $oldfinal;
                $ins->finalgrade = $oldfinal;
                $ins->overridden = 1;
                $ins->usermodified = (int)$usermodified;
                $ins->timecreated = $oldtimecreated ?: time();
                $ins->timemodified = $oldtimemodified ?: $ins->timecreated;
                $DB->insert_record('grade_grades', $ins);
            }
            $courseupdated = true;
            $didupdateanything = true;
            $winner = 'old';
            $messages[] = get_string('grade_updated', 'local_migrategrades');
            }
        }

        // Migrate quiz/assign grades by matching grade_items.itemname (same course) and applying the same comparison rule.
        $modres = $this->migrate_mod_items($olduserid, $oldcourseid, $newuserid, $newcourseid, (int)$usermodified);
        if (($modres['updated'] ?? 0) > 0) {
            $didupdateanything = true;
            $messages[] = 'Actividades migradas (quiz/tarea): ' . (int)$modres['updated'];
        }

        if ($didupdateanything) {
            // Regrade once to refresh gradebook calculations for this user/course.
            require_once($GLOBALS['CFG']->libdir . '/gradelib.php');
            if (function_exists('grade_regrade_final_grades')) {
                grade_regrade_final_grades($newcourse->id, $newuser->id);
            }
        }

        return array(
            'status' => ($didupdateanything ? 'updated' : 'skipped'),
            'winner' => $winner,
            'message' => implode('; ', $messages),
            'old' => $oldfinal,
            'new' => $newfinal,
            'applied_timeenrolled' => $applied_timeenrolled,
            'applied_timecompleted' => $applied_timecompleted,
            'newuserid' => $newuserid,
            'newcourseid' => $newcourseid,
            'mod_updated' => $modres['updated'] ?? 0,
            'mod_skipped' => $modres['skipped'] ?? 0,
            'mod_missing_new_item' => $modres['missingnewitem'] ?? 0,
            'mod_details' => $modres['details'] ?? array(),
        );
    }
}
