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

namespace tool_eabcetlbridge\external;

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . '/externallib.php');

use external_value;
use external_single_structure;
use external_multiple_structure;
use external_function_parameters;
use context_system;
use tool_eabcetlbridge\strategies\user_grades_strategy;

/**
 * Receive user grades
 *
 * @package   tool_eabcetlbridge
 * @category  external
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class receive_user_grades extends \external_api {

    /**
     * Returns parameters for the execute method.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'csvfilename' => new external_value(PARAM_TEXT, 'The name of the CSV file.'),
            'csvdata' => new external_value(PARAM_RAW, 'The base64 encoded CSV data.')
        ]);
    }

    /**
     * Web service that receives a CSV with grades for a single user across multiple courses and processes it.
     *
     * @param string $csvfilename The name of the file.
     * @param string $csvdata The base64 encoded CSV content.
     * @return array A summary of the processing.
     */
    public static function execute($csvfilename, $csvdata) {

        // Validate parameters.
        try {
            $params = self::validate_parameters(self::execute_parameters(), [
                'csvfilename' => $csvfilename,
                'csvdata' => $csvdata
            ]);

            // The service must be executed by a user with the right capabilities.
            $context = context_system::instance();
            self::validate_context($context);
            require_capability('moodle/site:config', $context);

            // Decode the CSV data.
            $csvcontent = base64_decode($params['csvdata']);
            if ($csvcontent === false) {
                throw new \Exception('Invalid CSV data.');
            }

            // Instantiate and use the new strategy to process the CSV content directly.
            $strategy = new user_grades_strategy();
            $strategy->set_csv_content($csvcontent);
            $result = $strategy->process_csv();

        } catch (\Exception $e) {
            // The 'warnings' field must match the external_multiple_structure defined in execute_returns.
            return [
                'status' => 'error',
                'coursesprocessed' => 0,
                'gradesupdated' => 0,
                'warnings' => [[
                    'warningcode' => 'exception',
                    'message' => $e->getMessage()
                ]]
            ];
        }

        return [
            'status' => 'success',
            'coursesprocessed' => $result['courses_processed'],
            'gradesupdated' => $result['grades_updated'],
            'warnings' => $result['warnings']
        ];
    }

    /**
     * Define the return structure for the web service.
     */
    public static function execute_returns() {
        return new \external_single_structure(
            [
                'status' => new external_value(PARAM_TEXT, 'The status of the operation (e.g., success).'),
                'coursesprocessed' => new external_value(PARAM_INT, 'Number of courses processed from the CSV.'),
                'gradesupdated' => new external_value(PARAM_INT, 'Total number of grade items updated.'),
                'warnings' => new external_multiple_structure(
                    new external_single_structure([
                        'warningcode' => new external_value(PARAM_ALPHANUMEXT, 'A code for the warning.'),
                        'message' => new external_value(PARAM_TEXT, 'The warning message.')
                    ]),
                    'List of warnings during processing.',
                    VALUE_DEFAULT
                )
            ]
        );
    }

}
