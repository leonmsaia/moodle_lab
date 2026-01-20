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

$cursos_file = "./cursos.csv";
$cursos_content = file_get_contents($cursos_file);

$cursos_guids = explode("\n", $cursos_content);

foreach ($cursos_guids as $cursos_guid) {
    $cursos_guid = trim($cursos_guid);
    if(!empty($cursos_guid)) {
        $curso = external_api::call_external_function("local_pubsub_upsert_course", ["ID" => $cursos_guid, 'codigosuseso' => true]);
        var_dump($curso);
    }
}
