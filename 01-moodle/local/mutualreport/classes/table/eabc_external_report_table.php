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


namespace local_mutualreport\table;

defined('MOODLE_INTERNAL') || die;

use core\param;
use html_writer;
use moodle_exception;
use core_reportbuilder\manager;
use core_reportbuilder\local\models\report;
use core_reportbuilder\local\report\column;
use core_reportbuilder\table\system_report_table;
use local_mutualreport\utils35;

/**
 * System report dynamic table class
 *
 * @package     core_reportbuilder
 * @copyright   2020 Paul Holden <paulh@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eabc_external_report_table extends system_report_table {

    /** @var string Unique ID prefix for the table */
    private const UNIQUEID_PREFIX = 'system-report-table-';

    /** @var \moodle_database External database connection */
    protected $externaldb = null;

    /**
     * Table constructor. Note that the passed unique ID value must match the pattern "system-report-table-(\d+)" so that
     * dynamic updates continue to load the same report
     *
     * @param string $uniqueid
     * @param array $parameters
     * @throws moodle_exception For invalid unique ID
     */
    public function __construct(string $uniqueid, array $parameters = []) {
        if (!preg_match('/^' . self::UNIQUEID_PREFIX . '(?<id>\d+)$/', $uniqueid, $matches)) {
            throw new moodle_exception('invalidsystemreportid', 'core_reportbuilder', '', null, $uniqueid);
        }

        parent::__construct($uniqueid);

        // If we are loading via a dynamic table AJAX request, defer the report loading until the filterset is added to
        // the table, as it is used to populate the report $parameters during construction.
        $serviceinfo = optional_param('info', null, PARAM_RAW);
        if ($serviceinfo !== 'core_table_get_dynamic_table_content') {
            $this->eabc_load_report_instance((int) $matches['id'], $parameters);
        }

        $utils = new utils35();
        $this->externaldb = $utils->db;
    }

    /**
     * Load the report persistent, and accompanying system report instance.
     *
     * @param int $reportid
     * @param array $parameters
     */
    private function eabc_load_report_instance(int $reportid, array $parameters): void {
        global $PAGE;

        $this->persistent = new report($reportid);
        $this->report = manager::get_report_from_persistent($this->persistent, $parameters);

        // TODO: can probably be removed pending MDL-72974.
        $PAGE->set_context($this->persistent->get_context());

        $fields = $this->report->get_base_fields();
        $groupby = [];
        $maintable = $this->report->get_main_table();
        $maintablealias = $this->report->get_main_table_alias();
        $joins = $this->report->get_joins();
        [$where, $params] = $this->report->get_base_condition();

        $this->set_attribute('data-region', 'reportbuilder-table');
        $this->set_attribute('class', $this->attributes['class'] . ' reportbuilder-table');

        // Download options.
        $this->showdownloadbuttonsat = [TABLE_P_BOTTOM];
        $this->is_downloading($parameters['download'] ?? null, $this->report->get_downloadfilename());

        // Retrieve all report columns. If we are downloading the report, remove as required.
        $columns = $this->report->get_active_columns();
        if ($this->is_downloading()) {
            $columns = array_diff_key($columns,
                array_flip($this->report->get_exclude_columns_for_download()));
        }

        // If we are aggregating any columns, we should group by the remaining ones.
        $aggregatedcolumns = array_filter($columns, static function(column $column): bool {
            return !empty($column->get_aggregation());
        });

        $hasaggregatedcolumns = !empty($aggregatedcolumns);
        if ($hasaggregatedcolumns) {
            $groupby = $fields;
        }

        $columnheaders = $columnsattributes = [];

        // Check whether report has checkbox toggle defined, note that select all is excluded during download.
        if (($checkbox = $this->report->get_checkbox_toggleall(true)) && !$this->is_downloading()) {
            $columnheaders['selectall'] = $PAGE->get_renderer('core')->render($checkbox);
            $this->no_sorting('selectall');
        }

        $columnindex = 1;
        foreach ($columns as $identifier => $column) {
            $column->set_index($columnindex++);

            $columnheaders[$column->get_column_alias()] = $column->get_title();

            // Specify whether column should behave as a user fullname column unless the column has a custom title set.
            if (preg_match('/^user:fullname.*$/', $column->get_unique_identifier()) && !$column->has_custom_title()) {
                $this->userfullnamecolumns[] = $column->get_column_alias();
            }

            // We need to determine for each column whether we should group by its fields, to support aggregation.
            if ($hasaggregatedcolumns && empty($column->get_aggregation())) {
                $groupby = array_merge($groupby, $column->get_groupby_sql());
            }

            // Add each columns fields, joins and params to our report.
            $fields = array_merge($fields, $column->get_fields());
            $joins = array_merge($joins, $column->get_joins());
            $params = array_merge($params, $column->get_params());

            // Disable sorting for some columns.
            if (!$column->get_is_sortable()) {
                $this->no_sorting($column->get_column_alias());
            }

            // Generate column attributes to be included in each cell.
            $columnsattributes[$column->get_column_alias()] = $column->get_attributes();
        }

        // If the report has any actions then append appropriate column, note that actions are excluded during download.
        if ($this->report->has_actions() && !$this->is_downloading()) {
            $columnheaders['actions'] = html_writer::tag('span', get_string('actions', 'core_reportbuilder'), [
                'class' => 'sr-only',
            ]);
            $this->no_sorting('actions');
        }

        $this->define_columns(array_keys($columnheaders));
        $this->define_headers(array_values($columnheaders));

        // Add column attributes to the table.
        $this->set_columnsattributes($columnsattributes);

        // Initial table sort column.
        if ($sortcolumn = $this->report->get_initial_sort_column()) {
            $this->sortable(true, $sortcolumn->get_column_alias(), $this->report->get_initial_sort_direction());
        }

        // Table configuration.
        $this->initialbars(false);
        $this->collapsible(false);
        $this->pageable(true);
        $this->set_default_per_page($this->report->get_default_per_page());

        // Initialise table SQL properties.
        $fieldsql = implode(', ', $fields);
        $this->init_sql($fieldsql, "{{$maintable}} {$maintablealias}", $joins, $where, $params, $groupby);
    }

    /**
     * Query the db. Store results in the table object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar. Bar
     * will only be used if there is a fullname column defined for the table.
     */
    public function query_db($pagesize, $useinitialsbar = true): void {
        //global $DB;

        if (!$this->is_downloading()) {
            $this->pagesize($pagesize, $this->externaldb->count_records_sql($this->countsql, $this->countparams));

            $this->rawdata = $this->externaldb->get_recordset_sql($this->get_table_sql(), $this->sql->params, $this->get_page_start(),
                $this->get_page_size());
        } else {
            $this->rawdata = $this->externaldb->get_recordset_sql($this->get_table_sql(), $this->sql->params);
        }
    }

    /**
     * Convenience method to call a number of methods for you to display the
     * table.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        //global $DB;

        if (!$this->columns) {
            $onerow = $this->externaldb->get_record_sql(
                "SELECT {$this->sql->fields} FROM {$this->sql->from} WHERE {$this->sql->where}",
                $this->sql->params,
                IGNORE_MULTIPLE
            );
            // If columns is not set then define columns as the keys of the rows returned
            // from the db.
            $this->define_columns(array_keys((array)$onerow));
            $this->define_headers(array_keys((array)$onerow));
        }
        $this->pagesize = $pagesize;
        $this->setup();
        $this->query_db($pagesize, $useinitialsbar);
        $this->build_table();
        $this->close_recordset();
        $this->finish_output();
    }

    /**
     * Get the html for the download buttons
     *
     * @return string
     */
    public function download_buttons(): string {
        global $OUTPUT;

        if ($this->report->can_be_downloaded() && !$this->is_downloading()) {
            return $OUTPUT->download_dataformat_selector(
                get_string('downloadas', 'table'),
                new \moodle_url('/local/mutualreport/pages/download35.php'),
                'download',
                [
                    'id' => $this->persistent->get('id'),
                    'parameters' => json_encode($this->report->get_parameters()),
                ]
            );
        }

        return '';
    }

    /**
     * Override start of HTML to remove top pagination.
     */
    public function start_html() {
        // Render the dynamic table header.
        echo $this->get_dynamic_table_html_start();

        // Render button to allow user to reset table preferences.
        echo $this->render_reset_button();

        echo $this->download_buttons();

        $this->wrap_html_start();

        $this->set_caption($this->report::get_name(), ['class' => 'sr-only']);

        echo html_writer::start_tag('div');
        echo html_writer::start_tag('table', $this->attributes) . $this->render_caption();
    }

}
