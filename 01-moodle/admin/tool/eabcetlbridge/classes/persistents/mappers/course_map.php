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

/**
 * Persistent for id mapping for courses.
 *
 * @package   tool_eabcetlbridge
 * @category  persistents
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_map extends id_map {

    const SOURCETYPE = 'course';
    const SOURCETABLE = 'course';
    const SOURCEKEY = 'shortname';
    const TARGETID = 'id';

    /**
     * {@inheritdoc}
     */
    protected static function define_properties() {
        return [
            'source_type' => ['type' => PARAM_TEXT, 'default' => self::SOURCETYPE],
            'source_key' => ['type' => PARAM_TEXT],
            'target_id' => ['type' => PARAM_INT]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function get_new_records_sql() {
        $sourcetype = self::SOURCETYPE;
        $sourcetable = self::SOURCETABLE;
        $sourcekey = self::SOURCEKEY;
        $targetid = self::TARGETID;
        $prefix = self::PREFIXTABLE;
        $sql = "SELECT t.{$targetid}, t.{$sourcekey}
                  FROM {{$sourcetable}} t
             LEFT JOIN {eabcetlbridge_id_map} $prefix
                       ON {$prefix}.source_key = t.{$sourcekey}
                       AND {$prefix}.source_type = '$sourcetype'
                 WHERE {$prefix}.id IS NULL";
        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    public static function get_updated_records_sql() {
        global $DB;

        $sourcetype = self::SOURCETYPE;
        $sourcetable = self::SOURCETABLE;
        $sourcekey = self::SOURCEKEY;
        $targetid = self::TARGETID;
        $prefix = self::PREFIXTABLE;
        $fields = self::get_sql_fields($prefix, $prefix);

        $diferent = $DB->sql_equal("{$prefix}.source_key", "t.{$sourcekey}", true, true, true);

        $sql = "SELECT {$fields}, t.{$sourcekey} as new_source_key
                  FROM {eabcetlbridge_id_map} $prefix
                  JOIN {{$sourcetable}} t
                       ON t.{$targetid} = {$prefix}.target_id
                 WHERE {$prefix}.source_type = '$sourcetype'
                       AND {$diferent}";

        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    public static function get_deleted_records_sql() {
        /** @global \moodle_database $DB */
        global $DB;
        $sourcetype = self::SOURCETYPE;
        $sourcetable = self::SOURCETABLE;
        $sourcekey = self::SOURCEKEY;
        $targetid = self::TARGETID;
        $prefix = self::PREFIXTABLE;

        $diferent = $DB->sql_equal("{$prefix}.source_key", "t.{$sourcekey}", true, true, true);

        $sql = "SELECT $prefix.id
                  FROM {eabcetlbridge_id_map} $prefix
             LEFT JOIN {{$sourcetable}} t ON t.{$targetid} = $prefix.target_id
                 WHERE $prefix.source_type = '{$sourcetype}'
                       AND (t.id IS NULL OR {$diferent})";
        return $sql;
    }

}
