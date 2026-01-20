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

namespace tool_eabcetlbridge;

use csv_import_reader;
use core_text;
use core\exception\moodle_exception;
use core\context\system;
use core\url;

/**
 * Class for common functions for upload data
 *
 * @package   tool_eabcetlbridge
 * @category  classes
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upload_utils {

    /**
     * Validation callback function - verified the column line of csv file.
     * Converts standard column names to lowercase.
     * @param csv_import_reader $cir
     * @param url $returnurl return url in case of any error
     * @param array $stdfields standard member fields
     * @param array $acceptedfields accepted management fields
     * @param int $numberobligatorycolumns number of obligatory columns
     * @return array list of fields
     * @throws moodle_exception
     */
    public static function validate_uploaded_columns(csv_import_reader $cir, url $returnurl, $stdfields,
            $acceptedfields, $numberobligatorycolumns) {

        $columns = $cir->get_columns();

        if (empty($columns)) {
            $cir->close();
            $cir->cleanup();
            throw new moodle_exception('cannotreadtmpfile', 'error', $returnurl);
        }

        if (count($columns) < $numberobligatorycolumns) {
            $cir->close();
            $cir->cleanup();
            throw new moodle_exception('csvfewcolumns', 'error', $returnurl);
        }

        // Test columns.
        $processed = array();
        $specialfieldsregex = "/^(" . implode('|', $acceptedfields) . ")\d+$/";

        foreach ($columns as $key => $unused) {
            $field = $columns[$key];
            $field = trim($field);
            $lcfield = \core_text::strtolower($field);
            if (in_array($field, $stdfields) || in_array($lcfield, $stdfields)) {
                // Standard fields are only lowercase.
                $newfield = $lcfield;
            } else if (preg_match($specialfieldsregex, $lcfield)) {
                // Special fields for enrolments.
                $newfield = $lcfield;
            } else {
                $cir->close();
                $cir->cleanup();
                throw new moodle_exception('invalidfieldname', 'error', $returnurl, $field);
            }
            if (in_array($newfield, $processed)) {
                $cir->close();
                $cir->cleanup();
                throw new moodle_exception('duplicatefieldname', 'error', $returnurl, $newfield);
            }
            $processed[$key] = $newfield;
        }

        return $processed;
    }

    /**
     * Parse fields from CSV line.
     * @param array $filecolumns
     * @param int $linenum
     * @param array $fields
     * @param string $specialfieldsregex
     * @return array of row and structure columns by digit and field
     */
    public static function parse_cvs_content(&$filecolumns, $linenum, &$fields, $specialfieldsregex) {

        $rowcols = $structurecols = array();
        $rowcols['line'] = $linenum;

        foreach ($fields as $key => $field) {
            $rowcols[$filecolumns[$key]] = s(trim($field));

            $field = core_text::strtolower($filecolumns[$key]);
            $value = $rowcols[$filecolumns[$key]];
            if (preg_match($specialfieldsregex, $field, $matches)) {
                $field = $matches[1];
                $digit = substr($matches[0], strlen($matches[1])); // Get the substring after the matched field name.
                $structurecols[$digit][$field] = $value;
            }
        }

        return [
            $rowcols,
            $structurecols
        ];

    }

}
