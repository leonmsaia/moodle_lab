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

use local_mutualreport\reportbuilder\local\formatters\common as common_formatter;
use core_reportbuilder\local\entities\base;
use core_course\reportbuilder\local\formatters\completion as completion_formatter;
use core_course\reportbuilder\local\entities\completion as core_completion;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use completion_criteria_completion;
use completion_info;
use html_writer;
use lang_string;
use stdClass;

/**
 * Course completion entity implementation
 *
 * @package     core_course
 * @copyright   2022 David Matamoros <davidmc@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion extends core_completion {

    /** Status for in progress. */
    const STATUS_INPROGRESS = 1;
    /** Status for failed. */
    const STATUS_FAILED = 2;
    /** Status for failed for absence. */
    const STATUS_FAILED_FOR_ABSENCE = 3;
    /** Status for approved. */
    const STATUS_APPROVED = 4;

    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        $tables = parent::get_default_tables();
        $tables[] = 'course_completions';
        $tables[] = 'user_enrolments';
        $tables[] = 'enrol';
        return $tables;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        [
            'course_completion' => $coursecompletion,
            'course' => $course,
            'grade_grades' => $grade,
            'grade_items' => $gradeitem,
            'user' => $user,
            'course_completions' => $coursecompletions,
            'user_enrolments' => $userenrolment,
            'enrol' => $enrol,
        ] = $this->get_table_aliases();

        $columns = parent::get_all_columns();

        // Completado enviado.
        $columns[] = (new column(
            'completadoenviado',
            new lang_string('finalized', 'local_mutualreport'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$coursecompletions}.timecompleted", 'completadoenviado')
            ->set_is_sortable(true)
            ->add_callback([common_formatter::class, 'userdate']);

        // Calificacion enviada.
        $columns[] = (new column(
            'calificacionenviada',
            new lang_string('field_calificacionenviada', 'local_mutualreport'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("
                CASE
                    WHEN {$grade}.finalgrade >= {$gradeitem}.gradepass
                         THEN TRUNCATE({$grade}.finalgrade, 0)
                    WHEN (FROM_UNIXTIME({$userenrolment}.timecreated + 60*60*24*30) < NOW()
                         AND {$grade}.finalgrade IS NULL)
                         THEN ''
                    WHEN (FROM_UNIXTIME({$userenrolment}.timecreated + 60*60*24*30) < NOW()
                         AND {$grade}.finalgrade IS NOT NULL)
                         THEN TRUNCATE({$grade}.finalgrade, 0)
                    ELSE ''
                END
            ", 'calificacionenviada')
            ->set_is_sortable(true);

        // Estado.
        $approved = new lang_string('grade_approved', 'local_mutualreport');
        $failedforabsence = new lang_string('grade_failedforabsence', 'local_mutualreport');
        $failed = new lang_string('grade_failed', 'local_mutualreport');
        $inprogress = new lang_string('grade_inprogress', 'local_mutualreport');
        $columns[] = (new column(
            'estado',
            new lang_string('status', 'local_mutualreport'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("
                CASE
                    WHEN {$grade}.finalgrade >= {$gradeitem}.gradepass
                         THEN '" . self::STATUS_APPROVED . "'
                    WHEN (FROM_UNIXTIME({$userenrolment}.timecreated + 60*60*24*30) < NOW()
                         AND {$grade}.finalgrade IS NULL)
                         THEN '" . self::STATUS_FAILED_FOR_ABSENCE . "'
                    WHEN (FROM_UNIXTIME({$userenrolment}.timecreated + 60*60*24*30) < NOW()
                         AND {$grade}.finalgrade IS NOT NULL)
                         THEN '" . self::STATUS_FAILED . "'
                    ELSE '" . self::STATUS_INPROGRESS . "'
                END
            ", 'estado')
            ->set_is_sortable(true)
            ->add_callback([common_formatter::class, 'format_badge'], [
                'default' => [
                    'text' => $inprogress,
                    'color' => 'badge badge-primary'
                ],
                'checknull' => [
                    'text' => $inprogress,
                    'color' => 'badge badge-primary'
                ],
                'options' => [
                    self::STATUS_APPROVED => [
                        'text' => $approved,
                        'color' => 'badge badge-success'
                    ],
                    self::STATUS_FAILED_FOR_ABSENCE => [
                        'text' => $failedforabsence,
                        'color' => 'badge badge-warning'
                    ],
                    self::STATUS_FAILED => [
                        'text' => $failed,
                        'color' => 'badge badge-danger'
                    ],
                    self::STATUS_INPROGRESS => [
                        'text' => $inprogress,
                        'color' => 'badge badge-primary'
                    ]
                ]
            ]);

        return $columns;
    }

    /**
     * Returns list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        [
            'grade_grades' => $grade,
            'grade_items' => $gradeitem,
            'user_enrolments' => $userenrolment,
        ] = $this->get_table_aliases();

        $filters = parent::get_all_filters();

        $statusfield = "
            CASE
                WHEN {$grade}.finalgrade >= {$gradeitem}.gradepass
                     THEN '" . self::STATUS_APPROVED . "'
                WHEN (FROM_UNIXTIME({$userenrolment}.timecreated + 60*60*24*30) < NOW()
                     AND {$grade}.finalgrade IS NULL)
                     THEN '" . self::STATUS_FAILED_FOR_ABSENCE . "'
                WHEN (FROM_UNIXTIME({$userenrolment}.timecreated + 60*60*24*30) < NOW()
                     AND {$grade}.finalgrade IS NOT NULL)
                     THEN '" . self::STATUS_FAILED . "'
                ELSE '" . self::STATUS_INPROGRESS . "'
            END
        ";

        $filters[] = (new filter(
            \core_reportbuilder\local\filters\select::class,
            'estado',
            new lang_string('status', 'local_mutualreport'),
            $this->get_entity_name(),
            $statusfield
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                self::STATUS_INPROGRESS => get_string('grade_inprogress', 'local_mutualreport'),
                self::STATUS_FAILED => get_string('grade_failed', 'local_mutualreport'),
                self::STATUS_FAILED_FOR_ABSENCE => get_string('grade_failedforabsence', 'local_mutualreport'),
                self::STATUS_APPROVED => get_string('grade_approved', 'local_mutualreport'),
            ]);

        return $filters;
    }
}
