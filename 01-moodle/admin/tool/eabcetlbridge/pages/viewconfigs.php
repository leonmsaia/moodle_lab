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
 * Crear/Editar Configuración
 *
 * @package   tool_eabcetlbridge
 * @category  pages
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__, 5) . '/config.php');

use core_reportbuilder\system_report_factory;
use tool_eabcetlbridge\reportbuilder\local\systemreports\configs_report as persistentreport;
use tool_eabcetlbridge\persistents\configs as persistent;
use tool_eabcetlbridge\url;

$context = core\context\system::instance();

// Capabilities.
require_admin();

// Variables.
global $PAGE, $OUTPUT;
$pageurl = url::viewconfigs();
$pagetitle = 'Listado de Configuraciones de Estrategias de Migración';

$params = array();

// Url of current page.
$baseurl = new url($pageurl, $params);

// Page settings.
/** @var moodle_page $PAGE */
$PAGE->set_subpage('eabcetlbridge');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout('report');

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

// Edit controls.
if ($editcontrols = persistent::edit_controls($baseurl, $context)) {
    echo $OUTPUT->render($editcontrols);
}

// Create report.
$reportparams = ['contextid' => $context->id];

$report = system_report_factory::create(persistentreport::class, $context, '', '', 0, $reportparams);

echo $report->output();

echo $OUTPUT->footer();
