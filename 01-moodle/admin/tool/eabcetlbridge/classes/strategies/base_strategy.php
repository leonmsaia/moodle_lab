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

/**
 * Define the contract for all migration strategies.
 *
 * @package   tool_eabcetlbridge
 * @category  strategies
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_eabcetlbridge\strategies;

use tool_eabcetlbridge\persistents\{configs, batch_files};
use tool_eabcetlbridge\{utils};

/**
 * Base class for all ETL migration strategies.
 * Each strategy implements the specific logic for a data type (e.g., Grades, Users).
 */
abstract class base_strategy {

    /** @var configs The migration configuration record (mdl_eabcetlbridge_config). */
    protected $config;

    /** @var batch_files $batchfile The batch file object. */
    protected $batchfile;

    /** @var csv_import_reader */
    protected $cir = null;
    /** @var int */
    protected $qtylines = 0;
    /** @var bool Contains true if the object has been validated */
    protected $validated = false;
    /** @var array Contains the validation errors */
    protected $errors = array();

    /**
     * Constructor.
     *
     * @param configs $config The migration configuration record.
     */
    public function __construct($config = null, $batchfile = null) {
        if ($config) {
            $this->config = $config;
        }
        if ($batchfile) {
            $this->batchfile = $batchfile;
        }
    }

    /**
     * Get the name of the strategy.
     *
     * This function is useful for differentiating between different migration strategies.
     *
     * @return string The name of the strategy.
     */
    public static function get_name() {
        return 'Estrategia Base';
    }

    /**
     * Returns a list of all strategy classes.
     *
     * @return class-string<self>[] an associative array with the class name as the value.
     */
    public static function get_strategies() {
        $baseclass = self::class;
        $component = 'tool_eabcetlbridge';
        $namespace = '';

        /** @var class-string<self>[] $classes */
        $classes = utils::get_child_classes($baseclass, $component, $namespace);

        $list = [];
        foreach ($classes as $class) {
            $list[$class] = $class::get_name();
        }
        return $list;
    }

    /**
     * Maps the CSV headers to the corresponding grade item ID.
     *
     * @param array $params The parameters for the mapping.
     *     - param 1:
     *     - param 2:
     *     - param 3:
     * @return array The mapping of CSV headers to grade item IDs.
     */
    public function get_grade_import_mapping($params = []): array {
        return [];
    }

    /**
     * Process the CSV file.
     */
    public function process_csv() {

    }

    /**
     * Process the data.
     */
    public function process() {

    }

}
