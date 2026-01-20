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
 * Plugin administration pages are defined here.
 *
 * @package     holdingmng
 * @category    admin
 * @copyright   2020 e-ABC Learning <contacto@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once ('../../config.php');
global $PAGE, $OUTPUT, $CFG, $USER;

require_login();
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('title','local_holdingmng'));
$PAGE->set_url(new moodle_url($CFG->wwwroot .'/local/holdingmng/index.php'));

redirect(new moodle_url('/local/holdingmng/holdings.php'));

$renderer = $PAGE->get_renderer('local_holdingmng');
$html = "";

// Header
$header = [];
$header['title'] = get_string('title', 'local_holdingmng');
$header['description'] = get_string('description', 'local_holdingmng');

// Alert
$alert = ['message' => get_string('nopermissions', 'local_holdingmng')];

// Menu
$data = [];

// Options en menú
// Holdins
$link = $CFG->wwwroot .'/local/holdingmng/holdings.php';
$strlink = get_string('holdings', 'local_holdingmng');
$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];

// Empresas en holding
$link = $CFG->wwwroot .'/local/holdingmng/companies.php';
$strlink = get_string('companies', 'local_holdingmng');
$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];

// Usuarios en holding
$link = $CFG->wwwroot .'/local/holdingmng/users.php';
$strlink = get_string('users', 'local_holdingmng');
$data['linkaccess'][] = ['link'=>$link, 'strlink'=>$strlink];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('title','local_holdingmng'));

if(is_siteadmin() or has_capability('local/holdingmng:view', context_system::instance())){
    $html .= $renderer->render_menu($data);

}else{
    $html = $renderer->render_alert($alert);
}
echo $html;
echo $OUTPUT->footer();