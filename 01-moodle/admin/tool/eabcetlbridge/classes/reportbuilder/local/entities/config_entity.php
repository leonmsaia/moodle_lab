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
use core_reportbuilder\local\filters\{text, boolean_select};
use core_reportbuilder\local\entities\base;
use tool_eabcetlbridge\persistents\configs as persistent;
use tool_eabcetlbridge\reportbuilder\local\formatters\common as common_formatter;

/**
 * Config report entity
 *
 * @package   tool_eabcetlbridge
 * @category  entities
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config_entity extends base {

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
        return new lang_string('entity_config', 'tool_eabcetlbridge');
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

        // Name column.
        $columns[] = (new column(
            'name',
            new lang_string('column_name', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.name")
            ->set_is_sortable(true);

        // Shortname column.
        $columns[] = (new column(
            'shortname',
            new lang_string('column_shortname', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.shortname")
            ->set_is_sortable(true);

        // Strategyclass column.
        $columns[] = (new column(
            'strategyclass',
            new lang_string('column_strategyclass', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.strategyclass")
            ->set_is_sortable(true);

        // Sourcequery column.
        $columns[] = (new column(
            'sourcequery',
            new lang_string('column_sourcequery', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.sourcequery")
            ->set_is_sortable(true);

        // Mapping column.
        $columns[] = (new column(
            'mapping',
            new lang_string('column_mapping', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.mapping")
            ->set_is_sortable(true);

        // Isenabled column.
        $columns[] = (new column(
            'isenabled',
            new lang_string('column_isenabled', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.isenabled")
            ->set_is_sortable(true);

        // Lastruntime column.
        $columns[] = (new column(
            'lastruntime',
            new lang_string('column_lastruntime', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.lastruntime")
            ->set_is_sortable(true)
            ->add_callback([common_formatter::class, 'format_time']);

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

        // Name filter.
        $filters[] = (new filter(
            text::class,
            'name',
            new lang_string('column_name', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.name"
        ))
            ->add_joins($this->get_joins());

        // Shortname filter.
        $filters[] = (new filter(
            text::class,
            'shortname',
            new lang_string('column_shortname', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.shortname"
        ))
            ->add_joins($this->get_joins());

        // Isenabled filter.
        $filters[] = (new filter(
            boolean_select::class,
            'isenabled',
            new lang_string('column_isenabled', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.isenabled"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }

}
