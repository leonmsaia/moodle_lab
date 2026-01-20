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
 * Upload Membes
 *
 * @package   tool_eabcetlbridge
 * @category  pages
 * @copyright 2025 e-ABC Learning <info@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__, 5) . '/config.php');

global $CFG;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');

use tool_eabcetlbridge\forms\upload_form;
use tool_eabcetlbridge\upload_utils;

$context = core\context\system::instance();

$csvid = optional_param('id', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

// Capabilities.
require_admin();

// Variables.
global $PAGE, $OUTPUT;
$pageurl = new core\url('/admin/tool/eabcetlbridge/pages/upload_files_for_migrate.php');
$pagetitle = 'Carga de Archivos para Migración';

// URL settings.
$params = array();
if ($returnurl) {
    $returnurl = new core\url($returnurl);
    $params['returnurl'] = $returnurl;
} else {
    $returnurl = $pageurl;
}

// Url of current page.
$baseurl = new core\url($pageurl, $params);

// Page settings.
/** @var moodle_page $PAGE */
$PAGE->set_subpage('eabcetlbridge');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout('report');

// Import and validate CSV file with csv_import_reader.
$cir = null;
if (empty($csvid)) {
    $form1 = new upload_form($baseurl, null, 'post', '', null, true, null, '', get_string('upload'));

    if ($data = $form1->is_cancelled()) {

        redirect($returnurl);

    } else if ($data = $form1->get_data()) {

        $csvid = csv_import_reader::get_new_iid('eabcetlbridge_migration_file');
        $cir = new csv_import_reader($csvid, 'eabcetlbridge_migration_file');

        $content = $form1->get_file_content('file');

        $readcount = $cir->load_csv_content($content, $data->encoding, $data->delimiter_name);
        $csvloaderror = $cir->get_error();
        unset($content);

        if (!is_null($csvloaderror)) {
            throw new core\exception\moodle_exception('csvloaderror', '', $returnurl, $csvloaderror);
        }

        $previewrows = $data->previewrows;

        // Continue to form2.

    } else {
        echo $OUTPUT->header();

        echo $OUTPUT->heading($pagetitle);

        /*if ($editcontrols) {
            echo $OUTPUT->render($editcontrols);
        }*/

        $form1->display();
        echo $OUTPUT->footer();
        die;
    }
} else {
    $cir = new csv_import_reader($csvid, 'uploadmember');
}
// Add CSVID to URL.
$baseurl->param('id', $csvid);

// Test if columns ok.
$stdfields = \tool_eabcetlbridge\strategies\base_strategy::get_standard_fields_for_upload();
$acceptedfields = \tool_eabcetlbridge\strategies\base_strategy::get_accepted_fields_for_upload();
$numberobligatorycolumns = \tool_eabcetlbridge\strategies\base_strategy::get_number_obligatory_columns_for_upload();
$stdfields = upload_utils::validate_uploaded_columns(
    $cir,
    $returnurl,
    $stdfields,
    $acceptedfields,
    $numberobligatorycolumns
);

// Create table for list preview of members.
$table = new list_upload_members(
    'uploadmembers' . $csvid,
    $baseurl,
    $cir,
    $stdfields,
    $acceptedfields,
    $previewrows
);

$data = new stdClass();
$data->id = $csvid;
$data->config = '';
if ($organizationid) {
    $data->organizationid = $organizationid;
}

$form2 = new upload_members_form($baseurl, [
    'data' => $data,
    'table' => $table
]);

// If a file has been uploaded, then process it.
if ($data = $form2->is_cancelled()) {

    $cir->cleanup(true);
    redirect($returnurl);

} else if ($data = $form2->get_data()) {

    echo $OUTPUT->header();

    echo $OUTPUT->heading(get_string('uploadmember_result', 'local_rayp_organization'));

    // Process the file.
    $table = $form2->get_table();
    $table->setup_for_process($data);
    $table->render_table();

    // Get results.
    $results = $table->get_results();
    foreach ($results as $text => $result) {
        if ($result > 0) {
            echo $OUTPUT->notification(get_string($text, 'local_rayp_organization', $result), 'notifysuccess');
        }
    }

    if ($table->get_bulk() != RAYP_BULK_NONE && !$table->is_empty_bulk()) {
        echo $OUTPUT->continue_button($bulknurl);
    } else {
        echo $OUTPUT->continue_button($returnurl);
    }

    echo $OUTPUT->footer();
    die;
}

/** @var core_renderer $OUTPUT */
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

// Render preview table data.
//$table->render_table();

//if ($table->get_no_error()) {
//    $form2->display();
//}

echo $OUTPUT->footer();
