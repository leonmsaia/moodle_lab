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

namespace tool_eabcetlbridge\reportbuilder\local\systemreports;

use context_system, lang_string, pix_icon;
use core_reportbuilder\local\report\action;
use core_reportbuilder\system_report;
use tool_eabcetlbridge\reportbuilder\local\entities\adhoc_task as mainentity;
use tool_eabcetlbridge\url;

/**
 * Ad-hoc Tasks system report class implementation.
 *
 * @package    local_rayp_learning
 * @copyright  2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adhoc_tasks_report extends system_report {

    /**
     * Initialise report.
     *
     * @return void
     */
    protected function initialise(): void {
        $mainentity = new mainentity();
        $entitymainalias = $mainentity->get_table_alias('task_adhoc');

        $this->set_main_table('task_adhoc', $entitymainalias);
        $this->add_entity($mainentity);

        // Base fields required for any logic.
        $this->add_base_fields("{$entitymainalias}.id");

        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        $this->set_downloadable(true, get_string('entity_adhoc_task', 'tool_eabcetlbridge'));
    }

    /**
     * Validates access to view this report.
     *
     * @return bool
     */
    protected function can_view(): bool {
        // Only users with site configuration capabilities can view this report.
        return has_capability('moodle/site:config', context_system::instance());
    }

    /**
     * Adds the default columns to display in the report.
     *
     * @return void
     */
    public function add_columns(): void {
        $columns = [
            'adhoc_task:id',
            'adhoc_task:component',
            'adhoc_task:classname',
            'adhoc_task:nextruntime',
            'adhoc_task:timestarted',
            'adhoc_task:timecreated',
            'adhoc_task:pid',
            'adhoc_task:attemptsavailable',
            'adhoc_task:faildelay',
        ];
        $this->add_columns_from_entities($columns);
    }

    /**
     * Adds the default filters to display in the report.
     *
     * @return void
     */
    protected function add_filters(): void {
        $filters = [
            'adhoc_task:component',
            'adhoc_task:classname',
            'adhoc_task:faildelay',
            'adhoc_task:nextruntime',
        ];
        $this->add_filters_from_entities($filters);
    }

    /**
     * Adds actions for each row.
     *
     * Ad-hoc tasks are managed by the system, so direct actions like delete/edit are not safe.
     * This section is kept for structural completeness.
     *
     * @return void
     */
    protected function add_actions(): void {
        $editurl = url::editadhoc_tasks();

        // Delete action.
        $this->add_action((new action(
            new url($editurl, ['id' => ':id', 'action' => 'delete', 'sesskey' => sesskey()]),
            new pix_icon('t/delete', '', 'core'),
            [],
            false,
            new lang_string('delete')
        ))->add_callback(function($row) {
            return has_capability('moodle/site:config', context_system::instance());
        }));
    }
}
