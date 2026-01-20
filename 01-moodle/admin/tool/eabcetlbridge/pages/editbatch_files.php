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
 * Upload Migration File
 *
 * @package   tool_eabcetlbridge
 * @category  pages
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__, 5) . '/config.php');

global $CFG;

require_once($CFG->libdir . '/filelib.php');

use core\notification;
use tool_eabcetlbridge\forms\batch_files_form as persistentform;
use tool_eabcetlbridge\persistents\batch_files as persistent;
use tool_eabcetlbridge\url;

$context = core\context\system::instance();

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

// Capabilities.
require_admin();

// Variables.
global $PAGE, $OUTPUT, $SITE;
$pageurl = url::editbatch_files();
$pagetitle = ($id) ? 'Editar Archivo de Migración' : 'Subir Archivo de Migración';

// URL settings.
$params = array();
if ($returnurl) {
    $returnurl = new url($returnurl);
    $params['returnurl'] = $returnurl;
} else {
    $returnurl = url::viewbatch_files();
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

                $message = markdown_to_html("¿Desea realmente borrar el archivo de migración $id?");

                $yesurl->param('action', 'delete');

                echo $OUTPUT->header();
                echo $OUTPUT->confirm($message, $yesurl, $returnurl, [
                    'confirmtitle' => 'Borrar Configuración',
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

$fileid = ($id) ?: null;
$persistent = false;
$data = new stdClass();
if ($id) {
    $persistent = new persistent($id);
    $data = $persistent->to_record();
}

$confirmdata = [
    'message' => "Editando la archivo de migración: $id",
    'confirmtitle' => "Editar Archivo de Migración: $id",
    'submitstr' => "Guardar",
];

// Get an unused draft for attachments.
$draftitemid = file_get_submitted_draft_itemid('file');
file_prepare_draft_area(
    $draftitemid,
    $context->id,
    persistent::COMPONENT,
    persistent::FILEAREA,
    $fileid,
    [
        'subdirs' => 0,
        'maxbytes' => $SITE->maxbytes,
        'maxfiles' => 1,
    ]
);

$customdata = [
    'persistent' => $persistent,
    'userid' => $USER->id,
    'data' => $data,
    'confirmdata' => $confirmdata
];

$form = new persistentform($baseurl, $customdata, 'post', '', ['id' => 'eabcetlbridge_configs_form']);

$form->set_data((object) array('file' => $draftitemid));

// Manage form.
if ($form->is_cancelled()) {

    redirect($returnurl);

} else if ($data = $form->get_data()) {

    $idnumber = $data->id ?? null;

    try {

        $persistent = new persistent($idnumber, $data);
        if ($idnumber) {
            $persistent->update();
        } else {
            $persistent->create();
        }

        // Now save the files in correct part of the File API.
        file_save_draft_area_files(
            $data->file,
            $context->id,
            $persistent->get('component'),
            $persistent->get('filearea'),
            $persistent->get('id'),
            [
                'subdirs' => 0,
                'maxbytes' => $SITE->maxbytes,
                'maxfiles' => 1,
            ]
        );

        notification::success(get_string('changessaved'));

    } catch (\Exception $e) {

        notification::error($e->getMessage());

    }

    // We are done, so let's redirect somewhere.
    redirect($returnurl);
}

$editcontrols = persistent::edit_controls($baseurl, $context);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
if ($editcontrols && !$id) {
    echo $OUTPUT->render($editcontrols);
}
$form->display();
echo $OUTPUT->footer();
