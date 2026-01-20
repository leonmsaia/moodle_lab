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
 * CLI script to populate users to eabcetlbridge_id_map table.
 *
 * @package     tool_eabcetlbridge
 * @category    cli
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Script options.
list($options, $unrecognized) = cli_get_params(
    ['help' => false],
    ['h' => 'help']
);

if ($unrecognized || $options['help']) {
    $help = <<<EOT
Carga los usuarios en la tabla eabcetlbridge_id_map

Options:
-h, --help            Imprime esta ayuda

Example:
\$ sudo -u www-data php admin/tool/eabcetlbridge/cli/populate_users_to_id_map.php
EOT;
    echo $help;
    die;
}

global $DB;

// User Mapping.
cli_writeln('Consultando usuarios que no esten en la tabla eabcetlbridge_id_map...');
$table = 'user';
$sourcetype = 'user';
$sourcekey = 'username';
$targetid = 'id';

// Use a recordset to avoid loading all users into memory. This is critical for performance.
$sql = "SELECT t.{$targetid}, t.{$sourcekey}
          FROM {{$table}} t
     LEFT JOIN {eabcetlbridge_id_map} m
               ON m.source_key = t.{$sourcekey}
               AND m.source_type = '$sourcetype'
         WHERE m.id IS NULL AND t.deleted = 0";

$time = time();
$count = 0;
$records = [];
$rs = $DB->get_recordset_sql($sql);
foreach ($rs as $row) {
    $mapping = new stdClass();
    $mapping->source_type = $sourcetype;
    $mapping->source_key = $row->{$sourcekey};
    $mapping->target_id = $row->{$targetid};
    $mapping->timecreated = $time;
    $mapping->timemodified = $time;

    $records[] = $mapping;
    $count++;

    if ($count % 1000 == 0) {
        $DB->insert_records('eabcetlbridge_id_map', $records);
        cli_writeln("... {$count} registros procesados.");
        $records = [];
    }

}
$rs->close(); // Always close the recordset.

if (!empty($records)) {
    $DB->insert_records('eabcetlbridge_id_map', $records);
    cli_writeln("... {$count} registros procesados.");
}

cli_writeln("Total de registros creados: {$count}");
