<?php

namespace local_migrategrades;

defined('MOODLE_INTERNAL') || die();

class rut_csv_utils {
    public static function validate_columns(\csv_import_reader $cir, array $columns) {
        $required = array('username');
        $lower = array();
        foreach ($columns as $c) {
            $lower[] = \core_text::strtolower(trim($c));
        }

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

    public static function map_row(array $line, array $columns) : array {
        $out = array();
        foreach ($columns as $idx => $name) {
            $key = \core_text::strtolower(trim($name));
            $out[$key] = isset($line[$idx]) ? trim($line[$idx]) : '';
        }
        return $out;
    }
}
