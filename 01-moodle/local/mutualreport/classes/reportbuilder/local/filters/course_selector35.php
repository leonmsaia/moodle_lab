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
use core_reportbuilder\local\filters\course_selector as core_course_selector;
use local_mutualreport\utils35;

/**
 * Course selector filter class implementation
 *
 * @package     core_reportbuilder
 * @copyright   2021 David Matamoros <davidmc@moodle.com>.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_selector35 extends core_course_selector {

    /**
     * Setup form
     *
     * @param MoodleQuickForm $mform
     */
    public function setup_form(MoodleQuickForm $mform): void {

        $utils = new utils35();
        $courses = $utils->get_courses_options();

        $operatorlabel = get_string('filterfieldvalue', 'core_reportbuilder', $this->get_header());
        $options = [
            'multiple' => true,
        ];

        $mform->addElement('autocomplete', $this->name . '_values', $operatorlabel, $courses, $options)
            ->setHiddenLabel(true);
    }

    /**
     * Return filter SQL
     *
     * @param array $values
     * @return array
     */
    public function get_sql_filter(array $values): array {
        //global $DB;
        $utils = new utils35();

        $fieldsql = $this->filter->get_field_sql();
        $params = $this->filter->get_field_params();

        $courseids = $values["{$this->name}_values"] ?? [];
        if (empty($courseids)) {
            return ['', []];
        }

        [$courseselect, $courseparams] = $utils->db->get_in_or_equal(
            $courseids,
            SQL_PARAMS_NAMED,
            database::generate_param_name('_')
        );

        return ["{$fieldsql} $courseselect", array_merge($params, $courseparams)];
    }

}
