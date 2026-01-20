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

use context_course;
use core_course\reportbuilder\local\formatters\enrolment as enrolment_formatter;
use core_course\reportbuilder\local\entities\enrolment as core_enrolment;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_user\output\status_field;
use enrol_plugin;
use lang_string;
use stdClass;

use local_mutualreport\reportbuilder\local\formatters\common as common_formatter;
use local_mutualreport\reportbuilder\local\filters\date_gte_to_cutoff_date;
use local_mutualreport\reportbuilder\local\filters\date_lte_to_cutoff_date;

/**
 * Course enrolment entity implementation
 *
 * @package     core_course
 * @copyright   2022 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrolment extends core_enrolment {

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $userenrolments = $this->get_table_alias('user_enrolments');
        $enrol = $this->get_table_alias('enrol');

        $columns = parent::get_all_columns();

        // Enrolment time created.
        $columns[] = (new column(
            'timecreated2',
            new lang_string('enroleddate', 'local_mutualreport'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$userenrolments}.timecreated")
            ->set_is_sortable(true)
            ->add_callback([common_formatter::class, 'userdate']);

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $userenrolments = $this->get_table_alias('user_enrolments');
        $enrol = $this->get_table_alias('enrol');

        $filters = parent::get_all_filters();

        // Enrolment time created.
        $filter = (new filter(
            date_gte_to_cutoff_date::class,
            'timecreated_gte_to_cutoff_date',
            new lang_string('timecreated', 'moodle'),
            $this->get_entity_name(),
            "{$userenrolments}.timecreated"
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                date::DATE_RANGE,
                date::DATE_LAST,
                date::DATE_CURRENT,
            ]);

        $filters[] = $filter;

        $filters[] = (new filter(
            date_lte_to_cutoff_date::class,
            'timecreated_lte_to_cutoff_date',
            new lang_string('timecreated', 'moodle'),
            $this->get_entity_name(),
            "{$userenrolments}.timecreated"
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                date::DATE_RANGE
            ]);

        return $filters;
    }

}
