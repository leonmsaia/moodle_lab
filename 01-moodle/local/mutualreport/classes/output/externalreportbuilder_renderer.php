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

use html_writer;
use moodle_url;
use plugin_renderer_base;
use core_reportbuilder\output\renderer;
use core_reportbuilder\output\system_report;
use core_reportbuilder\table\custom_report_table;
use core_reportbuilder\table\custom_report_table_view;
use core_reportbuilder\table\system_report_table;
use core_reportbuilder\local\models\report;

/**
 * Report renderer class
 *
 * @package     core_reportbuilder
 * @copyright   2020 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class externalreportbuilder_renderer extends renderer {

    /**
     * Render a system report
     *
     * @param system_report $report
     * @return string
     */
    protected function render_eabc_system_report(system_report $report): string {
        $context = $report->export_for_template($this);

        return $this->render_from_template('core_reportbuilder/report', $context);
    }

    /**
     * Render a system report table
     *
     * @param system_report_table $table
     * @return string
     */
    protected function render_eabc_external_report_table(system_report_table $table): string {
        ob_start();
        $table->out($table->get_default_per_page(), false);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

}
