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

namespace tool_eabcetlbridge\strategies\course_history;

defined('MOODLE_INTERNAL') || die();

use csv_import_reader;
use core_php_time_limit;

/**
 * Strategy for migrating Course History data from a CSV content.
 *
 * @package   tool_eabcetlbridge
 * @category  strategies
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class load_data
{

    /** @var string $error csv import error. */
    protected $error;
    /** @var int $iid Unique identifier for these csv records. */
    protected $iid;
    /** @var array $headers Column names for the data. */
    protected $headers;
    /** @var array $previewdata A subsection of the csv imported data. */
    protected $previewdata;

    /**
     * Load CSV content for previewing.
     *
     * @param string $text The CSV data being imported.
     * @param string $encoding The type of encoding the file uses.
     * @param string $separator The separator being used to define each field.
     * @param int $previewrows How many rows are being previewed.
     */
    public function load_csv_content($text, $encoding, $separator, $previewrows)
    {
        $this->raise_limits();

        $this->iid = csv_import_reader::get_new_iid('coursehistoryutils');
        $csvimport = new csv_import_reader($this->iid, 'coursehistoryutils');

        $csvimport->load_csv_content($text, $encoding, $separator);
        $this->error = $csvimport->get_error();

        // If there are no import errors then proceed.
        if (empty($this->error)) {

            // Get header (field names).
            $this->headers = $csvimport->get_columns();
            $this->trim_headers();

            $csvimport->init();
            $this->previewdata = array();

            for ($numlines = 0; $numlines <= $previewrows; $numlines++) {
                $lines = $csvimport->next();
                if ($lines) {
                    $this->previewdata[] = $lines;
                }
            }
        }
    }

    /**
     * Cleans the column headers from the CSV file.
     */
    protected function trim_headers()
    {
        foreach ($this->headers as $i => $h) {
            $h = trim($h); // Remove whitespace.
            $h = clean_param($h, PARAM_RAW); // Clean the header.
            $this->headers[$i] = $h;
        }
    }

    /**
     * Raises the php execution time and memory limits for importing the CSV file.
     */
    protected function raise_limits()
    {
        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);
    }

    /**
     * Returns the headers parameter for this class.
     *
     * @return array returns headers parameter for this class.
     */
    public function get_headers()
    {
        return $this->headers;
    }

    /**
     * Returns the error parameter for this class.
     *
     * @return string returns error parameter for this class.
     */
    public function get_error()
    {
        return $this->error;
    }

    /**
     * Returns the iid parameter for this class.
     *
     * @return int returns iid parameter for this class.
     */
    public function get_iid()
    {
        return $this->iid;
    }

    /**
     * Returns the preview_data parameter for this class.
     *
     * @return array returns previewdata parameter for this class.
     */
    public function get_previewdata()
    {
        return $this->previewdata;
    }
}
