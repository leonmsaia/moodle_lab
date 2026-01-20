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

namespace local_mutualreport\reportbuilder\local\entities;

use context_course;
use context_helper;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\course_selector;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\custom_fields;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\entities\course as core_course;
use html_writer;
use lang_string;
use stdClass;
use local_mutualreport\reportbuilder\local\filters\course_shortname_list;
use local_mutualreport\reportbuilder\local\filters\course_shortname_list35;
use theme_config;
use local_mutualreport\reportbuilder\local\filters\course_selector35;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Course entity class implementation
 *
 * This entity defines all the course columns and filters to be used in any report.
 *
 * @package     core_reportbuilder
 * @copyright   2021 Sara Arjona <sara@moodle.com> based on Marina Glancy code.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends core_course {

    /**
     * Returns list of all available columns.
     *
     * These are all the columns available to use in any report that uses this entity.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {

        $columns = parent::get_all_columns();
        $tablealias = $this->get_table_alias('course');
        $contexttablealias = $this->get_table_alias('context');

        // Name column.
        $columns[] = (new column(
            'fullname35',
            new lang_string('course'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.fullname")
            ->set_is_sortable(true);

        return $columns;
    }


    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $filters = parent::get_all_filters();

        $tablealias = $this->get_table_alias('course');

        // Username filter.
        $filters[] = (new filter(
            course_selector35::class,
            'courseselector35',
            new lang_string('courseselect', 'core_reportbuilder'),
            $this->get_entity_name(),
            "{$tablealias}.id"
        ))
            ->add_joins($this->get_joins());

        // Course shortname list filter.
        $filters[] = (new filter(
            course_shortname_list::class,
            'courseshortnamelist',
            new lang_string('filter_courseshortnamelist', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$tablealias}.shortname"
        ))
            ->add_joins($this->get_joins());

        // Course shortname list filter 35.
        $filters[] = (new filter(
            course_shortname_list35::class,
            'courseshortnamelist35',
            new lang_string('filter_courseshortnamelist', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$tablealias}.shortname"
        ))
            ->add_joins($this->get_joins());

        return $filters;

    }

}
