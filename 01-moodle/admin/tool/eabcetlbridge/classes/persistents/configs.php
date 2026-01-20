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

namespace tool_eabcetlbridge\persistents;

/**
 * Persistent for migration configuration.
 *
 * @package   tool_eabcetlbridge
 * @category  persistents
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class configs extends base_persistent {

    /**
     * Table name for the persistent.
     * @var string
     */
    const TABLE = 'eabcetlbridge_config';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'name' => ['type' => PARAM_TEXT],
            'shortname' => ['type' => PARAM_USERNAME],
            'strategyclass' => ['type' => PARAM_TEXT],
            'sourcequery' => ['type' => PARAM_RAW, 'default' => ''],
            'mapping' => ['type' => PARAM_RAW, 'default' => ''],
            'isenabled' => ['type' => PARAM_INT, 'default' => self::STATUS_ENABLED],
            'isautomatic' => ['type' => PARAM_INT, 'default' => self::STATUS_DISABLED],
            'lastruntime' => ['type' => PARAM_INT, 'default' => 0],
        ];
    }

    /**
     * Returns a list of migration configuration names which are enabled.
     *
     * This list is used in the manual upload form to allow the user to select
     * which configuration to use for the migration.
     *
     * @return array
     */
    public static function get_configs_for_manual_upload() {
        $list = [];
        $records = self::get_records(['isenabled' => self::STATUS_ENABLED]);
        foreach ($records as $record) {
            $list[$record->get('id')] = "{$record->get('name')}  [{$record->get('strategyclass')}] ";
        }
        return $list;
    }

    /**
     * Returns a list of migration configuration records which are enabled and automatic.
     *
     * These records are used in the cron job to automate the migration process.
     *
     * @return self[]
     */
    public static function get_automatic_configs() {
        return self::get_records([
            'isautomatic' => self::STATUS_ENABLED,
            'isenabled' => self::STATUS_ENABLED
        ]);
    }

    /**
     * Returns an array of enabled status options with their translated names.
     *
     * The status options are:
     * - STATUS_ENABLED (Enabled): The configuration is enabled.
     * - STATUS_DISABLED (Disabled): The configuration is disabled.
     *
     * @return array
     */
    public static function get_isenabled_options() {
        return [
            self::STATUS_ENABLED => 'Activado',
            self::STATUS_DISABLED => 'Desactivado',
        ];
    }

    /**
     * Returns an array of enabled status options with their translated names.
     *
     * The status options are:
     * - STATUS_ENABLED (Enabled): The configuration is automatic.
     * - STATUS_DISABLED (Disabled): The configuration is not automatic.
     *
     * @return array
     */
    public static function get_isautomatic_options() {
        return [
            self::STATUS_ENABLED => 'Activado',
            self::STATUS_DISABLED => 'Desactivado',
        ];
    }

    /**
     * Gets the latest configuration for a given strategy, or creates a default one if none exists.
     *
     * This is used to ensure that automated processes that require a configuration
     * can always find one.
     *
     * @return self The found or newly created configuration object.
     */
    public static function get_default_or_create() {
        $strategyclass = 'tool_eabcetlbridge\strategies\grades_strategy';

        // Try to find an existing config, get the latest one.
        $records = self::get_records(['strategyclass' => $strategyclass], 'id');

        if (!empty($records)) {
            // Return the last created one.
            return reset($records);
        }

        // If not found, create a new one with default values.
        $configdata = (object) [
            'name' => 'Configuración Automática de Calificaciones',
            'shortname' => 'auto_grades_migration',
            'strategyclass' => $strategyclass,
            'isenabled' => self::STATUS_ENABLED,
            'isautomatic' => self::STATUS_DISABLED, // Disabled by default for safety.
        ];

        $newconfig = new self(0, $configdata);
        $newconfig->create();
        return $newconfig;
    }

}
