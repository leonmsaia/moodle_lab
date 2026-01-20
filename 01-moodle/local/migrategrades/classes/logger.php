<?php

namespace local_migrategrades;

defined('MOODLE_INTERNAL') || die();

use stdClass;

class logger {
    public static function log_row(array $data) : void {
        global $DB;

        $rec = new stdClass();
        $rec->timecreated = $data['timecreated'] ?? time();
        $rec->actorid = (int)($data['actorid'] ?? 0);

        $rec->username = $data['username'] ?? null;
        $rec->shortname = $data['shortname'] ?? null;

        $rec->newuserid = isset($data['newuserid']) ? (int)$data['newuserid'] : null;
        $rec->newcourseid = isset($data['newcourseid']) ? (int)$data['newcourseid'] : null;

        $rec->status = $data['status'] ?? 'error';
        $rec->winner = $data['winner'] ?? null;

        $rec->oldgrade = isset($data['oldgrade']) ? $data['oldgrade'] : null;
        $rec->newgrade = isset($data['newgrade']) ? $data['newgrade'] : null;

        $rec->applied_timeenrolled = isset($data['applied_timeenrolled']) ? (int)$data['applied_timeenrolled'] : null;
        $rec->applied_timecompleted = isset($data['applied_timecompleted']) ? (int)$data['applied_timecompleted'] : null;

        $rec->message = $data['message'] ?? null;

        try {
            $DB->insert_record('local_migrategrades_log', $rec);
        } catch (\Throwable $e) {
            // Intentionally ignore logging failures to not break migrations.
        }
    }
}
