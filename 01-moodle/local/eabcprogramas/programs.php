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
require "programs_filter.php";
require "programs_table.php";

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/eabcprogramas/programs.php'));
require_login();

if (is_siteadmin()) {

    $download = optional_param('download', '', PARAM_ALPHA);
    $table = new programs_table('programs');
    //$table->show_download_buttons_at([]);
    $table->is_downloading($download, 'Programas', 'Programas');

    $columns = array('codigo', 'description', 'version', 'caducidad', 'horas', 'responsable', 'fromdate', 'todate', 'status', 'otorgados');
    $headers = array('Código', 'Descripción', 'Versión', 'Duración (meses)', 'Carga (horas)', 'Área Responsable', 'Fecha de Lanzamiento', 'Fecha de Finalización', 'Condición', 'Programas Otorgados');
    
    if (!$table->is_downloading()) {
        $columns[] = 'action';
        $headers[] = 'Acción';
    }
    if ($table->is_downloading()) {
        $columns[] = 'objetivos';
        $headers[] = 'Objetivos';
    }

    $table->define_columns($columns);
    $table->define_headers($headers);

    $mform = new programs_filter();

    if (!$table->is_downloading()) {
        $PAGE->set_title('Programas');
        $PAGE->set_heading(get_string('programs', 'local_eabcprogramas'));
        echo $OUTPUT->header();
    }

    if (!$table->is_downloading()) {
        $url_crear = new moodle_url('/local/eabcprogramas/programa.php', ['action' => 'crear']);
        $url_regresar = new moodle_url('/local/eabcprogramas/manage.php');
        $crear = ' <a href="' . $url_crear . '" class="btn btn-primary active">' . get_string('create', 'local_eabcprogramas') . ' ' . get_string('new_program', 'local_eabcprogramas') . '</a>';
        $regresar = ' <a href="' . $url_regresar . '" class="btn btn-primary">Regresar</a><br>';
        echo $crear;
        echo $regresar;
    }

    $fromform = $mform->get_data();
    if (!$table->is_downloading()) {
        $mform->display();
    }
    $and = '';

    if (isset($fromform->solouno)) {
        $status = $fromform->status;
        $and .= " AND status = " . $status;
    }

    if (isset($fromform->codigo)) {
        $codigo = $fromform->codigo;
        if ($codigo) {
            $and .= " AND codigo like '%" . $codigo . "%'";
        }
    }
    if (isset($fromform->description)) {
        $description = $fromform->description;
        if ($description) {
            $and .= " AND description like '%" . $description . "%'";
        }
    }
    if (isset($fromform->caducidad)) {
        $caducidad = $fromform->caducidad;
        if ($caducidad) {
            $and .= " AND caducidad = " . $caducidad;
        }
    }
    if (isset($fromform->horas)) {
        $horas = $fromform->horas;
        if ($horas) {
            $and .= " AND horas = " . $horas;
        }
    }
    if (isset($fromform->responsable)) {
        $responsable = $fromform->responsable;
        if ($responsable) {
            $and .= " AND responsable like '%" . $responsable . "%'";
        }
    }

    if (isset($fromform->startdate_lanzamiento) && isset($fromform->todate_lanzamiento)) {
        $startdate_lanzamiento = $fromform->startdate_lanzamiento;
        $todate_lanzamiento = $fromform->todate_lanzamiento;
        if ($startdate_lanzamiento && $todate_lanzamiento) {
            $and .= " AND fromdate BETWEEN " . $startdate_lanzamiento . " AND " . $todate_lanzamiento;
        }
    }

    if (isset($fromform->startdate_vencimiento) && isset($fromform->todate_vencimiento)) {
        $startdate_vencimiento = $fromform->startdate_vencimiento;
        $todate_vencimiento = $fromform->todate_vencimiento;
        if ($startdate_vencimiento && $todate_vencimiento) {
            $and .= " AND todate BETWEEN " . $startdate_vencimiento . " AND " . $todate_vencimiento;
        }
    }


    $fields = '*';
    $from = '{local_eabcprogramas}';
    $where = '1=1';
    $where .= $and;
    $table->set_sql($fields, $from, $where);
    $table->define_baseurl("$CFG->wwwroot/local/eabcprogramas/programs.php");

    $table->out(10, true);
    if (!$table->is_downloading()) {
        $url_crear = new moodle_url('/local/eabcprogramas/programa.php', ['action' => 'crear']);
        $url_regresar = new moodle_url('/local/eabcprogramas/manage.php');
        $crear = ' <a href="' . $url_crear . '" class="btn btn-primary active">' . get_string('create', 'local_eabcprogramas') . ' ' . get_string('new_program', 'local_eabcprogramas') . '</a>';
        $regresar = ' <a href="' . $url_regresar . '" class="btn btn-primary">Regresar</a><br>';
        echo $crear;
        echo $regresar;
    }
    if (!$table->is_downloading()) {
        echo $OUTPUT->footer();
    }
}
