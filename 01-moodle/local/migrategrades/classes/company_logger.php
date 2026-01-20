<?php

namespace local_migrategrades;

defined('MOODLE_INTERNAL') || die();

use stdClass;

class company_logger {
    public static function log_row(array $data) : void {
        global $DB;

        $rec = new stdClass();
        $rec->timecreated = $data['timecreated'] ?? time();
        $rec->actorid = (int)($data['actorid'] ?? 0);

        $rec->username = $data['username'] ?? null;
        $rec->userid = isset($data['userid']) ? (int)$data['userid'] : null;

        $rec->empresarut = $data['empresarut'] ?? null;
        $rec->companyid = isset($data['companyid']) ? (int)$data['companyid'] : null;

        $rec->status = $data['status'] ?? 'error';
        $rec->message = $data['message'] ?? null;

        $rec->soap_error = $data['soap_error'] ?? null;
        $rec->soap_mensaje = $data['soap_mensaje'] ?? null;

        try {
            $DB->insert_record('local_migrategrades_company_log', $rec);
        } catch (\Throwable $e) {
            // Intentionally ignore logging failures.
        }
    }
}
