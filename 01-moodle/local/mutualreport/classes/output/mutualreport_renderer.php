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

namespace local_mutualreport\output;

defined('MOODLE_INTERNAL') || die();

use core\output\plugin_renderer_base;

/**
 * Renderer for local_mutualreport.
 *
 * @package     local_mutualreport
 * @copyright   2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mutualreport_renderer extends plugin_renderer_base {


    /**
     * Render the action bar template.
     *
     * @param local_mutualreport\output\elsa_action_bar $actionbar the action bar to be rendered
     * @return string the rendered action bar
     */
    public function render_elsa_action_bar($actionbar) {
        return $this->render_from_template(
            $actionbar->get_template(),
            $actionbar->export_for_template($this)
        );
    }

}
