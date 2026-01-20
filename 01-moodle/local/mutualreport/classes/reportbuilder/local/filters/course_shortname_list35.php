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
use local_mutualreport\utils35;

/**
 * Course shortname list filter class implementation for Moodle 3.5 external DB.
 *
 * @package     local_mutualreport
 * @copyright   2024 e-abclearning.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_shortname_list35 extends base {

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
        $mform->addHelpButton(
            $this->name . '_values',
            'filter_courseshortnamelist',
            'local_mutualreport'
        );
        $mform->addElement(
            'static',
            $this->name . '_description',
            '',
            get_string('filter_courseshortnamelist_help', 'local_mutualreport')
        );
    }

    /**
     * Return filter SQL.
     *
     * @param array $values
     * @return array
     */
    public function get_sql_filter(array $values): array {
        $utils = new utils35();

        $fieldsql = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();

        $shortnamesraw = $values["{$this->name}_values"] ?? '';
        if (empty(trim($shortnamesraw))) {
            return ['', []];
        }

        $shortnames = preg_split('/[\s,]+/', $shortnamesraw, -1, PREG_SPLIT_NO_EMPTY);
        $shortnames = array_map('trim', $shortnames);

        [$shortnameselect, $shortnameparams] = $utils->db->get_in_or_equal(
            $shortnames,
            SQL_PARAMS_NAMED,
            database::generate_param_name('shortname')
        );

        return ["{$fieldsql} {$shortnameselect}", array_merge($params, $shortnameparams)];
    }
}
