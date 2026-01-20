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

namespace local_mutualreport\reportbuilder\local\filters;

use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\helpers\database;

/**
 * Select report filter
 *
 * The options for the select are defined when creating the filter by calling {@see set_options} or {@see set_options_callback}
 *
 * To extend this class in your own filter (e.g. to pre-populate available options), you should override the {@see get_operators}
 * and/or {@see get_select_options} methods
 *
 * @package     core_reportbuilder
 * @copyright   2021 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eabc_select_like extends select {

    /**
     * Validate filter form values
     *
     * @param int|null $operator
     * @param mixed|null $value
     * @return bool
     */
    private function validate_filter_values(?int $operator, $value): bool {
        return !($operator === null || $value === '');
    }

    /**
     * Return filter SQL
     *
     * Note that operators must be of type integer, while values can be integer or string.
     *
     * @param array $values
     * @return array array of two elements - SQL query and named parameters
     */
    public function get_sql_filter(array $values): array {

        $operator = $values["{$this->name}_operator"] ?? self::ANY_VALUE;
        $value = $values["{$this->name}_value"] ?? 0;

        // Validate filter form values.
        if (!$this->validate_filter_values((int) $operator, $value)) {
            // Filter configuration is invalid. Ignore the filter.
            return ['', []];
        }

        $field = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();
        $fieldsql = '';

        switch ($operator) {
            case self::EQUAL_TO:
                [$insql, $inparams] = static::get_sql_like_frament($field, $value, false);
                $fieldsql .= $insql;
                $params = array_merge($params, $inparams);
                break;
            case self::NOT_EQUAL_TO:
                [$insql, $inparams] = static::get_sql_like_frament($field, $value, true);
                $fieldsql .= $insql;
                $params = array_merge($params, $inparams);
                break;
            default:
                return ['', []];
        }
        return [$fieldsql, $params];
    }

    /**
     * Returns a SQL fragment for a LIKE filter on a given field and value
     *
     * @param string $field The field to filter on
     * @param string $value The value to filter by
     * @param bool $notlike Whether to return a NOT LIKE fragment
     * @return array An array with two elements - a SQL fragment and an array of named parameters
     */
    protected static function get_sql_like_frament($field, $value, $notlike = false)
    {
        global $DB;

        $params = [];
        $param1 = database::generate_param_name();
        $param2 = database::generate_param_name();
        $param3 = database::generate_param_name();
        $param4 = database::generate_param_name();
        $params[$param1] = "$value";
        $params[$param2] = "{$value},%";
        $params[$param3] = "%,{$value},%";
        $params[$param4] = "%,{$value}";

        /** @var \moodle_database $DB */
        $fieldsql1 = $DB->sql_like($field, ":{$param1}", false, false, $notlike);
        $fieldsql2 = $DB->sql_like($field, ":{$param2}", false, false, $notlike);
        $fieldsql3 = $DB->sql_like($field, ":{$param3}", false, false, $notlike);
        $fieldsql4 = $DB->sql_like($field, ":{$param4}", false, false, $notlike);
        $fieldsql = "($fieldsql1 OR $fieldsql2 OR $fieldsql3 OR $fieldsql4)";
        return [$fieldsql, $params];
    }

}
