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

namespace local_mutualreport\reportbuilder\local\formatters;

use stdClass, html_writer, moodle_url;

/**
 * Common formatter
 *
 * @package    local_mutualreport
 * @copyright  2024 e-ABC Learning <contacto@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class common {

    /**
     * Return formatted time
     *
     * @param string $date
     * @param stdClass $row
     * @param string $fieldname
     * @return string
     */
    public static function format_time($date, stdClass $row, $arguments = array()): string {
        if (isset($arguments['checkfield'])) {
            $check = $arguments['checkfield'];
            $field = $check['field'];
            if (isset($row->$field) && $row->$field == $check['value']) {
                return (string) $check['string'];
            }
        }

        if (isset($arguments['checkpendinggrade'])) {
            $check = $arguments['checkpendinggrade'];
            $submittedfield = $check['submittedfield'];
            $gradedfield = $check['gradedfield'];

            if (!empty($row->$submittedfield) && empty($row->$gradedfield)) {
                $string = (string) $check['string'];
                return \html_writer::span($string, 'badge badge-warning');
            }
        }

        $result = '';

        $default = $arguments['default'] ?? false;
        if ($default) {
            $result = $default;
        }

        if (!is_null($date)) {
            if (!$date) {
                return get_string('never');
            }
            $result = userdate($date);
        }
        return $result;
    }

    /**
     * Returns formatted date.
     *
     * @param int|null $value Unix timestamp
     * @param stdClass $row
     * @param array|string $format Format string for strftime
     * @return string
     */
    public static function userdate(?int $value, stdClass $row, $format = '%d-%m-%Y %H:%M:%S'): string {
        global $PAGE;

        $format1 = '%d-%m-%Y %H:%M:%S';
        $format2 = '%d-%m-%Y %H:%M:%S';
        if (is_array($format)) {
            $format1 = $format[0];
            $format2 = $format[1];
        } else if (is_string($format)) {
            $format1 = $format;
            $format2 = $format;
        }

        // Check if the report is being downloaded.
        // When downloading, $PAGE->pagetype is not the report page type.
        // This is a reliable way to detect a download context within a callback.
        $isdownloading = ($PAGE->pagetype == 'local_mutualreport_download');

        if (!$isdownloading) {
            return $value ? userdate($value, $format1) : '';
        } else {
            return $value ? userdate($value, $format2) : '';
        }

    }

    /**
     * Return formatted simple time
     *
     * @param string $date
     * @param stdClass $row
     * @param array $arguments
     */
    public static function format_simple_time($date, stdClass $row = null, $arguments = array()): string {
        $result = '';
        $dateformat = 'd-m-y';
        if (!is_null($date)) {
            if (!$date) {
                return get_string('never');
            }
            $result = date($dateformat, $date);
        }
        return $result;
    }

    /**
     * Formats a completion progress bar.
     *
     * This function generates an HTML progress bar to visually represent the completion status.
     * It can interpret the completion value as either binary (0 or 1) or as a percentage.
     * If binary values are used, 0 represents not completed and 1 represents completed.
     * Otherwise, the completion value itself is used as a percentage.
     *
     * @param int $completed The completion status, either binary or percentage.
     * @param stdClass|null $row The data object containing user-related information, must include 'userid'.
     * @param array $arguments Additional arguments, including 'binaryvalues' to indicate binary interpretation.
     * @return string An HTML string of a progress element representing the completion status.
     */
    public static function format_completed_bar($completed, stdClass $row = null, $arguments = array()): string {

        $msg = '';
        $binaryvalues = $arguments['binaryvalues'] ?? false;
        if ($binaryvalues) {
            if ($completed == 1) {
                $msg = get_string('completeds', 'godeep_activitiesdetailstable');
                return \html_writer::span($msg, 'badge badge-success');
            } else if ($completed == 2) {
                $msg = get_string('notstarted', 'godeep_activitiesdetailstable');
                return \html_writer::span($msg, 'badge badge-light border');
            } else if ($completed == 3) {
                $msg = get_string('nottracked', 'godeep_activitiesdetailstable');
                return \html_writer::span($msg, 'badge badge-secondary');
            } else {
                $msg = get_string('notcompleteds', 'godeep_activitiesdetailstable');
                return \html_writer::span($msg, 'badge badge-danger');
            }
        } else {
            $completeds = $completed;
            if ($completeds >= 100) {
                $msg = get_string('completeds', 'godeep_activitiesdetailstable') . ' ' . $completeds . '%';
            } else {
                $msg = get_string('notcompleteds', 'godeep_activitiesdetailstable') . ' ' . $completeds . '%';
            }
        }
        $id = $row->userid;
        $content = \html_writer::tag('progress', $completeds, [
            'id' => $id . 'progress',
            'class' => 'progressbar helptooltip',
            'title' => $msg,
            'max' => '100',
            'value' => (int) $completeds
        ]);

        return  $content;
    }

    /**
     * Return formatted number
     *
     * @param float $number
     * @param stdClass $row null not used, but obligatory param for reportbuilder
     * @param array $arguments
     */
    public static function format_number($number, stdClass $row = null, $arguments = array()): string {
        $decimals = $arguments['decimals'] ?? 2;
        return number_format($number, $decimals);
    }

    /**
     * Formats a value as a badge element with an appropriate color.
     *
     * @param mixed $value The value to format, used as a key in the $options array.
     * @param stdClass $row The data object containing user-related information, not used in this function.
     * @param array $arguments Additional arguments, including
     *      - 'default' to specify the default text and color
     *      - 'options' to specify an array of key-value with text and color.
     * @return string The formatted value as a badge element with an appropriate color.
     */
    public static function format_badge($value, stdClass $row, $arguments = array()) : string {

        $default = $arguments['default'] ?? ['text' => '', 'color' => ''];
        $text = $default['text'] ?? '';
        $color = $default['color'] ?? '';

        $options = $arguments['options'] ?? array();
        foreach ($options as $key => $data) {
            if ($value == $key) {
                $text = $data['text'] ?? '';
                $color = $data['color'] ?? '';
                break;
            }
        }

        $checknull = $arguments['checknull'] ?? false;
        if ($checknull && is_null($value)) {
            $text = $checknull['text'] ?? '';
            $color = $checknull['color'] ?? '';
        }

        return html_writer::span($text, $color);

    }

    /**
     * Formats a value as a link to an URL.
     *
     * This function uses the value as the parameter for the URL. The URL is specified
     * in the $arguments['url'] parameter. The link text is specified in the $arguments['text']
     * parameter. The link attributes can be specified in the $arguments['attributes'] parameter.
     *
     * @param mixed $text The value to format, used as a parameter for the URL.
     * @param stdClass $row The data object containing user-related information, not used in this function.
     * @param array $arguments Additional arguments, including
     *      - 'url' to specify the URL
     *      - 'urlparams' to specify an array of key-value with parameter names and values
     *      - 'statictext' to specify the link text
     *      - 'attributes' to specify the link attributes
     * @return string The formatted value as a link.
     */
    public static function format_link($text, stdClass $row, $arguments = array()): string {
        $url = $arguments['url'] ?? '';
        $urlparams = $arguments['urlparams'] ?? array();
        if (!empty($urlparams)) {
            $params = array();
            foreach ($urlparams as $key => $value) {
                $params[$key] = $row->$value;
            }
            $url = new moodle_url($url, $params);
        }
        $text = $arguments['statictext'] ?? $text;

        $dynamictext = $arguments['dynamictext'] ?? false;
        if ($dynamictext) {
            $text = $row->$dynamictext;
        }

        $attributes = $arguments['attributes'] ?? array();
        return html_writer::link($url, $text, $attributes);
    }

}
