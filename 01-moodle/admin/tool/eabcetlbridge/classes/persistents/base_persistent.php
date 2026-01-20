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

use core\persistent;
use core\url;
use stdClass;
use tabobject;
use tabtree;
use lang_string;

/**
 * Common persistent
 *
 * @package   tool_eabcetlbridge
 * @category  persistents
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_persistent extends persistent {

    /** @var int The status disabled */
    const STATUS_DISABLED = 0;
    /** @var int The status enabled */
    const STATUS_ENABLED = 1;

    /** @var int Status for preview files. When is uploaded manually */
    const STATUS_PREVIEW = 20;
    /** @var int Status for pending files. */
    const STATUS_PENDING = 30;
    /** @var int Status for files sent to the queue. */
    const STATUS_SENTTOQUEUE = 40;
    /** @var int Status for files being processed. */
    const STATUS_PROCESSING = 50;
    /** @var int Status for completed files. */
    const STATUS_COMPLETED = 60;
    /** @var int Status for failed files. */
    const STATUS_FAILED = 70;

    /** @var int Type for manually uploaded files */
    const TYPE_MANUAL = 0;
    /** @var int Type for automatically uploaded files */
    const TYPE_AUTOMATED = 1;

    /** @var int Actions for insert */
    const ACTION_INSERT = 10;
    /** @var int Actions for update */
    const ACTION_UPDATE = 20;
    /** @var int Actions for delete */
    const ACTION_DELETE = 30;

    /**
     * The old data used for show in after_update and after_delete event
     * @var static
     */
    protected $olddata;

    /**
     * Create an instance of this class.
     *
     * @param int $id If set, this is the id of an existing record, used to load the data.
     * @param stdClass $record If set will be passed to {@link self::from_record()}.
     */
    final public function __construct($id = 0, ?stdClass $record = null) {
        global $CFG;

        if ($id > 0) {
            $this->raw_set('id', $id);
            $this->read();
        }
        if (!empty($record)) {
            $this->from_record($record);
        }
        if ($CFG->debugdeveloper) {
            $this->verify_protected_methods();
        }
    }

    /**
     * Hook to execute before an update.
     *
     * Please note that at this stage the data has already been validated and therefore
     * any new data being set will not be validated before it is sent to the database.
     *
     * @return void
     */
    public function before_update() {
        $olddata = new static($this->get('id'));
        if ($olddata) {
            $this->olddata = $olddata;
        }
    }

    /**
     * Clean a record object before inserting or updating it.
     *
     * Cleans a record object by:
     *  - removing any custom properties not defined in the persistent definition
     *  - setting the id to null
     *  - setting the timecreated and timemodified fields to the current timestamp
     *  - setting the usermodified field to the current user's id
     *  - ensuring all fields are not null, and setting them to their default value if they are
     *  - cleaning each field according to its type
     *
     * @param stdClass $record The record to clean.
     *
     * @return array The cleaned record.
     */
    public static function clean_record(&$record) {
        global $USER;

        $properties = static::properties_definition();

        $record = array_intersect_key((array) $record, $properties);

        // Set custom persistent properties.
        $now = time();
        $record['id'] = null;
        $record['timecreated'] = $now;
        $record['timemodified'] = $now;
        $record['usermodified'] = $USER->id;

        foreach ($properties as $property => $definition) {
            if (!isset($record[$property])) {
                if (isset($definition['default'])) {
                    $record[$property] = $definition['default'];
                } else {
                    $record[$property] = 0;
                }
            }
            if (is_null($record[$property]) && $definition['null'] == NULL_NOT_ALLOWED && isset($definition['default'])) {
                if (isset($definition['default'])) {
                    $record[$property] = $definition['default'];
                } else {
                    $record[$property] = 0;
                }
            }
            $record[$property] = clean_param($record[$property], $definition['type']);
        }

        return $record;
    }

    /**
     * Returns navigation controls (tabtree)
     *
     * @param url $currenturl
     * @param \core\context $context
     * @return null|tabtree
     */
    public static function edit_controls(url $currenturl, $context = null) {

        // Get context for cheking capabilities.
        if ($context == null) {
            $context = \core\context\system::instance();
        }

        // Get current element type.
        $class = static::class;
        $classinfo = new \ReflectionClass($class);
        $type = $classinfo->getShortName();

        $params = $currenturl->params();
        $viewurl = new url("/admin/tool/eabcetlbridge/pages/view" . $type . ".php");
        $addurl = new url("/admin/tool/eabcetlbridge/pages/edit" . $type . ".php");

        // Remove returnurl.
        unset($params['returnurl']);

        $tabs = array();

        $tabs[] = new tabobject('view' . $type . '.php', new url($viewurl, $params), 'Listado');

        $tabs[] = new tabobject('edit' . $type . '.php', new url($addurl,  [
            'categoryid' => $params['categoryid'] ?? 0,
            'returnurl' => $currenturl
        ]), 'Agregar');

        $currenttab = 'view' . $type . '.php';
        $path = $currenturl->get_path();
        $path = explode('/', $path);
        if (!empty($path)) {
            $currenttab = end($path);
        }

        if (count($tabs) > 1) {
            return new tabtree($tabs, $currenttab);
        } else {
            return null;
        }

    }

    /**
     * Get the available status options with their translated names.
     *
     * @return array
     */
    public static function get_status_options() {
        return [
            self::STATUS_DISABLED => get_string('status_disabled', 'tool_eabcetlbridge'),
            self::STATUS_PREVIEW => get_string('status_preview', 'tool_eabcetlbridge'),
            self::STATUS_PENDING => get_string('status_pending', 'tool_eabcetlbridge'),
            self::STATUS_SENTTOQUEUE => get_string('status_senttoqueue', 'tool_eabcetlbridge'),
            self::STATUS_PROCESSING => get_string('status_processing', 'tool_eabcetlbridge'),
            self::STATUS_COMPLETED => get_string('status_completed', 'tool_eabcetlbridge'),
            self::STATUS_FAILED => get_string('status_failed', 'tool_eabcetlbridge'),
        ];
    }

    /**
     * Returns an array of status options for report view.
     * @return array
     */
    public static function get_status_options_for_view() {
        return [
            'default' => [
                'text' => new lang_string('status_pending', 'tool_eabcetlbridge'),
                'color' => 'badge badge-info'
            ],
            'checknull' => [
                'text' => 'NULL?',
                'color' => 'badge badge-warning'
            ],
            'options' => [
                self::STATUS_DISABLED => [
                    'text' => new lang_string('status_disabled', 'tool_eabcetlbridge'),
                    'color' => 'badge badge-secondary'
                ],
                self::STATUS_PREVIEW => [
                    'text' => new lang_string('status_preview', 'tool_eabcetlbridge'),
                    'color' => 'badge badge-secondary'
                ],
                self::STATUS_PENDING => [
                    'text' => new lang_string('status_pending', 'tool_eabcetlbridge'),
                    'color' => 'badge badge-info'
                ],
                self::STATUS_SENTTOQUEUE => [
                    'text' => new lang_string('status_senttoqueue', 'tool_eabcetlbridge'),
                    'color' => 'badge badge-primary'
                ],
                self::STATUS_PROCESSING => [
                    'text' => new lang_string('status_processing', 'tool_eabcetlbridge'),
                    'color' => 'badge badge-dark'
                ],
                self::STATUS_COMPLETED => [
                    'text' => new lang_string('status_completed', 'tool_eabcetlbridge'),
                    'color' => 'badge badge-success'
                ],
                self::STATUS_FAILED => [
                    'text' => new lang_string('status_failed', 'tool_eabcetlbridge'),
                    'color' => 'badge badge-danger'
                ],
            ]
        ];
    }

}
