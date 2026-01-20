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

namespace tool_eabcetlbridge\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\filters\{text, boolean_select, select, number};
use core_reportbuilder\local\entities\base;
use tool_eabcetlbridge\persistents\planners\planner as persistent;
use tool_eabcetlbridge\reportbuilder\local\formatters\common as common_formatter;

/**
 * Planner report entity
 *
 * @package   tool_eabcetlbridge
 * @category  entities
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class planner extends base {

    /**
     * {@inheritdoc}
     */
    protected function get_default_table_aliases(): array {
        return [
            persistent::TABLE => 'persistent',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_tables(): array {
        return array_keys($this->get_default_table_aliases());
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity_planner', 'tool_eabcetlbridge');
    }

    /**
     * {@inheritdoc}
     */
    public function initialise(): base {

        // All the columns defined by the entity.
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {

        $tablealias = $this->get_table_alias(persistent::TABLE);

        // ID column.
        $columns[] = (new column(
            'id',
            new lang_string('column_id', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.id")
            ->set_is_sortable(true);

        // Type column.
        $columns[] = (new column(
            'type',
            new lang_string('column_type', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.type")
            ->set_is_sortable(true)
            ->add_callback(
                [common_formatter::class, 'format_badge'],
                persistent::get_type_options_for_view()
            );

        // Type column.
        $columns[] = (new column(
            'objective',
            new lang_string('column_objective', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.objective")
            ->set_is_sortable(true)
            ->add_callback(
                [common_formatter::class, 'format_badge'],
                persistent::get_objective_options_for_view()
            );

        // Itemidentifier column.
        $columns[] = (new column(
            'itemidentifier',
            new lang_string('column_itemidentifier', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.itemidentifier")
            ->set_is_sortable(true);

        // Courseid column.
        $columns[] = (new column(
            'courseid',
            new lang_string('column_courseid', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.courseid")
            ->set_is_sortable(true);

        // Batchfileid column.
        $columns[] = (new column(
            'batchfileid',
            new lang_string('column_batchfileid', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.batchfileid")
            ->set_is_sortable(true);

        // Configid column.
        $columns[] = (new column(
            'configid',
            new lang_string('column_configid', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.configid")
            ->set_is_sortable(true);

        // Status column.
        $columns[] = (new column(
            'status',
            new lang_string('column_status', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.status")
            ->set_is_sortable(true)
            ->add_callback(
                [common_formatter::class, 'format_badge'],
                persistent::get_status_options_for_view()
            );

        // Usermodified column.
        $columns[] = (new column(
            'usermodified',
            new lang_string('column_usermodified', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.usermodified")
            ->set_is_sortable(true);

        // Timecreated column.
        $columns[] = (new column(
            'timecreated',
            new lang_string('column_timecreated', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.timecreated")
            ->set_is_sortable(true)
            ->add_callback([common_formatter::class, 'format_time']);

        // Timemodified column.
        $columns[] = (new column(
            'timemodified',
            new lang_string('column_timemodified', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.timemodified")
            ->set_is_sortable(true)
            ->add_callback([common_formatter::class, 'format_time']);

        return $columns;

    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $tablealias = $this->get_table_alias(persistent::TABLE);

        // Id filter.
        $filters[] = (new filter(
            number::class,
            'id',
            new lang_string('column_id', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.id"
        ))
            ->add_joins($this->get_joins());

        // Type filter.
        $filters[] = (new filter(
            select::class,
            'type',
            new lang_string('column_type', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.type"
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback(static function(): array {
                return persistent::get_type_options();
            });

        // Status filter.
        $filters[] = (new filter(
            select::class,
            'status',
            new lang_string('column_status', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.status"
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback(static function(): array {
                return persistent::get_status_options();
            });

        $filters[] = (new filter(
            number::class,
            'courseid',
            new lang_string('column_courseid', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.courseid"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'batchfileid',
            new lang_string('column_batchfileid', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.batchfileid"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'itemidentifier',
            new lang_string('column_itemidentifier', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.itemidentifier"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'configid',
            new lang_string('column_configid', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.configid"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'timemodified',
            new lang_string('column_timemodified', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.timemodified"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }

}
