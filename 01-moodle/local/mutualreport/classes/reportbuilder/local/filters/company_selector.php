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

namespace local_mutualreport\reportbuilder\local\filters;

use MoodleQuickForm;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\filters\base;

/**
 * Course selector filter class implementation
 *
 * @package     core_reportbuilder
 * @copyright   2021 David Matamoros <davidmc@moodle.com>.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_selector extends base {

    /**
     * Setup form
     *
     * @param MoodleQuickForm $mform
     */
    public function setup_form(MoodleQuickForm $mform): void {

        $operatorlabel = get_string(
            'filterfieldvalue', 'core_reportbuilder', $this->get_header()
        );

        $options = [
            'ajax' => 'local_mutualreport/form-search-selector',
            'methodname' => 'local_mutualreport_get_companies_by_user',
            'template' => 'local_mutualreport/select-element-suggestion',
            'multiple' => true,
            'valuehtmlcallback' => function($companyid) {
                global $DB, $OUTPUT;
                $company = $DB->get_record(
                    'company', ['id' => (int)$companyid], 'id, name', IGNORE_MISSING
                );
                if ($company) {
                    $data = new \stdClass();
                    $data->name = $company->name;
                    return $OUTPUT->render_from_template('local_mutualreport/select-element-suggestion', $data);
                }
                return '';
            },
            'noselectionstring' => get_string('filter_company_help', 'local_mutualreport'),
        ];
        // Users field.
        $mform
            ->addElement(
                'autocomplete',
                $this->name . '_values',
                $operatorlabel,
                [],
                $options
            )
            ->setHiddenLabel(true);
        $mform->addHelpButton(
            $this->name . '_values',
            'filter_company',
            'local_mutualreport'
        );
    }

    /**
     * Return filter SQL
     *
     * @param array $values
     * @return array
     */
    public function get_sql_filter(array $values): array {
        global $DB;

        $fieldsql = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();

        $companyids = $values["{$this->name}_values"] ?? [];
        if (empty($companyids)) {
            return ['', []];
        }

        [$companyselect, $companyparams] = $DB->get_in_or_equal($companyids, SQL_PARAMS_NAMED, database::generate_param_name('_'));

        return ["{$fieldsql} $companyselect", array_merge($params, $companyparams)];
    }

    /**
     * Return sample filter values
     *
     * @return array
     */
    public function get_sample_values(): array {
        return [
            "{$this->name}_values" => [1],
        ];
    }
}
