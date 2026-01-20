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

use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\filters\text as core_text;
use local_mutualreport\utils35;

/**
 * Text report filter
 *
 * @package     core_reportbuilder
 * @copyright   2021 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text35 extends core_text {

    /**
     * Validate filter form values
     *
     * @param int $operator
     * @param string|null $value
     * @return bool
     */
    private function validate_filter_values(int $operator, ?string $value): bool {
        $operatorsthatdontrequirevalue = [
            self::ANY_VALUE,
            self::IS_EMPTY,
            self::IS_NOT_EMPTY,
        ];

        if ($value === '' && !in_array($operator, $operatorsthatdontrequirevalue)) {
            return false;
        }

        return true;
    }

    /**
     * Return filter SQL
     *
     * @param array $values
     * @return array array of two elements - SQL query and named parameters
     */
    public function get_sql_filter(array $values): array {
        //global $utils->db;
        $utils = new utils35();
        $name = database::generate_param_name();

        $operator = (int) ($values["{$this->name}_operator"] ?? self::ANY_VALUE);
        $value = trim($values["{$this->name}_value"] ?? '');

        $fieldsql = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();

        // Validate filter form values.
        if (!$this->validate_filter_values($operator, $value)) {
            // Filter configuration is invalid. Ignore the filter.
            return ['', []];
        }

        switch($operator) {
            case self::CONTAINS:
                $res = $utils->db->sql_like($fieldsql, ":$name", false, false);
                $value = $utils->db->sql_like_escape($value);
                $params[$name] = "%$value%";
                break;
            case self::DOES_NOT_CONTAIN:
                $res = $utils->db->sql_like($fieldsql, ":$name", false, false, true);
                $value = $utils->db->sql_like_escape($value);
                $params[$name] = "%$value%";
                break;
            case self::IS_EQUAL_TO:
                $res = $utils->db->sql_equal($fieldsql, ":$name", false, false);
                $params[$name] = $value;
                break;
            case self::IS_NOT_EQUAL_TO:
                $res = $utils->db->sql_equal($fieldsql, ":$name", false, false, true);
                $params[$name] = $value;
                break;
            case self::STARTS_WITH:
                $res = $utils->db->sql_like($fieldsql, ":$name", false, false);
                $value = $utils->db->sql_like_escape($value);
                $params[$name] = "$value%";
                break;
            case self::ENDS_WITH:
                $res = $utils->db->sql_like($fieldsql, ":$name", false, false);
                $value = $utils->db->sql_like_escape($value);
                $params[$name] = "%$value";
                break;
            case self::IS_EMPTY:
                $paramempty = database::generate_param_name();
                $res = "COALESCE({$fieldsql}, :{$paramempty}) = :{$name}";
                $params[$paramempty] = $params[$name] = '';
                break;
            case self::IS_NOT_EMPTY:
                $paramempty = database::generate_param_name();
                $res = "COALESCE({$fieldsql}, :{$paramempty}) != :{$name}";
                $params[$paramempty] = $params[$name] = '';
                break;
            default:
                // Filter configuration is invalid. Ignore the filter.
                return ['', []];
        }
        return array($res, $params);
    }

}
