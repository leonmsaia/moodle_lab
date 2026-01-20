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

use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\number;
use core_user;
use lang_string;
use stdClass;
use tool_eabcetlbridge\reportbuilder\local\formatters\common as common_formatter;

/**
 * Ad-hoc Task report entity.
 *
 * @package   tool_eabcetlbridge
 * @category  entities
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adhoc_task extends base {

    /**
     * {@inheritdoc}
     */
    protected function get_default_table_aliases(): array {
        return ['task_adhoc' => 'ata'];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_tables(): array {
        $aliases = $this->get_default_table_aliases();
        return array_keys($aliases);
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity_adhoc_task', 'tool_eabcetlbridge');
    }

    /**
     * {@inheritdoc}
     */
    public function initialise(): base {
        // Add all columns defined by this entity.
        foreach ($this->get_all_columns() as $column) {
            $this->add_column($column);
        }

        // Add all filters defined by this entity.
        foreach ($this->get_all_filters() as $filter) {
            $this->add_filter($filter)->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns for the adhoc_task entity.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $columns = [];
        $tablealias = $this->get_table_alias('task_adhoc');

        // ID column.
        $columns[] = (new column(
            'id',
            new lang_string('column_id', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_fields("{$tablealias}.id")
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true);

        // Component column.
        $columns[] = (new column(
            'component',
            new lang_string('column_component', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_fields("{$tablealias}.component")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true);

        // Classname column.
        $columns[] = (new column(
            'classname',
            new lang_string('column_classname', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_fields("{$tablealias}.classname")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true);

        // User column (joining with the user table).
        /*$userfullname = core_user::get_fullname_sql_format();
        $columns[] = (new column(
            'user',
            new lang_string('user'),
            $this->get_entity_name()
        ))
            ->add_field($userfullname, 'userfullname')
            ->add_join(null, 'user', 'u', "u.id = {$tablealias}.userid", 'LEFT')
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true, ['u.firstname', 'u.lastname']);*/

        // Next runtime column.
        $columns[] = (new column(
            'nextruntime',
            new lang_string('column_nextruntime', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_fields("{$tablealias}.nextruntime")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_callback([common_formatter::class, 'format_userdate']);

        // Time created column.
        $columns[] = (new column(
            'timecreated',
            new lang_string('column_timecreated', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_fields("{$tablealias}.timecreated")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_callback([common_formatter::class, 'format_userdate']);

        // Time started column.
        $columns[] = (new column(
            'timestarted',
            new lang_string('column_timestarted', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_fields("{$tablealias}.timestarted")
            ->set_type(column::TYPE_TIMESTAMP)
            ->set_is_sortable(true)
            ->add_callback([common_formatter::class, 'format_userdate']);

        // Faildelay column.
        $columns[] = (new column(
            'faildelay',
            new lang_string('column_faildelay', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_fields("{$tablealias}.faildelay")
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true);

        // Attemptsavailable column.
        $columns[] = (new column(
            'attemptsavailable',
            new lang_string('column_attemptsavailable', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_fields("{$tablealias}.attemptsavailable")
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true);

        // Customdata column.
        $columns[] = (new column(
            'customdata',
            new lang_string('column_customdata', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_fields("{$tablealias}.customdata")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(true);

        // Pid column.
        $columns[] = (new column(
            'pid',
            new lang_string('column_pid', 'tool_eabcetlbridge'),
            $this->get_entity_name()
        ))
            ->add_fields("{$tablealias}.pid")
            ->set_type(column::TYPE_INTEGER)
            ->set_is_sortable(true);

        return $columns;
    }

    /**
     * Return list of all available filters for this entity.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        global $DB;
        $filters = [];
        $tablealias = $this->get_table_alias('task_adhoc');

        // Component filter (dynamic select).
        $filters[] = (new filter(
            text::class,
            'component',
            new lang_string('column_component', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.component"
        ))->set_options_callback(static function() use ($DB, $tablealias): array {
            $sql = "SELECT DISTINCT t.component, t.component
                      FROM {task_adhoc} t
                     WHERE t.component IS NOT NULL
                  ORDER BY t.component ASC";
            return $DB->get_records_sql_menu($sql);
        });

        // Classname filter.
        $filters[] = (new filter(
            text::class,
            'classname',
            new lang_string('column_classname', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.classname"
        ));

        // Faildelay filter.
        $filters[] = (new filter(
            number::class,
            'faildelay',
            new lang_string('column_faildelay', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.faildelay"
        ));

        // Next runtime filter.
        $filters[] = (new filter(
            date::class,
            'nextruntime',
            new lang_string('column_nextruntime', 'tool_eabcetlbridge'),
            $this->get_entity_name(),
            "{$tablealias}.nextruntime"
        ));

        // User filter.
        //$filters[] = \core_reportbuilder\local\entities\user::get_filter('userid', "{$tablealias}.userid", new lang_string('user'));

        return $filters;
    }
}