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

use plugin_renderer_base;
use core_reportbuilder\table\system_report_table;

/**
 * Renderer for consolidated reports.
 *
 * @package     local_mutualreport
 * @copyright   2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class consolidatedreport_renderer extends \core_reportbuilder\output\renderer {

    /**
     * Renders a system report.
     *
     * @param eabc_consolidated_report $report
     * @return string
     */
    public function render_eabc_consolidated_report(eabc_consolidated_report $report): string {
        $context = $report->export_for_template($this);

        return $this->render_from_template('core_reportbuilder/report', $context);
    }

    /**
     * Render a system report table
     *
     * @param system_report_table $table
     * @return string
     */
    protected function render_eabc_consolidated_report_table(system_report_table $table): string {
        ob_start();
        $table->out($table->get_default_per_page(), false);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * Renders a system report table specific to consolidated report v35.
     * 
     * @param system_report_table $table
     * @return string
     */
    protected function render_eabc_consolidated_report_table_35(system_report_table $table): string {
        ob_start();
        $table->out($table->get_default_per_page(), false);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * Renders a system report table specific to simple report.
     *
     * @param system_report_table $table
     * @return string
     */
    protected function render_eabc_simple_report_table(system_report_table $table): string {
        ob_start();
        $table->out($table->get_default_per_page(), false);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
