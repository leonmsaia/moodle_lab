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

namespace tool_eabcetlbridge\persistents\mappers\user_grade_migration;

use tool_eabcetlbridge\persistents\mappers\id_map;

/**
 * User Planner persistent.
 *
 * @package   tool_eabcetlbridge
 * @category  mappers
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users_by_file extends id_map {

    /**
     * Table name for the persistent.
     * @var string
     */
    const TABLE = 'eabcetlbridge_id_map';

    /** @var string The source type of the records (e.g., 'user', 'course') */
    const SOURCETYPE = 'user_grade_migration';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'source_type' => ['type' => PARAM_TEXT, 'default' => self::SOURCETYPE],
            'source_key' => ['type' => PARAM_TEXT], // Username.
            'courseid' => ['type' => PARAM_INT],
            'target_id' => ['type' => PARAM_INT, 'default' => 0], // Batchfileid.
            'customint1' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'customint2' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'customchar1' => ['type' => PARAM_TEXT, 'default' => '', 'null' => NULL_ALLOWED],
            'customchar2' => ['type' => PARAM_TEXT, 'default' => '', 'null' => NULL_ALLOWED]
        ];
    }
    /**
     * Register users from a file.
     * This will insert new records for users that are not already in the id_map for the given course and source type.
     *
     * @param int $batchfileid The id of the batch file that has been processed.
     * @param int $courseid The id of the course that has been processed.
     * @param array $usernames An array of usernames
     */
    public static function register_users(
        int $batchfileid,
        int $courseid,
        array $usernames = []
    ) {
        /** @global \moodle_database $DB */
        global $DB;

        if (empty($usernames)) {
            return;
        }

        $table = self::TABLE;

        // Find which users already exist for this course and source_type.
        [$insql, $inparams] = $DB->get_in_or_equal($usernames);
        $sql = "SELECT source_key
                  FROM {{$table}}
                 WHERE source_type = ? AND courseid = ? AND source_key " . $insql;
        $params = array_merge([self::SOURCETYPE, $courseid], $inparams);
        $existingusers = $DB->get_fieldset_sql($sql, $params);

        // Determine which users are new.
        $newusers = array_diff($usernames, $existingusers);

        if (empty($newusers)) {
            return;
        }

        // Prepare records for batch insertion.
        $records = [];
        foreach ($newusers as $username) {
            $record = new \stdClass();
            $record->source_type = self::SOURCETYPE;
            $record->source_key = $username;
            $record->courseid = $courseid;
            $record->target_id = $batchfileid;
            $records[] = static::clean_record($record);
        }

        $DB->insert_records(self::TABLE, $records);
    }

    /**
     * Updates the batchfileid (target_id) for a list of users in a specific course.
     *
     * @param int $batchfileid The new batch file id to set.
     * @param int $courseid The course id.
     * @param array $usernames The usernames to update.
     */
    public static function update_users_batch(int $batchfileid, int $courseid, array $usernames = []) {
        /** @global \moodle_database $DB */
        global $DB;

        if (empty($usernames)) {
            return;
        }

        [$userisinsql, $userisinparams] = $DB->get_in_or_equal($usernames, SQL_PARAMS_NAMED, 'username');
        $params = array_merge($userisinparams, [
            'target_id' => $batchfileid,
            'timemodified' => time(),
            'courseid' => $courseid,
            'source_type' => self::SOURCETYPE
        ]);

        $table = self::TABLE;

        $sql = "UPDATE {{$table}}
                       SET target_id = :target_id,
                           timemodified = :timemodified
                     WHERE source_key $userisinsql
                           AND courseid = :courseid
                           AND source_type = :source_type";

        $DB->execute($sql, $params);
    }

    /**
     * Inserts or updates user records in the id_map table.
     * If a user for the given course and source type already exists, it updates the target_id.
     * If not, it inserts a new record.
     *
     * @param int $batchfileid The id of the batch file (target_id).
     * @param int $courseid The course id.
     * @param array $usernames An array of usernames (source_key).
     */
    public static function register_or_update_users(int $batchfileid, int $courseid, array $usernames = []) {
        /** @global \moodle_database $DB */
        global $DB;

        if (empty($usernames)) {
            return;
        }

        $table = self::TABLE;

        // 1. Find which users already exist for this course and source_type.
        [$insql, $inparams] = $DB->get_in_or_equal($usernames, SQL_PARAMS_NAMED, 'source_key');
        $sql = "SELECT source_key
                  FROM {{$table}}
                 WHERE source_type = :source_type
                       AND courseid = :courseid
                       AND source_key $insql";
        $params = array_merge([
            'source_type' => self::SOURCETYPE,
            'courseid' => $courseid
        ], $inparams);
        $existingusers = $DB->get_fieldset_sql($sql, $params);

        // 2. Determine which users are new and which need updating.
        $userstoinsert = array_diff($usernames, $existingusers);
        $userstoupdate = $existingusers;

        // 3. Insert new users in a single batch operation.
        if (!empty($userstoinsert)) {
            $records = [];
            foreach ($userstoinsert as $username) {
                $record = new \stdClass();
                $record->source_type = self::SOURCETYPE;
                $record->source_key = $username;
                $record->courseid = $courseid;
                $record->target_id = $batchfileid;
                $records[] = static::clean_record($record);
            }
            $DB->insert_records(self::TABLE, $records);
        }

        // 4. Update existing users in a single batch operation.
        if (!empty($userstoupdate)) {
            [$userisinsql, $userisinparams] = $DB->get_in_or_equal($userstoupdate, SQL_PARAMS_NAMED, 'username');
            $params = array_merge($userisinparams, [
                'target_id' => $batchfileid,
                'timemodified' => time(),
                'courseid' => $courseid,
                'source_type' => self::SOURCETYPE
            ]);

            $sql = "UPDATE {{$table}}
                           SET target_id = :target_id,
                               timemodified = :timemodified
                         WHERE source_key $userisinsql
                               AND source_type = :source_type
                               AND courseid = :courseid";
            $DB->execute($sql, $params);
        }
    }
}
