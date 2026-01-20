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

namespace tool_eabcetlbridge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;
use core\exception\moodle_exception;
use Exception;

/**
 * External database connection manager for Moodle 3.5
 *
 * This class handles creating and managing connections to the Moodle 3.5 database
 * from within Moodle 4.5 for data migration purposes.
 *
 * @package    local_restoration
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_external_db_connection {

    /** @var moodle_database|null Singleton instance of the Moodle 3.5 database connection */
    private static $db35_instance = null;

    /**
     * Get a connection to the Moodle 3.5 database
     *
     * Creates a new database connection using the configuration stored in $CFG->moodle35_db
     * Uses the same moodle_database class as the main $DB object.
     *
     * @return moodle_database Database connection object
     * @throws moodle_exception If connection fails or configuration is missing
     */
    public static function get_moodle35_connection() {
        global $CFG;

        // Return existing connection if already created
        if (self::$db35_instance !== null) {
            return self::$db35_instance;
        }

        // Validate configuration exists
        if (!isset($CFG->moodle35_db) || !is_array($CFG->moodle35_db)) {
            throw new moodle_exception('error', 'local_restoration', '', null,
                'Missing $CFG->moodle35_db configuration. Please add it to config.php');
        }

        $config = $CFG->moodle35_db;

        // Validate required fields
        $required = ['dbtype', 'dblibrary', 'dbhost', 'dbname', 'dbuser', 'dbpass', 'prefix'];
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new moodle_exception('error', 'local_restoration', '', null,
                    "Missing required field '$field' in \$CFG->moodle35_db configuration");
            }
        }

        // Get the database driver instance
        require_once($CFG->libdir . '/dml/moodle_database.php');
        require_once($CFG->libdir . '/dmllib.php');

        $db35 = moodle_database::get_driver_instance($config['dbtype'], $config['dblibrary'], true);

        if (!$db35) {
            throw new moodle_exception('error', 'local_restoration', '', null,
                "Failed to get database driver instance for {$config['dbtype']}/{$config['dblibrary']}");
        }

        // Set default options if not provided
        $dboptions = isset($config['dboptions']) ? $config['dboptions'] : [];
        if (!isset($dboptions['dbport'])) {
            $dboptions['dbport'] = 3306;
        }
        if (!isset($dboptions['dbcollation'])) {
            $dboptions['dbcollation'] = 'utf8mb4_unicode_ci';
        }

        // Attempt connection
        try {
            $connected = $db35->connect(
                $config['dbhost'],
                $config['dbuser'],
                $config['dbpass'],
                $config['dbname'],
                $config['prefix'],
                $dboptions
            );

            if (!$connected) {
                throw new moodle_exception('error', 'local_restoration', '', null,
                    'Database connection returned false');
            }

        } catch (Exception $e) {
            throw new moodle_exception('error', 'local_restoration', '', null,
                'Failed to connect to Moodle 3.5 database: ' . $e->getMessage());
        }

        // Store the instance
        self::$db35_instance = $db35;

        return $db35;
    }

    /**
     * Validate that a database connection is working
     *
     * @param moodle_database $db Database connection to validate
     * @return bool True if connection is valid and working
     */
    public static function validate_connection($db) {
        if (!$db) {
            return false;
        }

        try {
            // Try a simple query
            $result = $db->get_record_sql("SELECT 1 as test");
            return ($result && isset($result->test) && $result->test == 1);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Close the Moodle 3.5 database connection
     *
     * @param moodle_database|null $db Database connection to close (optional)
     * @return void
     */
    public static function close_connection($db = null) {
        if ($db === null && self::$db35_instance !== null) {
            $db = self::$db35_instance;
        }

        if ($db) {
            $db->dispose();
            self::$db35_instance = null;
        }
    }

    /**
     * Get user ID from Moodle 3.5 by username
     *
     * @param moodle_database $db35 Connection to Moodle 3.5
     * @param string $username Username to search for
     * @return int|null User ID or null if not found
     */
    public static function get_user_id_by_username($db35, $username) {
        try {
            $user = $db35->get_record('user', ['username' => $username], 'id');
            return $user ? $user->id : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get course ID from Moodle 3.5 by shortname
     *
     * @param moodle_database $db35 Connection to Moodle 3.5
     * @param string $shortname Course shortname
     * @return int|null Course ID or null if not found
     */
    public static function get_course_id_by_shortname($db35, $shortname) {
        try {
            $course = $db35->get_record('course', ['shortname' => $shortname], 'id');
            return $course ? $course->id : null;
        } catch (Exception $e) {
            return null;
        }
    }

}
