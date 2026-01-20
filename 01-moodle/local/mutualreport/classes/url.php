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

namespace local_mutualreport;

use core\url as moodle_url;

/**
 * Class for manage plugin urls
 *
 * @package   local_mutualreport
 * @category  classes
 * @copyright 2024 e-ABC Learning <contacto@e-abclearning.com>
 */
class url extends moodle_url {

    public static function view_report_elsa_v1($params = null) {
        return new moodle_url('/local/mutualreport/view.php', $params);
    }

    /**
     * Get report elsa v2
     * @param array $params
     * @return moodle_url
     */
    public static function view_report_elsa_v2($params = null) {
        return new moodle_url('/local/mutualreport/pages/report.php', $params);
    }

    /**
     * View report elsa v35 with external database database
     * @param array $params
     * @return moodle_url
     */
    public static function view_report_elsa_with_external_db35($params = null) {
        return new moodle_url('/local/mutualreport/pages/report35.php', $params);
    }

    /**
     * View report elsa consolidado v1
     * @param array $params
     * @return moodle_url
     */
    public static function view_report_elsa_consolidado_v1($params = null) {
        return new moodle_url('/local/mutualreport/pages/consolidated_report.php', $params);
    }

    /**
     * View report elsa consolidado v1
     * @param array $params
     * @return moodle_url
     */
    public static function view_report_elsa_consolidado_v35($params = null) {
        return new moodle_url('/local/mutualreport/pages/consolidated_report35.php', $params);
    }

}
