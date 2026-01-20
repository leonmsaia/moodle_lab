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

use core\external\persistent_exporter;
use core_table\local\filter\integer_filter;
use core_table\local\filter\string_filter;
use core_reportbuilder\system_report;
use core_reportbuilder\form\filter;
use core_reportbuilder\local\models\report;
use core_reportbuilder\table\system_report_table;
use core_reportbuilder\table\system_report_table_filterset;
use core_reportbuilder\external\system_report_exporter;
use renderer_base;

use local_mutualreport\table\eabc_external_report_table;

/**
 * Report exporter class
 *
 * @package     core_reportbuilder
 * @copyright   2020 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eabc_system_report_exporter extends system_report_exporter {

    protected $customtable = null;

    public function __construct(
            \core\persistent $persistent,
            $related = array(),
            $customtable = null) {
        parent::__construct($persistent, $related);

        if ($customtable) {
            $this->customtable = $customtable;
        } else {
            $this->customtable = eabc_external_report_table::class;
        }
    }

    /**
     * Get additional values to inject while exporting
     *
     * @uses \core_reportbuilder\output\renderer::render_system_report_table()
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_other_values(renderer_base $output): array {
        /** @var system_report $source */
        $source = $this->related['source'];

        /** @var string $parameters */
        $parameters = $this->related['parameters'];

        /** @var int $reportid */
        $reportid = $this->persistent->get('id');

        // We store the report ID and parameters within the table filterset so that they are available between AJAX requests.
        $filterset = new system_report_table_filterset();
        $filterset->add_filter(new integer_filter('reportid', null, [$reportid]));
        $filterset->add_filter(new string_filter('parameters', null, [$parameters]));

        $params = (array) json_decode($parameters, true);
        $table = $this->customtable::create($reportid, $params);
        $table->set_filterset($filterset);

        // Generate filters form if report uses the default form, and contains any filters.
        $filterspresent = $source->get_filter_form_default() && !empty($source->get_active_filters());
        if ($filterspresent && empty($params['download'])) {
            $filtersform = new filter(null, null, 'post', '', [], true, [
                'reportid' => $reportid,
                'parameters' => $parameters,
            ]);
            $filtersform->set_data_for_dynamic_submission();
        }

        // Get the report classes and attributes.
        $sourceattributes = $source->get_attributes();
        if (isset($sourceattributes['class'])) {
            $classes = $sourceattributes['class'];
            unset($sourceattributes['class']);
        }
        $attributes = array_map(static function($key, $value): array {
            return ['name' => $key, 'value' => $value];
        }, array_keys($sourceattributes), $sourceattributes);

        return [
            'table' => $output->render($table),
            'parameters' => $parameters,
            'filterspresent' => $filterspresent,
            'filtersapplied' => $source->get_applied_filter_count(),
            'filtersform' => $filterspresent ? $filtersform->render() : '',
            'attributes' => $attributes,
            'classes' => $classes ?? '',
        ];
    }

}
