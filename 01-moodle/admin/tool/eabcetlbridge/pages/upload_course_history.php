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

require_once(dirname(__FILE__, 5) . '/config.php');

global $CFG, $PAGE, $OUTPUT, $SITE;

require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/csvlib.class.php');

use core\notification;
use tool_eabcetlbridge\forms\course_history_upload_form;
use tool_eabcetlbridge\forms\course_history_mapping_form;
use tool_eabcetlbridge\strategies\course_history_strategy;
use tool_eabcetlbridge\strategies\course_history\load_data;
use tool_eabcetlbridge\url;

$context = core\context\system::instance();

// Capabilities.
require_admin();

$iid = optional_param('iid', 0, PARAM_INT);
$pageurl = new moodle_url('/admin/tool/eabcetlbridge/pages/upload_course_history.php');

$PAGE->set_subpage('eabcetlbridge');
$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_title('Subir Historial de Cursos');
$PAGE->set_pagelayout('report');

$renderer = $PAGE->get_renderer('tool_eabcetlbridge');

echo $OUTPUT->header();
echo $OUTPUT->heading('Subir Historial de Cursos');

// Step 1: Upload Form
if (!$iid) {
    $mform = new course_history_upload_form($pageurl);

    if ($formdata = $mform->get_data()) {
        // Form submitted, process upload
        $text = $mform->get_file_content('userfile');
        $csvimport = new load_data();
        $csvimport->load_csv_content($text, $formdata->encoding, $formdata->delimiter, $formdata->previewrows);
        $csvimporterror = $csvimport->get_error();

        if (!empty($csvimporterror)) {
            echo $renderer->errors(array($csvimport->get_error()));
            echo $OUTPUT->continue_button($pageurl);
            echo $OUTPUT->footer();
            die();
        }
        $iid = $csvimport->get_iid();

        // Redirect to self with iid to avoid resubmission issues and proceed to step 2.
        redirect(new moodle_url($pageurl, ['iid' => $iid]));
    } else {
        $mform->display();
    }
} else {
    // Step 2: Mapping Form
    // Use the same type string as in load_data.php ('coursehistoryutils')
    $csvimport = new csv_import_reader($iid, 'coursehistoryutils');
    $header = $csvimport->get_columns();

    if (empty($header)) {
        notification::error('Error leyendo encabezados del archivo CSV. Es posible que el archivo haya expirado o sea inválido.');
        $csvimport->cleanup();
        echo $OUTPUT->continue_button($pageurl); // Back to start
    } else {
        // Get preview data for display
        $csvimport->init();
        $preview = [];
        $count = 0;
        // Show first 10 rows for context
        while ($count < 10 && ($row = $csvimport->next())) {
            $preview[] = $row;
            $count++;
        }

        // Show the preview table
        echo $renderer->import_preview_page($header, $preview);

        $customdata = [
            'header' => $header,
            'iid' => $iid
        ];

        $mform2 = new course_history_mapping_form($pageurl, $customdata);

        if ($data = $mform2->get_data()) {
            // Step 3: Process Logic
            try {
                $strategy = new course_history_strategy();

                $mapping = [
                    'map_username' => $data->map_username,
                    'map_course' => $data->map_course,
                    'map_grade' => $data->map_grade,
                    'map_startdate' => $data->map_startdate,
                    'map_enddate' => $data->map_enddate,
                ];

                $result = $strategy->process_import($csvimport, $mapping);

                notification::success("Procesamiento completado.");
                echo $OUTPUT->box_start();
                if (isset($result['total_rows_scanned'])) {
                    echo "<p>Filas escaneadas: {$result['total_rows_scanned']}</p>";
                }
                if (isset($result['unique_records_found'])) {
                    echo "<p>Registros únicos encontrados: {$result['unique_records_found']}</p>";
                }
                echo $OUTPUT->box_end();

                // Cleanup CSV import
                // Cleanup CSV import
                $csvimport->cleanup();

                // Process database updates
                echo $OUTPUT->notification('Iniciando actualización de base de datos...', 'info');

                // Breaking the list into chunks could be good for 5000+ records but for now processing all at once
                // as PHP time limit usually handles execution time, not memory if array is 5000 items (not huge).
                $options = [
                    'override_grade' => $data->override_grade
                ];
                $stats = $strategy->save_changes($result['data'], $options);

                echo $OUTPUT->box_start();
                echo $OUTPUT->heading('Resumen de cambios', 4);
                echo "<ul>";
                echo "<li>Registros procesados: <strong>{$stats['processed']}</strong></li>";
                echo "<li>Matriculaciones actualizadas (Fecha creación): <strong>{$stats['enrollments_updated']}</strong></li>";
                echo "<li>Completados creados: <strong>{$stats['completions_created']}</strong></li>";
                echo "<li>Completados actualizados: <strong>{$stats['completions_updated']}</strong></li>";
                echo "<li>Calificaciones actualizadas (Override): <strong>{$stats['grades_updated']}</strong></li>";
                echo "<li>Usuarios no encontrados: <strong>{$stats['users_not_found']}</strong></li>";
                echo "<li>Cursos no encontrados: <strong>{$stats['courses_not_found']}</strong></li>";
                echo "<li>Errores: <strong>{$stats['errors']}</strong></li>";
                echo "</ul>";
                echo $OUTPUT->box_end();

                echo $OUTPUT->continue_button(new moodle_url('/admin/tool/eabcetlbridge/pages/upload_course_history.php')); // Reset or go somewhere else
            } catch (\Exception $e) {
                notification::error('Error importando: ' . $e->getMessage());
                // In case of error, we can display the form again or a back button
                echo $OUTPUT->continue_button(new moodle_url($pageurl, ['iid' => $iid]));
            }
        } else {
            $mform2->display();
        }
    }
}

echo $OUTPUT->footer();
