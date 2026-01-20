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


define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
global $CFG;


require_once($CFG->libdir."/externallib.php");

$facilitadores_file = "./facilitadores.csv";
$facilitadores_content = file_get_contents($facilitadores_file);

$facilitadores_guids = explode("\n", $facilitadores_content);

foreach ($facilitadores_guids as $facilitador_guid) {
    $facilitador_guid = trim($facilitador_guid);
    if(!empty($facilitador_guid)) {
        $facilitador = external_api::call_external_function("local_pubsub_create_facilitador", ["ID" => $facilitador_guid]);
        var_dump($facilitador);
    }
}
