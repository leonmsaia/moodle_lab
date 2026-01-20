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

namespace local_mutualreport\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_mutualreport\utils;
use local_mutualreport\utils35;

/**
 * Company summary exporter external API.
 *
 * @package   local_mutualreport
 * @copyright 2024 e-abclearning.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_summary_exporter extends external_api {

    /**
     * Get companies by user parameters.
     *
     * @return external_function_parameters
     */
    public static function get_companies_by_user_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'The search query.', VALUE_OPTIONAL, ''),
        ]);
    }

    /**
     * Get companies by user.
     *
     * @param string $query The search query.
     * @return array
     */
    public static function get_companies_by_user(string $query): array {
        global $USER;

        self::validate_context(\context_system::instance());

        return utils::search_companies_for_user($USER->id, $query);
    }

    /**
     * Get companies by user returns.
     *
     * @return external_multiple_structure
     */
    public static function get_companies_by_user_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Company ID'),
                'name' => new external_value(PARAM_TEXT, 'Company name'),
            ])
        );
    }

    /**
     * Get companies by user parameters for external DB.
     *
     * @return external_function_parameters
     */
    public static function get_companies_by_user_35_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'The search query.', VALUE_OPTIONAL, ''),
        ]);
    }

    /**
     * Get companies by user from external DB.
     *
     * @param string $query The search query.
     * @return array
     */
    public static function get_companies_by_user_35(string $query): array {
        global $USER;

        self::validate_context(\context_system::instance());

        $utils35 = new utils35();
        return $utils35->search_companies_for_user($USER->username, $query);
    }

    /**
     * Get companies by user from external DB returns.
     *
     * @return external_multiple_structure
     */
    public static function get_companies_by_user_35_returns(): external_multiple_structure {
        // The return structure is the same as the other function.
        return self::get_companies_by_user_returns();
    }
}
