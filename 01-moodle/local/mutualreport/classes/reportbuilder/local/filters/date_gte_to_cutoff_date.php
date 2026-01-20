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

use core\{clock, di};
use core\lang_string;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\filters\date as core_date;
use MoodleQuickForm;

/**
 * Date report filter
 *
 * This filter accepts a unix timestamp to perform date filtering on
 *
 * @package     core_reportbuilder
 * @copyright   2021 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class date_gte_to_cutoff_date extends core_date {

    /**
     * Return an array of operators available for this filter
     *
     * @return lang_string[]
     */
    private function get_operators(): array {
        $operators = [
            self::DATE_ANY => new lang_string('filterisanyvalue', 'core_reportbuilder'),
            self::DATE_NOT_EMPTY => new lang_string('filterisnotempty', 'core_reportbuilder'),
            self::DATE_EMPTY => new lang_string('filterisempty', 'core_reportbuilder'),
            self::DATE_RANGE => new lang_string('filterrange', 'core_reportbuilder'),
            self::DATE_BEFORE => new lang_string('filterdatebefore', 'core_reportbuilder'),
            self::DATE_AFTER => new lang_string('filterdateafter', 'core_reportbuilder'),
            self::DATE_LAST => new lang_string('filterdatelast', 'core_reportbuilder'),
            self::DATE_CURRENT => new lang_string('filterdatecurrent', 'core_reportbuilder'),
            self::DATE_NEXT => new lang_string('filterdatenext', 'core_reportbuilder'),
            self::DATE_PAST => new lang_string('filterdatepast', 'core_reportbuilder'),
            self::DATE_FUTURE => new lang_string('filterdatefuture', 'core_reportbuilder'),
        ];

        return $this->filter->restrict_limited_operators($operators);
    }

    /**
     * Setup form
     *
     * Note that we cannot support float inputs in this filter currently, because decimals are not supported when calculating
     * relative timeframes according to {@link https://www.php.net/manual/en/datetime.formats.php}
     *
     * @param MoodleQuickForm $mform
     */
    public function setup_form(MoodleQuickForm $mform): void {
        // Operator selector.
        $operatorlabel = get_string('filterfieldoperator', 'core_reportbuilder', $this->get_header());
        $typesnounit = [self::DATE_ANY, self::DATE_NOT_EMPTY, self::DATE_EMPTY, self::DATE_RANGE,
            self::DATE_PAST, self::DATE_FUTURE];

        $elements[] = $mform->createElement('select', "{$this->name}_operator", $operatorlabel, $this->get_operators());
        $mform->setType("{$this->name}_operator", PARAM_INT);
        $mform->setDefault("{$this->name}_operator", self::DATE_ANY);

        // Value selector for last and next operators.
        $valuelabel = get_string('filterfieldvalue', 'core_reportbuilder', $this->get_header());

        $elements[] = $mform->createElement('text', "{$this->name}_value", $valuelabel, ['size' => 3]);
        $mform->setType("{$this->name}_value", PARAM_INT);
        $mform->setDefault("{$this->name}_value", 1);
        $mform->hideIf("{$this->name}_value", "{$this->name}_operator", 'in', array_merge($typesnounit, [self::DATE_CURRENT]));

        // Unit selector for last and next operators.
        $unitlabel = get_string('filterfieldunit', 'core_reportbuilder', $this->get_header());
        $units = [
            self::DATE_UNIT_MINUTE => get_string('filterdateminutes', 'core_reportbuilder'),
            self::DATE_UNIT_HOUR => get_string('filterdatehours', 'core_reportbuilder'),
            self::DATE_UNIT_DAY => get_string('filterdatedays', 'core_reportbuilder'),
            self::DATE_UNIT_WEEK => get_string('filterdateweeks', 'core_reportbuilder'),
            self::DATE_UNIT_MONTH => get_string('filterdatemonths', 'core_reportbuilder'),
            self::DATE_UNIT_YEAR => get_string('filterdateyears', 'core_reportbuilder'),
        ];

        $elements[] = $mform->createElement('select', "{$this->name}_unit", $unitlabel, $units);
        $mform->setType("{$this->name}_unit", PARAM_INT);
        $mform->setDefault("{$this->name}_unit", self::DATE_UNIT_DAY);
        $mform->hideIf("{$this->name}_unit", "{$this->name}_operator", 'in', $typesnounit);

        // Add operator/value/unit group.
        $mform->addGroup($elements, "{$this->name}_group", $this->get_header(), '', false)
            ->setHiddenLabel(true);

        // Get migration date from settings.
        $config = get_config('local_mutualreport');
        $hour = isset($config->migration_hour) ? (int)$config->migration_hour : 0;
        $minute = isset($config->migration_minute) ? (int)$config->migration_minute : 0;
        $second = isset($config->migration_second) ? (int)$config->migration_second : 0;
        $month = !empty($config->migration_month) ? (int)$config->migration_month : 9;
        $day = !empty($config->migration_day) ? (int)$config->migration_day : 17;
        $year = !empty($config->migration_year) ? (int)$config->migration_year : 2025;
        $cutofftimestamp = mktime($hour, $minute, $second, $month, $day, $year);

        // For 4.5 report, only allow dates from the cutoff date onwards.
        $dateoptions = ['optional' => true];
        $dateoptions['startyear'] = $year;
        $dateoptions['stopyear'] = $year + 6;

        // Date selectors for range operator.
        $mform->addElement('date_selector', "{$this->name}_from",
            get_string('filterfieldfrom', 'core_reportbuilder', $this->get_header()),
            $dateoptions);
        $mform->setType("{$this->name}_from", PARAM_INT);
        $mform->setDefault("{$this->name}_from", 0);
        $mform->hideIf("{$this->name}_from", "{$this->name}_operator", 'neq', self::DATE_RANGE);

        $mform->addElement('date_selector', "{$this->name}_to",
            get_string('filterfieldto', 'core_reportbuilder', $this->get_header()), $dateoptions);
        $mform->setType("{$this->name}_to", PARAM_INT);
        $mform->setDefault("{$this->name}_to", 0);
        $mform->hideIf("{$this->name}_to", "{$this->name}_operator", 'neq', self::DATE_RANGE);

        // Cutoff info.
        $cutoffdateformatted = userdate($cutofftimestamp);
        $messagedata = (object)['cutoffdate' => $cutoffdateformatted];
        $mform->addElement(
            'static',
            "{$this->name}_cutoff_info",
            '',
            get_string('filter_date_cutoff_info_45', 'local_mutualreport', $messagedata)
        );
        $mform->hideIf("{$this->name}_cutoff_info", "{$this->name}_operator", 'neq', self::DATE_RANGE);
    }

}
