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

/**
 * Base report
 *
 * @package    local_rayp_learning
 * @copyright  2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_rayp_learning\reportbuilder\local\systemreports;

defined('MOODLE_INTERNAL') || die();

use core_reportbuilder\system_report;
use core_reportbuilder\local\entities\user;
use core_reportbuilder\local\report\column;
use core_user\fields;
use stdClass;
use moodle_url;
use lang_string;
use html_writer;
use context_system;
use local_rayp_learning\utils;

/**
 * Base report
 *
 * @package    local_rayp_learning
 * @copyright  2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_report extends system_report {

    /**
     * Defines the report type, used in actions
     * @var int
     */
    protected $type = RAYP_ELEMENT_GENERAL;

    /**
     * Defines all alias used in the report
     * @var stdClass
     */
    protected $aliases = null;

    /**
     * Defines all entities used in the report
     * @var stdClass
     */
    protected $entities = null;

    /**
     * Output the report
     *
     * @uses \core_reportbuilder\output\renderer::render_system_report()
     *
     * @return string
     */
        public function custom_output(): string {
        global $PAGE;

        /** @var \local_rayp_learning\output\custom_reportbuilder_renderer $renderer */
        $renderer = $PAGE->get_renderer('local_rayp_learning', 'custom_reportbuilder');
        $report = new \local_rayp_learning\output\custom_system_report($this->get_report_persistent(), $this, $this->get_parameters());

        return $renderer->render($report);
    }

    /**
     * Returns the columns that the report should be grouped by.
     *
     * @return string[]
     */
    public function get_groupby() {
        return [];
    }

    /**
     * Return custom manual filters for the report.
     *
     * @return \core_reportbuilder\local\report\column[]
     */
    protected function get_custom_manual_columns() {
        $columns = array();

        $usertablealias = $this->aliases->user;

        // Add userid column.
        $column = (new column(
            'userid',
            new lang_string('id', 'local_rayp_learning'),
            $this->entities->user->get_entity_name()
        ))
            ->add_joins($this->entities->user->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$usertablealias}.id")
            ->set_is_sortable(true);
        $columns['user:userid'] = $column;

        // Add userid column.
        $fullnameselect = user::get_name_fields_select($usertablealias);
        $fullnamesort = explode(', ', $fullnameselect);
        $userpictureselect = fields::for_userpic()->get_sql($usertablealias, false, '', '', false)->selects;
        $viewfullnames = has_capability('moodle/site:viewfullnames', context_system::instance());
        $column = (new column(
            'fullnamewithpicturelink2',
            new lang_string('userfullnamewithpicturelink', 'core_reportbuilder'),
            $this->entities->user->get_entity_name()
        ))
            ->add_joins($this->entities->user->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields($fullnameselect)
            ->add_field("{$usertablealias}.id")
            ->add_fields($userpictureselect)
            ->set_is_sortable(true, $fullnamesort)
            ->add_callback(static function(?string $value, stdClass $row) use ($viewfullnames): string {
                global $OUTPUT;

                if ($value === null) {
                    return '';
                }

                // Ensure we populate all required name properties.
                $namefields = fields::get_name_fields();
                foreach ($namefields as $namefield) {
                    $row->{$namefield} = $row->{$namefield} ?? '';
                }

                /** @var \core_renderer $OUTPUT */
                $text = $OUTPUT->user_picture($row, ['link' => false, 'alttext' => true]) .
                    fullname($row, $viewfullnames);

                // Fix for empty picture, for no show in the donwload CSV.
                if (isset($row->picture) && empty($row->picture)) {
                    $text = fullname($row, $viewfullnames);
                }

                return html_writer::link(
                    new moodle_url('/user/profile.php', ['id' => $row->id]),
                    $text
                );

            });

        $columns['user:fullnamewithpicturelink2'] = $column;

        return $columns;
    }

    /**
     * Determines if the report can use custom aggregation.
     *
     * Some databases do not support the SQL needed to do custom aggregation.
     * This function checks if the current database is one of those.
     *
     * @return bool
     */
    public function can_report_use_custom_aggregation() {

        return utils::can_use_custom_aggregation();

    }

}
