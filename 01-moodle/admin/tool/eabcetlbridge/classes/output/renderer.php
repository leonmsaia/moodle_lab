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

namespace tool_eabcetlbridge\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use html_table;
use html_writer;

/**
 * Renderer for the Course History Upload tool.
 *
 * @package   tool_eabcetlbridge
 * @category  output
 * @copyright 2025 e-ABC Learning <contact@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base
{

    /**
     * A renderer for the CSV file preview.
     *
     * @param array $header Column headers from the CSV file.
     * @param array $data The rest of the data from the CSV file.
     * @return string html to be displayed.
     */
    public function import_preview_page($header, $data)
    {
        $html = $this->output->heading(get_string('preview'));

        $table = new html_table();
        $table->head = array_map('s', $header);
        $table->data = array_map(static function ($row) {
            return array_map('s', $row);
        }, $data);
        $html .= html_writer::table($table);

        return $html;
    }

    /**
     * A renderer for errors generated trying to import the CSV file.
     *
     * @param array $errors Display import errors.
     * @return string errors as html to be displayed.
     */
    public function errors($errors)
    {
        $html = '';
        foreach ($errors as $error) {
            $html .= $this->output->notification($error);
        }
        return $html;
    }
}
