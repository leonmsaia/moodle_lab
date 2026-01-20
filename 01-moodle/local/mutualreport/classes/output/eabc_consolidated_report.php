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

use renderer_base;
use stdClass;
use core_reportbuilder\output\system_report;
use local_mutualreport\reportbuilder\local\systemreports\elsa_consolidado_v35;
use local_mutualreport\reportbuilder\local\systemreports\elsa_consolidado_v1;
use local_mutualreport\table\eabc_consolidated_report_table;
use local_mutualreport\table\eabc_consolidated_report_table_35;
use local_mutualreport\table\eabc_simple_report_table;

/**
 * System report output class for consolidated reports.
 *
 * @package     local_mutualreport
 * @copyright   2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eabc_consolidated_report extends system_report {

    /**
     * Export report data suitable for a template
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        // If the source report is our new consolidated report v35, use the specific exporter.
        if (get_class($this->source) === elsa_consolidado_v35::class) {
            $exporter = new \local_mutualreport\external\eabc_system_report_exporter($this->report, [
                'source' => $this->source,
                'parameters' => json_encode($this->parameters),
            ],
                eabc_consolidated_report_table_35::class
            );
        } else if (get_class($this->source) === elsa_consolidado_v1::class) {
            // Otherwise, use the original exporter and table for v1 consolidated report.
            $exporter = new \local_mutualreport\external\eabc_system_report_exporter($this->report, [
                'source' => $this->source,
                'parameters' => json_encode($this->parameters),
            ],
                eabc_consolidated_report_table::class
            );
        } else {
            // Otherwise, use the original exporter and table for v1 consolidated report.
            $exporter = new \local_mutualreport\external\eabc_system_report_exporter($this->report, [
                'source' => $this->source,
                'parameters' => json_encode($this->parameters),
            ],
                eabc_simple_report_table::class
            );
        }

        return $exporter->export($output);
    }
}
