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

use local_eabcprogramas\utils;

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require "$CFG->libdir/tablelib.php";
require "programas_usuarios_table.php";
require "form_filters.php";

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/eabcprogramas/programs/list_programs.php'));
require_login();
$download = optional_param('download', '', PARAM_ALPHA);
global $USER;


$table = new programas_usuarios_table('programs-otorgados');
//$table->show_download_buttons_at([]);

$table->is_downloading($download, 'Programas-Otorgados', 'Programas-Otorgados');

if (!$table->is_downloading()) {
    $PAGE->set_title('Programas Otorgados');
    $PAGE->set_heading(get_string('programas_otorgados', 'local_eabcprogramas'));
    echo $OUTPUT->header();
}

$columns = [];
$headers = [];
if (!$table->is_downloading()) {
    $columns[] = 'action';
    $headers[] = 'Descargar';
}
if (is_siteadmin() || has_capability('local/eabcprogramas:holding', context_system::instance())) {
    $columns = array_merge($columns,['codigo_programa', 'description', 'usuario', 'rut_usuario', 'empresa', 'rut_empresa', 'end_firstcourse', 'fecha_otorgamiento', 'fecha_vencimiento', 'status', 'codigo', 'codigo_diploma', 'codigo_certificado']);
    $headers = array_merge($headers,['Código de Programa', 'Descripción', 'Trabajador', 'RUT Trabajador', 'Empresa', 'RUT Empresa', 'Finalización Primer Curso', 'Fecha Otorgamiento', 'Fecha de Vencimiento', 'Vigente', 'Código de Programa Otorgado', 'Código de Diploma', 'Código de Certificado']);
} else {
    $columns = array_merge($columns,array('codigo_programa', 'description', 'empresa', 'rut_empresa', 'end_firstcourse', 'fecha_otorgamiento', 'fecha_vencimiento', 'status', 'codigo', 'codigo_diploma', 'codigo_certificado'));
    $headers = array_merge($headers,array('Código de Programa', 'Descripción', 'Empresa', 'RUT Empresa', 'Finalización Primer Curso', 'Fecha Otorgamiento', 'Fecha de Vencimiento', 'Vigente', 'Código de Programa Otorgado', 'Código de Diploma', 'Código de Certificado'));
}
$table->define_columns($columns);
$table->define_headers($headers);

$mform = new form_filters();
if (!$table->is_downloading()) {
    $url_regresar = new moodle_url('/local/eabcprogramas/manage.php');
    $regresar = ' <a href="' . $url_regresar . '" class="btn btn-primary">Regresar</a><br>';
    echo $regresar;
    echo '<br>';
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
if (isset($fromform->ruttrabajador)) {
    $ruttrabajador = $fromform->ruttrabajador;
    if ($ruttrabajador) {
        $and .= " AND rut_usuario like '%" . $ruttrabajador . "%'";
    }
}
if (isset($fromform->trabajador)) {
    $trabajador = $fromform->trabajador;
    if ($trabajador) {
        $and .= " AND usuario like '%" . $trabajador . "%'";
    }
}
if (isset($fromform->rutempresa)) {
    $rutempresa = $fromform->rutempresa;
    if ($rutempresa) {
        $and .= " AND rut_empresa like '%" . $rutempresa . "%'";
    }
}

if (isset($fromform->empresa)) {
    $empresa = $fromform->empresa;
    if ($empresa) {
        $and .= " AND empresa like '%" . $empresa . "%'";
    }
}

if (isset($fromform->startdate_otorgamiento) && isset($fromform->todate_otorgamiento)) {
    $startdate_otorgamiento = $fromform->startdate_otorgamiento;
    $todate_otorgamiento = $fromform->todate_otorgamiento;
    if ($startdate_otorgamiento && $todate_otorgamiento) {
        $and .= " AND fecha_otorgamiento BETWEEN " . $startdate_otorgamiento . " AND " . $todate_otorgamiento;
    }
}

if (isset($fromform->startdate_endfirstcourse) && isset($fromform->todate_endfirstcourse)) {
    $startdate_endfirstcourse = $fromform->startdate_endfirstcourse;
    $todate_endfirstcourse = $fromform->todate_endfirstcourse;
    if ($startdate_endfirstcourse && $todate_endfirstcourse) {
        $and .= " AND end_firstcourse BETWEEN " . $startdate_endfirstcourse . " AND " . $todate_endfirstcourse;
    }
}

if (isset($fromform->startdate_vencimiento) && isset($fromform->todate_vencimiento)) {
    $startdate_vencimiento = $fromform->startdate_vencimiento;
    $todate_vencimiento = $fromform->todate_vencimiento;
    if ($startdate_vencimiento && $todate_vencimiento) {
        $and .= " AND fecha_vencimiento BETWEEN " . $startdate_vencimiento . " AND " . $todate_vencimiento;
    }
}

if (is_siteadmin()) {
    $and .= "";
} else if (has_capability('local/eabcprogramas:holding', context_system::instance())) {
    //$empresa = utils::get_data_user_field_text('empresarazonsocial', $USER->id);
    $holdings = utils::get_holdings_user($USER->id);
    if ($holdings) {
        $empresas = utils::get_companies($USER->id, $holdings);
        $and .= " AND empresa IN $empresas";
    }
} else if (has_capability('local/eabcprogramas:trabajador', context_system::instance())) {
    $and .= ' AND userid = ' . $USER->id;
}

$fields = '*';
$from = '{local_eabcprogramas_usuarios}';
$where = '1=1';
$orderby = ''; //' ORDER BY fecha_otorgamiento ASC';
$where .= $and . $orderby;
$table->set_sql($fields, $from, $where);
$table->define_baseurl("$CFG->wwwroot/local/eabcprogramas/programs/list_programs.php");

$table->out(10, true);
if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}
