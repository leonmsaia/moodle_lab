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

namespace tool_eabcetlbridge\persistents\mappers;

use stdClass;
use tool_eabcetlbridge\persistents\base_persistent;

/**
 * Persistent for id mapping.
 *
 * @package   tool_eabcetlbridge
 * @category  persistents
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class id_map extends base_persistent {

    /**
     * Table name for the persistent.
     * @var string
     */
    const TABLE = 'eabcetlbridge_id_map';

    /** @var string The source type of the records (e.g., 'user', 'course') */
    const SOURCETYPE = '';
    /** @var string The table name of the source records (e.g., 'mdl_user', 'mdl_course') */
    const SOURCETABLE = '';
    /** @var string The source key of the source records (e.g., 'username', 'shortname') */
    const SOURCEKEY = '';
    /** @var string The primary key of the target records (e.g., 'id', 'idnumber') */
    const TARGETID = 'id';

    /** @var string The table name for the prefix */
    const PREFIXTABLE = 'eabcmap';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'source_type' => ['type' => PARAM_TEXT],
            'source_key' => ['type' => PARAM_TEXT],
            'courseid' => ['type' => PARAM_INT],
            'target_id' => ['type' => PARAM_INT, 'default' => 0],
            'customint1' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'customint2' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'customchar1' => ['type' => PARAM_TEXT, 'default' => '', 'null' => NULL_ALLOWED],
            'customchar2' => ['type' => PARAM_TEXT, 'default' => '', 'null' => NULL_ALLOWED]
        ];
    }

    /**
     * Returns a SQL query string for retrieving new records from the database that
     * haven't been mapped yet.
     *
     * The query uses a LEFT JOIN to find records that don't have a corresponding
     * mapping in the eabcetlbridge_id_map table.
     * The WHERE clause filters out records that have been deleted.
     *
     * @return string The SQL query string.
     */
    public static function get_new_records_sql() {
        return "";
    }

    /**
     * Generates a SQL query that retrieves records from the database that have been
     * updated since the last mapping.
     *
     * @return string A SQL query string that can be used to retrieve the records.
     */
    public static function get_updated_records_sql() {
        return "";
    }

    public static function get_deleted_records_sql() {
        return "";
    }

    /**
     * Returns the number of new records from the database that haven't been mapped yet.
     *
     * This function will execute a SQL query that retrieves the count of new records from the database.
     * The query uses a LEFT JOIN to find records that don't have a mapping in the eabcetlbridge_id_map table.
     * The WHERE clause filters out records that have been deleted.
     *
     * @return int The number of new records from the database that haven't been mapped yet.
     */
    public static function count_new_records() {
        /** @global \moodle_database $DB */
        global $DB;

        $sql = static::get_new_records_sql();

        $sql = "SELECT COUNT(*) FROM ({$sql}) map";

        $count = $DB->count_records_sql($sql);

        return $count;
    }

    /**
     * Returns the number of updated records from the database that need to be remapped.
     *
     * This function will execute a SQL query that retrieves the count of updated records from the database.
     * The query uses a subquery to find records that have been updated since the last mapping.
     * The WHERE clause filters out records that have been deleted.
     *
     * @return int The number of updated records from the database that need to be remapped.
     */
    public static function count_updated_records() {
        /** @global \moodle_database $DB */
        global $DB;

        $sql = static::get_updated_records_sql();

        $sql = "SELECT COUNT(*) FROM ({$sql}) map";

        $count = $DB->count_records_sql($sql);

        return $count;
    }

    /**
     * Returns the number of deleted records from the database that need to be removed from the id mapping.
     *
     * This function will execute a SQL query that retrieves the count of deleted records from the database.
     * The query uses a subquery to find records that have been deleted since the last mapping.
     *
     * @return int The number of deleted records from the database that need to be removed from the id mapping.
     */
    public static function count_deleted_records() {
        /** @global \moodle_database $DB */
        global $DB;

        $sql = static::get_deleted_records_sql();

        $sql = "SELECT COUNT(*) FROM ({$sql}) map";

        $count = $DB->count_records_sql($sql);

        return $count;
    }

    /**
     * Populates the eabcetlbridge_id_map table with new records from the database that haven't been mapped yet.
     *
     * Retrieves new records from the database and inserts them into the eabcetlbridge_id_map table.
     * The function takes two optional parameters: $limitfrom and $limitnum. $limitfrom is the number of records to skip before starting to retrieve records.
     * $limitnum is the number of records to retrieve. If not specified, the default value is 1000.
     *
     * @param int $limitfrom The number of records to skip before starting to retrieve records.
     * @param int $limitnum The number of records to retrieve.
     * @return void
     */
    public static function populate_new_records($limitfrom = 0, $limitnum = 1000) {
        /** @global \moodle_database $DB */
        global $DB;

        if ($limitnum <= 0 || $limitnum == 1000) {
            $configlimitnum = get_config('tool_eabcetlbridge', 'idmapper_limitnum');
            if ($configlimitnum > 0) {
                $limitnum = $configlimitnum;
            } else {
                $limitnum = 1000;
            }
        }

        $sourcetype = static::SOURCETYPE;
        $sourcetable = static::SOURCETABLE;
        $sourcekey = static::SOURCEKEY;
        $targetid = static::TARGETID;
        $sql = static::get_new_records_sql();
        $rs = $DB->get_recordset_sql($sql, null, $limitfrom, $limitnum);
        $records = [];
        foreach ($rs as $row) {
            $mapping = new stdClass();
            $mapping->source_type = $sourcetype;
            $mapping->source_key = $row->{$sourcekey};
            $mapping->target_id = $row->{$targetid};
            $records[] = $mapping;
        }
        $rs->close();

        $DB->insert_records(static::TABLE, $records);
    }

    /**
     * Populates the eabcetlbridge_id_map table with updated records from the source database.
     *
     * Retrieves updated records from the source database and inserts them into
     * the eabcetlbridge_id_map table.
     * The function takes two optional parameters: $limitfrom and $limitnum.
     * $limitfrom is the number of records to skip before starting to retrieve records.
     * $limitnum is the number of records to retrieve. If not specified, the default value is 1000.
     *
     * @param int $limitfrom The number of records to skip before starting to retrieve records.
     * @param int $limitnum The number of records to retrieve.
     * @return array An array of stdClass objects, each containing the updated persistents.
     */
    public static function populate_updated_records($limitfrom = 0, $limitnum = 1000) {
        /** @global \moodle_database $DB */
        global $DB;

        if ($limitnum <= 0) {
            $limitnum = 1000;
        }

        $sql = static::get_updated_records_sql();

        $rs = $DB->get_recordset_sql($sql, null, $limitfrom, $limitnum);
        foreach ($rs as $row) {
            $record = self::extract_record($row, self::PREFIXTABLE);
            $persistent = new static(0, $record);
            $persistent->set('source_key', $row->new_source_key);
            $persistent->update();
        }
        $rs->close();

    }

    /**
     * Deletes records from the eabcetlbridge_id_map table that have been deleted from the source database.
     *
     * Retrieves deleted records from the source database and deletes them from the eabcetlbridge_id_map table.
     * The function takes two optional parameters: $limitfrom and $limitnum.
     * $limitfrom is the number of records to skip before starting to delete records.
     * $limitnum is the number of records to delete. If not specified, the default value is 1000.
     *
     * @param int $limitfrom The number of records to skip before starting to delete records.
     * @param int $limitnum The number of records to delete.
     */
    public static function delete_deleted_records($limitfrom = 0, $limitnum = 1000) {
        /** @global \moodle_database $DB */
        global $DB;

        if ($limitnum <= 0 || $limitnum == 1000) {
            $configlimitnum = get_config('tool_eabcetlbridge', 'idmapper_limitnum');
            if ($configlimitnum > 0) {
                $limitnum = $configlimitnum;
            } else {
                $limitnum = 1000;
            }
        }

        $sql = static::get_deleted_records_sql();

        $todelete = [];
        $rs = $DB->get_recordset_sql($sql, null, $limitfrom, $limitnum);
        foreach ($rs as $row) {
            $todelete[] = $row->id;
        }
        $rs->close();

        [$insql, $inparams] = $DB->get_in_or_equal($todelete);
        $DB->delete_records_select(static::TABLE, "id {$insql}", $inparams);
    }

}
