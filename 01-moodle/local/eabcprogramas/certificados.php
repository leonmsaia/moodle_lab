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
 *
 * @package    local_eabcprogramas
 * @copyright  2020 e-abclearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require "$CFG->libdir/tablelib.php";
require "certificados_table.php";

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/eabcprogramas/certificados.php'));
require_login();
if (is_siteadmin()) {

    $download = optional_param('download', '', PARAM_ALPHA);
    $table = new certificados_table('certificados');
    $table->show_download_buttons_at([]);
    $table->is_downloading($download, 'certificados', 'certificados');

    if (!$table->is_downloading()) {
        $PAGE->set_title(get_string('certificados', 'local_eabcprogramas'));
        $PAGE->set_heading(get_string('certificados_modelo', 'local_eabcprogramas'));
        echo $OUTPUT->header();
    }
    $fields = '*';
    $from = '{local_certificados}';
    $where = '1=1 ORDER BY fromdate DESC';
    $table->set_sql($fields, $from, $where);
    $table->define_baseurl("$CFG->wwwroot/local/eabcprogramas/certificados.php");
    $url_crear = new moodle_url('/local/eabcprogramas/certificado.php', ['action' => 'crear']);
    $url_regresar = new moodle_url('/local/eabcprogramas/manage.php');

    if (!$table->is_downloading()) {
        $crear =  ' <a href="' . $url_crear . '" class="btn btn-primary active">Crear Nuevo Modelo</a>';
        $regrasar = ' <a href="' . $url_regresar . '" class="btn btn-primary">Regresar</a><br>';
        echo $crear;
        echo $regrasar;
    }
    $table->out(2, true);

    if (!$table->is_downloading()) {
        echo $OUTPUT->footer();
    }
}
