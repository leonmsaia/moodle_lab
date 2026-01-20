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

/**
 * Holding Manager Renderer.
 *
 * @package    holdingmng
 * @copyright  2020 e-ABC Learning <contacto@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_holdingmng\output;
defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use renderable;

class renderer extends plugin_renderer_base {

    public function render_menu($data) {
        return $this->render_from_template('local_holdingmng/linkaccess', $data);
    }

    public function render_header($header) {
        return $this->render_from_template('local_holdingmng/header', $header);
    }

    public function render_alert($alert) {
        return $this->render_from_template('local_holdingmng/alert', $alert);
    }

    public function render_table($table) {
        return $this->render_from_template('local_holdingmng/table', $table);
    }
}