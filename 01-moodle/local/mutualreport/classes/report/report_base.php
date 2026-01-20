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

namespace local_mutualreport\report;

use core\url as moodle_url;

/**
 * Base class for all mutualreport reports.
 *
 * @package     local_mutualreport
 * @copyright   2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class report_base {

    const GROUP_ELSA_45 = 'elsa45group';
    const GROUP_ELSA_35 = 'elsa35group';

    /**
     * Returns the unique machine-readable name for the report.
     * This is used for config settings. E.g., 'elsa_v2'.
     *
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Returns the human-readable name for the report.
     * This is used for labels in the admin settings. E.g., 'Reporte ELSA v2'.
     *
     * @return string
     */
    abstract public function get_title(): string;

    /**
     * Returns the URL to view the report.
     *
     * @return moodle_url
     */
    abstract public function get_url(): moodle_url;

    /**
     * Returns the group identifier for the menu.
     *
     * @return string
     */
    abstract public function get_group(): string;

    /**
     * * Returns the session groups.
     *
     * @return array
     */
    final public static function get_groups(): array {
        return [
            self::GROUP_ELSA_45 => get_string('report_elsa_maingroup', 'local_mutualreport'),
            self::GROUP_ELSA_35 => get_string('report_elsa_35group', 'local_mutualreport'),
        ];
    }

}
