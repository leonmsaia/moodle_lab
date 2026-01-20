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

namespace local_mutualreport\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\select;
use local_mutualreport\reportbuilder\local\filters\text35;
use local_mutualreport\reportbuilder\local\filters\company_selector;
use local_mutualreport\reportbuilder\local\filters\company_selector35;
use local_mutualreport\reportbuilder\local\filters\company_rut_list;
use local_mutualreport\reportbuilder\local\filters\company_rut_list35;
use local_mutualreport\reportbuilder\local\filters\company_contrato_list;
use local_mutualreport\reportbuilder\local\filters\company_contrato_list35;
use local_mutualreport\reportbuilder\local\formatters\common as common_formatter;

/**
 * Company entity
 *
 * @package    local_mutualreport
 * @copyright  2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company extends base {

    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'company',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('company_entity', 'local_mutualreport');
    }

    /**
     * Initialise the entity
     *
     * @return base
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

        $tablealias = $this->get_table_alias('company');

        // ID column.
        $columns[] = (new column(
            'id',
            new lang_string('field_id', 'local_mutualreport'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$tablealias}.id")
            ->set_is_sortable(true);

        // Name column.
        $columns[] = (new column(
            'name',
            new lang_string('companyname', 'local_mutualreport'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.name")
            ->set_is_sortable(true);

        // Rut column.
        $columns[] = (new column(
            'rut',
            new lang_string('companyrut', 'local_mutualreport'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.rut")
            ->set_is_sortable(true);

        // Contrato column.
        $columns[] = (new column(
            'contrato',
            new lang_string('nroadherente', 'local_mutualreport'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.contrato")
            ->set_is_sortable(true);

        return $columns;

    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $companyalias = $this->get_table_alias('company');

        $filters = [];

        // Rut filter.
        $filters[] = (new filter(
            text::class,
            'rut',
            new lang_string('companyrut', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$companyalias}.rut"
        ))
            ->add_joins($this->get_joins());

        // Rut filter.
        $filters[] = (new filter(
            text35::class,
            'rut35',
            new lang_string('companyrut', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$companyalias}.rut"
        ))
            ->add_joins($this->get_joins());

        // Contrato filter.
        $filters[] = (new filter(
            text::class,
            'contrato',
            new lang_string('nroadherente', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$companyalias}.contrato"
        ))
            ->add_joins($this->get_joins());

        // Contrato filter.
        $filters[] = (new filter(
            text35::class,
            'contrato35',
            new lang_string('nroadherente', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$companyalias}.contrato"
        ))
            ->add_joins($this->get_joins());

        // Company filter.
        $filters[] = (new filter(
            company_selector::class,
            'companyselector',
            new lang_string('filter_company', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$companyalias}.id"
        ))
            ->add_joins($this->get_joins());

        // Company filter.
        $filters[] = (new filter(
            select::class,
            'companyselector2',
            new lang_string('filter_company', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$companyalias}.id"
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback(static function(): array {
                global $USER;
                $companies = \local_mutualreport\utils::get_companies_from_userid_options(
                    $USER->id,
                    true
                );
                return $companies;
            });

        $filters[] = (new filter(
            company_selector35::class,
            'companyselector35',
            new lang_string('filter_company', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$companyalias}.id"
        ))
            ->add_joins($this->get_joins());

        // Company filter.
        $filters[] = (new filter(
            select::class,
            'companyselector35_2',
            new lang_string('filter_company', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$companyalias}.id"
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback(static function(): array {
                global $USER;
                $utils = new \local_mutualreport\utils35();
                $companies = $utils->get_companies_from_username_options(
                    $USER->username,
                    true
                );
                return $companies;
            });

        // Company RUT list filter.
        $filters[] = (new filter(
            company_rut_list::class,
            'companyrutlist',
            new lang_string('filter_companyrutlist', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$companyalias}.rut"
        ))
            ->add_joins($this->get_joins());

        // Company contrato list filter.
        $filters[] = (new filter(
            company_contrato_list::class,
            'companycontratolist',
            new lang_string('filter_companycontratolist', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$companyalias}.contrato"
        ))
            ->add_joins($this->get_joins());

        // Company RUT list filter 35.
        $filters[] = (new filter(
            company_rut_list35::class,
            'companyrutlist35',
            new lang_string('filter_companyrutlist', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$companyalias}.rut"
        ))
            ->add_joins($this->get_joins());

        // Company contrato list filter 35.
        $filters[] = (new filter(
            company_contrato_list35::class,
            'companycontratolist35',
            new lang_string('filter_companycontratolist', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$companyalias}.contrato"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }

}
