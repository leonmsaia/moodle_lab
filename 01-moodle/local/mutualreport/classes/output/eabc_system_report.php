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

use renderable;
use renderer_base;
use stdClass;
use templatable;
use core_reportbuilder\system_report as system_report_base;
use core_reportbuilder\external\system_report_exporter;
use core_reportbuilder\local\models\report;
use core_reportbuilder\output\system_report;
use local_mutualreport\external\eabc_system_report_exporter;

/**
 * System report output class
 *
 * @package     core_reportbuilder
 * @copyright   2020 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eabc_system_report extends system_report {

    /**
     * Export report data suitable for a template
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $exporter = new eabc_system_report_exporter($this->report, [
            'source' => $this->source,
            'parameters' => json_encode($this->parameters),
        ]);

        return $exporter->export($output);
    }

}
