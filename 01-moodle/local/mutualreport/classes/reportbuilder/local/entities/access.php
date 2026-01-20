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

declare(strict_types=1);

namespace local_mutualreport\reportbuilder\local\entities;

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_course\reportbuilder\local\entities\access as core_access;
use lang_string;
use stdClass;

use local_mutualreport\reportbuilder\local\formatters\common as common_formatter;

/**
 * Course access entity implementation
 *
 * @package     core_course
 * @copyright   2022 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access extends core_access {

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $tablealias = $this->get_table_alias('user_lastaccess');
        $user = $this->get_table_alias('user');

        $columns = parent::get_all_columns();

        // Last course access column.
        $columns[] = (new column(
            'timeaccess2',
            new lang_string('lastcourseaccess', 'moodle'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$tablealias}.timeaccess")
            ->add_field("{$user}.id", 'userid')
            ->set_is_sortable(true)
            ->add_callback(static function(?int $value, stdClass $row, $arguments, ?string $aggregation): string {
                if ($row->userid === null && $aggregation === null) {
                    return '';
                } else if ($value === null) {
                    return '-';
                }
                return common_formatter::userdate($value, $row);
            });

        return $columns;
    }

}
