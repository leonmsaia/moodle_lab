<?php

namespace local_migrategrades;

defined('MOODLE_INTERNAL') || die();

class csv_utils {
    /**
     * Validation callback for csv_import_reader::load_csv_content.
     * Returns true on success, string error message otherwise.
     */
    public static function validate_columns(&$columns) {
        $columns = array_map('trim', $columns);
        $lower = array_map(function($c) {
            return \core_text::strtolower($c);
        }, $columns);

        $required = array('username', 'shortname');
        $missing = array();
        foreach ($required as $r) {
            if (!in_array($r, $lower, true)) {
                $missing[] = $r;
            }
        }
        if (!empty($missing)) {
            return get_string('csv_missing_headers', 'local_migrategrades', implode(', ', $missing));
        }

        return true;
    }

    /**
     * Maps a CSV line to associative array with username + shortname.
     */
    public static function map_row(array $line, array $columns) : array {
        $mapped = array();
        foreach ($columns as $idx => $col) {
            $key = \core_text::strtolower(trim($col));
            $mapped[$key] = isset($line[$idx]) ? trim($line[$idx]) : '';
        }

        return array(
            'username' => $mapped['username'] ?? '',
            'shortname' => $mapped['shortname'] ?? '',
        );
    }
}
