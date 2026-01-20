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

namespace tool_eabcetlbridge\reportbuilder\local\formatters;

use stdClass;
use core\url as moodle_url;
use core\context\system as context_system;
use core\output\html_writer;

/**
 * Common formatter
 *
 * @package   tool_eabcetlbridge
 * @category  formatters
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class common {

    /**
     * Return entity editor
     *
     * @param string $editorfield
     * @param stdClass $row with id
     * @param string $fieldname
     * @return string
     */
    public static function entity_editor($text, stdClass $row, $arguments = array()): string {
        global $CFG;
        require_once("{$CFG->libdir}/filelib.php");

        if ($text === null) {
            return '';
        }

        $context = context_system::instance();
        $itemid = $row->id;
        $component = $arguments['component'] ?? 'tool_eabcetlbridge';
        $filearea = $arguments['filearea'] ?? 'description';
        $text = file_rewrite_pluginfile_urls(
            $text,
            'pluginfile.php',
            $context->id,
            $component,
            $filearea,
            $itemid,
            ['includetoken' => true]
        );

        return format_text($text, FORMAT_HTML, ['context' => $context->id]);
    }

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
                return html_writer::span($string, 'badge badge-warning');
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
     * Return formatted seconds to time
     *
     * @param string $date
     * @param stdClass $row
     * @param array $arguments
     * @return string
     */
    public static function format_seconds_to_hours($date, stdClass $row, $arguments = array()): string {
        if (empty($totalsecs)) {
            return get_string('never');
        }
        return format_time($totalsecs);
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
     * Return formatted user date
     *
     * @param string $date
     * @param stdClass $row
     * @param array $arguments
     * @return string
     */
    public static function format_userdate($date, stdClass $row = null, $arguments = array()): string {
        $result = '';
        if (!is_null($date)) {
            if (!$date) {
                return get_string('never');
            }
            $result = userdate($date);
        }
        return $result;
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
