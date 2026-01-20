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

namespace local_mutualreport\table;

defined('MOODLE_INTERNAL') || die;

use local_mutualreport\utils35;
use moodle_exception;
use core\output\html_writer;
use core_reportbuilder\table\system_report_table;
use local_mutualreport\reportbuilder\local\entities\completion;

/**
 * Consolidated system report dynamic table class.
 *
 * This class fetches data from the local database and then enriches it
 * with data from an external (Moodle 3.5) database, applying specific
 * business logic for consolidation.
 *
 * @package     local_mutualreport
 * @copyright   2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eabc_simple_report_table extends system_report_table {

    /**
     * Override start of HTML to remove top pagination.
     */
    public function start_html() {
        // Render the dynamic table header.
        echo $this->get_dynamic_table_html_start();

        // Render button to allow user to reset table preferences.
        echo $this->render_reset_button();

        echo $this->download_buttons();

        $this->wrap_html_start();

        $this->set_caption($this->report::get_name(), ['class' => 'sr-only']);

        echo html_writer::start_tag('div');
        echo html_writer::start_tag('table', $this->attributes) . $this->render_caption();
    }
}
