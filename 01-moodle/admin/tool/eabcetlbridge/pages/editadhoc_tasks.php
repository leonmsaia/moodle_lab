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
 * Edit planner
 *
 * @package   tool_eabcetlbridge
 * @category  pages
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__, 5) . '/config.php');

use core\notification;
use tool_eabcetlbridge\forms\planner_form as persistentform;
use tool_eabcetlbridge\helpers\adhoc as persistent;
use tool_eabcetlbridge\url;

$context = core\context\system::instance();

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

// Capabilities.
require_admin();

// Variables.
global $PAGE, $OUTPUT, $SITE;
$pageurl = url::editadhoc_tasks();
$pagetitle = ($id) ? 'Editar Ad-hoc' : 'Crear Ad-hoc';

// URL settings.
$params = array();
if ($returnurl) {
    $returnurl = new url($returnurl);
    $params['returnurl'] = $returnurl;
} else {
    $returnurl = url::viewadhoc_tasks();
}

if ($id) {
    $params['id'] = $id;
}

// Url of current page.
$baseurl = new url($pageurl, $params);

// Page settings.
/** @var moodle_page $PAGE */
$PAGE->set_subpage('eabcetlbridge');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout('report');

// Url for confirm delete or edit.
$yesurl = new url($baseurl, [
    'sesskey' => sesskey(),
    'returnurl' => $returnurl->out_as_local_url(false),
    'id' => $id,
    'confirm' => 1,
]);

// Manage actions (delete, edit) callbacks.
$actions = ['delete'];
if (in_array($action, $actions) && confirm_sesskey()) {

    try {
        switch ($action) {
            case 'delete':

                $persistent = new persistent($id);

                if ($confirm) {
                    $persistent->delete();
                    notification::success(get_string('deleted'));
                    redirect($returnurl);
                }

                $message = markdown_to_html("¿Desea realmente borrar la tarea ad-hoc $id?");

                $yesurl->param('action', 'delete');

                echo $OUTPUT->header();
                echo $OUTPUT->confirm($message, $yesurl, $returnurl, [
                    'confirmtitle' => "Borrar {$persistent->get_name()}",
                    'continuestr' => 'Si, Borrar',
                ]);
                echo $OUTPUT->footer();
                die();

                break;
        }
    } catch (\Exception $e) {

        notification::error($e->getMessage());

    }

    // We are done, so let's redirect somewhere.
    redirect($returnurl);
}

notification::warning('Acción no válida');
redirect($returnurl);
