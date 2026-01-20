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
 * Username list filter class implementation.
 *
 * @package     local_mutualreport
 * @copyright   2024 e-abclearning.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class username_list extends base {

    /**
     * Setup form.
     *
     * @param MoodleQuickForm $mform
     */
    public function setup_form(MoodleQuickForm $mform): void {
        $operatorlabel = get_string('filterfieldvalue', 'core_reportbuilder', $this->get_header());

        $element = $mform->addElement(
            'textarea',
            $this->name . '_values',
            $operatorlabel,
            ['rows' => 5, 'cols' => 30]
        );
        $element->setHiddenLabel(true);
        $mform->addElement(
            'static',
            $this->name . '_description',
            '',
            get_string('filter_usernamelist_help', 'local_mutualreport')
        );
        $mform->addHelpButton(
            $this->name . '_values',
            'filter_usernamelist',
            'local_mutualreport'
        );
    }

    /**
     * Return filter SQL.
     *
     * @param array $values
     * @return array
     */
    public function get_sql_filter(array $values): array {
        global $DB;

        $fieldsql = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();

        $usernamesraw = $values["{$this->name}_values"] ?? '';
        if (empty(trim($usernamesraw))) {
            return ['', []];
        }

        // Split by comma, newline, or space, and trim each username.
        $usernames = preg_split('/[\s,]+/', $usernamesraw);
        $usernames = array_map('trim', $usernames);
        $usernames = array_filter($usernames);

        if (empty($usernames)) {
            return ['', []];
        }

        [$userselect, $userparams] = $DB->get_in_or_equal(
            $usernames,
            SQL_PARAMS_NAMED,
            database::generate_param_name('username')
        );

        return ["{$fieldsql} {$userselect}", array_merge($params, $userparams)];
    }
}

