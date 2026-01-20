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

use lang_string;
use core\context\system;

/**
 * Persistent for batch files configuration.
 *
 * @package   tool_eabcetlbridge
 * @category  persistents
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class batch_files extends base_persistent {

    /**
     * Table name for the persistent.
     * @var string
     */
    const TABLE = 'eabcetlbridge_batch_file';

    /** @var string Name of the component where the files are stored */
    const COMPONENT = 'tool_eabcetlbridge';
    /** @var string Name of the file area where the files are stored */
    const FILEAREA = 'migration_files';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'configid' => ['type' => PARAM_INT, 'default' => 0],
            'courseid' => ['type' => PARAM_INT, 'null' => NULL_ALLOWED],
            'logid' => ['type' => PARAM_INT, 'null' => NULL_ALLOWED, 'default' => 0],
            'component' => ['type' => PARAM_TEXT, 'default' => self::COMPONENT],
            'filearea' => ['type' => PARAM_TEXT, 'default' => self::FILEAREA],
            'filename' => ['type' => PARAM_TEXT, 'default' => ''],
            'filepath' => ['type' => PARAM_TEXT, 'default' => ''],
            'type' => ['type' => PARAM_INT, 'default' => self::TYPE_MANUAL],
            'delimiter' => ['type' => PARAM_TEXT, 'default' => 'comma'],
            'encoding' => ['type' => PARAM_TEXT, 'default' => 'UTF-8'],
            'qtylines' => ['type' => PARAM_INT, 'default' => 0],
            'qtyrecords' => ['type' => PARAM_INT, 'default' => 0],
            'qtyrecordsprocessed' => ['type' => PARAM_INT, 'default' => 0],
            'status' => ['type' => PARAM_INT, 'default' => self::STATUS_PENDING],
            'errormessages' => ['type' => PARAM_RAW, 'null' => NULL_ALLOWED, 'default' => ''],
            'settings' => ['type' => PARAM_RAW, 'default' => ''],
        ];
    }

    /**
     * Return the available status options with their translated names for manual upload.
     *
     * These status options are used when the user uploads a file manually.
     * The status options are:
     * - STATUS_PREVIEW (Pending): The file is uploaded successfully but not yet processed.
     * - STATUS_DISABLED (Processing): The file is being processed. The file is uploaded successfully.
     * - STATUS_PENDING (Pending): The file is uploaded successfully but not yet processed.
     *
     * @return array
     */
    public static function get_status_for_manual_upload() {
        return [
            self::STATUS_PREVIEW => get_string('status_preview', 'tool_eabcetlbridge'),
            self::STATUS_DISABLED => get_string('status_disabled', 'tool_eabcetlbridge'),
            self::STATUS_PENDING => get_string('status_pending', 'tool_eabcetlbridge'),
        ];
    }

    /**
     * Return the available type options with their translated names.
     *
     * The type options are:
     * - TYPE_MANUAL (Manual): The file is uploaded manually by the user.
     * - TYPE_AUTOMATED (Automated): The file is uploaded automatically by a cron job.
     *
     * @return array
     */
    public static function get_type_options() {
        return [
            self::TYPE_MANUAL => get_string('type_manual', 'tool_eabcetlbridge'),
            self::TYPE_AUTOMATED => get_string('type_automated', 'tool_eabcetlbridge'),
        ];
    }

    /**
     * Returns an array of type options for report view.
     * @return array
     */
    public static function get_type_options_for_view() {
        return [
            'default' => [
                'text' => 'Inconsistente',
                'color' => 'badge badge-warning'
            ],
            'checknull' => [
                'text' => 'NULL?',
                'color' => 'badge badge-warning'
            ],
            'options' => [
                self::TYPE_MANUAL => [
                    'text' => new lang_string('type_manual', 'tool_eabcetlbridge'),
                    'color' => 'badge badge-info'
                ],
                self::TYPE_AUTOMATED => [
                    'text' => new lang_string('type_automated', 'tool_eabcetlbridge'),
                    'color' => 'badge badge-primary'
                ],
            ]
        ];
    }

    /**
     * Returns a list of pending files for the queue.
     *
     * The query is filtered by STATUS_PENDING and enabled configurations.
     *
     * @return self[]
     */
    public static function get_pending_files_for_queue() {
        global $DB;

        $fields = self::get_sql_fields('bf', 'bf');
        $batchfiles = self::TABLE;
        $configs = configs::TABLE;

        $params = [
            'status' => self::STATUS_PENDING,
            'isenabled' => configs::STATUS_ENABLED
        ];

        $sql = "SELECT {$fields}
                  FROM {{$batchfiles}} bf
                  JOIN {{$configs}} c ON c.id = bf.configid
                 WHERE bf.status = :status
                       AND c.isenabled = :isenabled";

        $instances = [];
        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $row) {
            $record = self::extract_record($row, 'bf');
            $instances[] = new self(0, $record);
        }

        return $instances;
    }

    /**
     * Return the content of the file associated with the given batch file id.
     *
     * @param static $batchfile The batch file object.
     * @return string|false The file content or false if the file does not exist.
     */
    public function get_file_content() {
        $fs = get_file_storage();
        $context = system::instance();
        if (!$files = $fs->get_area_files(
                $context->id,
                $this->get('component'),
                $this->get('filearea'),
                $this->get('id'),
                'id DESC',
                false)) {
            return false;
        }
        $file = reset($files);
        return $file->get_content();
    }

}
