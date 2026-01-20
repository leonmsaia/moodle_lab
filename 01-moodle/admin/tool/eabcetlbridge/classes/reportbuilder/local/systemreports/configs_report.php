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
use tool_eabcetlbridge\reportbuilder\local\entities\config_entity as mainentity;
use tool_eabcetlbridge\persistents\configs as persistent;
use tool_eabcetlbridge\url;

/**
 * Academic Types system report class implementation
 *
 * @package   tool_eabcetlbridge
 * @category  systemreports
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class configs_report extends system_report {

    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     *
     * @return void
     */
    protected function initialise(): void {
        // Our main entity, it contains all of the column definitions that we need.
        $mainentity = new mainentity();
        $entitymainalias = $mainentity->get_table_alias(persistent::TABLE);

        $this->set_main_table(persistent::TABLE, $entitymainalias);
        $this->add_entity($mainentity);

        // Any columns required by actions should be defined here to ensure they're always available.
        $this->add_base_fields("{$entitymainalias}.id");

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();
        $this->add_actions();

        // Set if report can be downloaded.
        $this->set_downloadable(true, get_string('entity_config', 'tool_eabcetlbridge'));
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('moodle/site:config', context_system::instance());
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier. If custom columns are needed just for this report, they can be defined here.
     *
     * @return void
     */
    public function add_columns(): void {
        $columns = [
            'config_entity:id',
            'config_entity:name',
            'config_entity:shortname',
            'config_entity:strategyclass',
            'config_entity:isenabled',
            'config_entity:lastruntime',
        ];
        $this->add_columns_from_entities($columns);
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     *
     * @return void
     */
    protected function add_filters(): void {
        $filters = [
            'config_entity:name',
            'config_entity:shortname',
            'config_entity:isenabled',
        ];
        $this->add_filters_from_entities($filters);
    }

    /**
     * Add the system report actions. An extra column will be appended to each row, containing all actions added here
     *
     * Note the use of ":id" placeholder which will be substituted according to actual values in the row
     */
    protected function add_actions(): void {

        $editurl = url::editconfigs();

        // Delete action. It will be only shown if user has 'has_management_unit_permission' capabillity.
        $this->add_action((new action(
            new url($editurl, ['id' => ':id', 'action' => 'delete', 'sesskey' => sesskey()]),
            new pix_icon('t/delete', '', 'core'),
            [],
            false,
            new lang_string('delete')
        ))->add_callback(function($row) {
            return has_capability('moodle/site:config', context_system::instance());
        }));

        // Edit action. It will be only shown if user has 'has_management_unit_permission' capabillity.
        $this->add_action((new action(
            new url($editurl, ['id' => ':id', 'sesskey' => sesskey()]),
            new pix_icon('t/edit', '', 'core'),
            [],
            false,
            new lang_string('edit')
        ))->add_callback(function($row) {
            return has_capability('moodle/site:config', context_system::instance());
        }));

    }

}
