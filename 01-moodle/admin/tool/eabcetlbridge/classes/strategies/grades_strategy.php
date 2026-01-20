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

namespace tool_eabcetlbridge\strategies;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/grade/import/lib.php');

use csv_import_reader;
use core\exception\moodle_exception;
use tool_eabcetlbridge\strategies\base_strategy;
use tool_eabcetlbridge\helpers\gradeimport_csv_load_data;

/**
 * Concrete strategy for migrating Moodle Grades data.
 *
 * @package   tool_eabcetlbridge
 * @category  strategies
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class grades_strategy extends base_strategy {

    /**
     * {@inheritdoc}
     */
    public static function get_name() {
        return 'Migración de Calificaciones (Simple y Dinámica)';
    }

    /**
     * Return an example query for extracting Grades data from the Moodle 3.5 database.
     *
     * This query is intended to be used as a reference for creating a query that fetches
     * the required data.
     *
     * The query is not intended to be executed directly, but rather to be used as a template
     * for creating a query that suits your specific needs.
     *
     * @return string The example query for extracting Grades data.
     */
    public static function get_example_query_for_extraction() {
        /* Consulta de ejemplo para extraer calificaciones.
        Asegúrese de que los alias de las columnas de calificación (ej. 'Tarea 1', 'Examen Final')
        coincidan exactamente con el 'itemname' de los ítems de calificación en el curso de Moodle.
        La columna 'course' se mapeará a la calificación final del curso.
        */
        $sql = "";
        return $sql;
    }

    /**
     * Returns the expected headers for the CSV file.
     *
     * @return array
     */
    public static function get_expected_csv_headers(): array {
        // Only fixed headers are defined here. The third column is the grade item.
        return ['username'];
    }

    /**
     * Validates the headers of the CSV file.
     *
     * It checks for exactly 3 columns, and that the first two are 'username' and 'course_shortname'.
     *
     * @param array $headers The headers from the CSV file.
     * @throws moodle_exception If validation fails.
     */
    public function validate_headers(array $headers) {
        // MODIFICADO: La validación es más flexible ahora.
        $requiredheaders = self::get_expected_csv_headers();
        $mincolcount = 2;

        if (count($headers) < $mincolcount) {
            throw new moodle_exception(
                'invalidcolumngen',
                'tool_eabcetlbridge',
                '',
                null,
                "Expected at least {$mincolcount} columns, found " . count($headers)
            );
        }

        // Check if the required headers are present in the uploaded file.
        if (count(array_intersect($requiredheaders, $headers)) != count($requiredheaders)) {
            $expected = implode(', ', $requiredheaders);
            $found = implode(', ', $headers);
            throw new moodle_exception(
                'invalidcolumn',
                'tool_eabcetlbridge',
                '',
                null,
                "Required columns missing. Expected at least: {$expected} | Found: {$found}"
            );
        }
    }

    /**
     * Builds a mapping between the CSV headers and the grade items.
     * The mapping is stored in an array, with keys 'mapping_0', 'mapping_1', etc.
     * The values are either the ID of the grade item or 'new' if the item does not exist.
     *
     * This function follows the original logic of the plugin, which was to create a new item if it did not exist.
     * The new dynamic mapping strategy is implemented in build_mapping_dynamic.
     *
     * @param array $requiredheaders The required headers from the CSV file.
     * @param array $coursegradeheaders The headers that are grade items related to the course.
     * @param array $headers The headers from the CSV file.
     * @param int $courseid The ID of the course.
     * @return array The mapping of CSV headers to grade item IDs.
     */
    public function build_mapping_new($requiredheaders, $coursegradeheaders, $headers, $courseid) {
        global $DB;
        $mapping = [];

        // Lógica original para mantener la compatibilidad hacia atrás.
        mtrace("Estrategia de mapeo fija activada (comportamiento original).");
        $gradeitemheader = array_diff($headers, $requiredheaders);
        $gradeitemheader = reset($gradeitemheader); // Obtener el nombre del ítem de calificación.

        foreach ($headers as $index => $header) {
            if (in_array($header, $requiredheaders)) {
                $mapping['mapping_' . $index] = 0;
            } else {
                $item = false;
                if (in_array($header, $coursegradeheaders)) {
                    $item = $DB->get_record('grade_items', ['courseid' => $courseid, 'itemtype' => 'course']);
                } else if ($header === $gradeitemheader) {
                    $items = $DB->get_records('grade_items', ['courseid' => $courseid, 'itemname' => $header]);
                    $item = reset($items);
                }

                if ($item) {
                    $mapping['mapping_' . $index] = $item->id;
                } else {
                    // El comportamiento original era crear un nuevo ítem si no existía.
                    $mapping['mapping_' . $index] = 'new';
                }
            }
        }

    }

    /**
     * Builds a mapping between the CSV headers and the grade items using a dynamic strategy.
     * This strategy first fetches all relevant grade items in one query, and then processes
     * the headers using an in-memory map.
     *
     * If a header does not match any grade item, it is ignored and a debug message is logged.
     * If a header matches a grade item, the grade item ID is stored in the mapping.
     * If a header matches a course grade item, the course grade item ID is stored in the mapping.
     *
     * This strategy is more efficient than the original strategy, as it only requires one query
     * to fetch all relevant grade items.
     *
     * @param array $requiredheaders The required headers from the CSV file.
     * @param array $coursegradeheaders The headers that are grade items related to the course.
     * @param array $headers The headers from the CSV file.
     * @param int $courseid The ID of the course.
     * @return array The mapping of CSV headers to grade item IDs.
     */
    public function build_mapping_dynamic($requiredheaders, $coursegradeheaders, $headers, $courseid) {
        global $DB;
        $mapping = [];

        mtrace("Estrategia de mapeo dinámico ('all') activada.");

        // 1. Get all potential item names from headers to query them all at once.
        $itemnames = [];
        foreach ($headers as $header) {
            if (!in_array($header, $requiredheaders) && !in_array($header, $coursegradeheaders)) {
                $itemnames[] = $header;
                // Also add the version without " (Real)" if it exists.
                if (str_ends_with($header, ' (Real)')) {
                    $itemnames[] = substr($header, 0, -7);
                }
            }
        }
        $itemnames = array_unique($itemnames);

        // 2. Fetch all relevant grade items in one query.
        $gradeitemsmap = [];
        $courseitem = $DB->get_record('grade_items', ['courseid' => $courseid, 'itemtype' => 'course']);

        if (!empty($itemnames)) {
            list($sql, $sqlparams) = $DB->get_in_or_equal($itemnames);
            $sqlparams[] = $courseid;
            $items = $DB->get_records_sql(
                "SELECT id, itemname FROM {grade_items} WHERE itemname $sql AND courseid = ?",
                $sqlparams
            );
            foreach ($items as $item) {
                $gradeitemsmap[$item->itemname] = $item;
            }
        }

        // 3. Process headers using the in-memory map.
        foreach ($headers as $index => $header) {
            $mapkey = 'mapping_' . $index;

            if (in_array($header, $requiredheaders)) {
                $mapping[$mapkey] = 0; // Ignore context columns for grading.
            } else if (in_array($header, $coursegradeheaders)) {
                $mapping[$mapkey] = $courseitem ? $courseitem->id : 0;
                if ($courseitem) {
                    mtrace("[SUCCESS]: Mapeo exitoso: Header '{$header}' -> grade_item ID" .
                           " {$courseitem->id} (Calificación del curso)");
                }
            } else {
                $item = $gradeitemsmap[$header] ?? null;
                // Fallback: if header ends with " (Real)" and was not found, try without it.
                if (!$item && str_ends_with($header, ' (Real)')) {
                    $baseheader = substr($header, 0, -7);
                    $item = $gradeitemsmap[$baseheader] ?? null;
                }

                if ($item) {
                    $mapping[$mapkey] = $item->id;
                    mtrace("[SUCCESS]: Mapeo exitoso -> Header '{$header}' -> grade_item ID {$item->id}");
                } else {
                    $mapping[$mapkey] = 0; // Ignore column if no item found.
                    mtrace("[WARNING]: No se encontró un ítem de calificación para '{$header}'" .
                           " en el curso ID {$courseid}. La columna será ignorada.");
                }
            }
        }

        return $mapping;
    }

    /**
     * Maps the CSV headers to the corresponding grade item ID.
     * The grade item column is identified as the one that is not 'username' or 'course_shortname'.
     *
     * @param array $params The parameters for the mapping.
     *     - courseid: The ID of the course.
     *     - headers: The CSV headers.
     * @return array The mapping of CSV headers to grade item IDs.
     */
    public function get_grade_import_mapping($params = []): array {
        global $DB;

        $mapping = [];
        $requiredheaders = self::get_expected_csv_headers();
        $coursegradeheaders = [
            'course',
            'Total del curso',
            'Total del curso (Real)',
            'Course total)',
            'Course total (Real)'
        ];
        $courseid = $params['courseid'];
        $headers = $params['headers'];

        if ($this->config->get('mapping') === 'new') {
            $mapping = $this->build_mapping_new(
                $requiredheaders,
                $coursegradeheaders,
                $headers,
                $courseid
            );
        } else {
            $mapping = $this->build_mapping_dynamic(
                $requiredheaders,
                $coursegradeheaders,
                $headers,
                $courseid
            );
        }

        return $mapping;
    }

    /**
     * Process the CSV file.
     */
    public function process_csv() {

        // Data has already been submitted so we can use the $iid to retrieve it.
        $type = "tool_eabcetlbridge_{$this->batchfile->get('id')}";
        $iid = csv_import_reader::get_new_iid($type);
        $csvimport = new csv_import_reader($iid, $type);

        // We need to read it for getting the headers.
        $qtylines = $csvimport->load_csv_content(
            $this->batchfile->get_file_content(),
            $this->batchfile->get('encoding'),
            $this->batchfile->get('delimiter')
        );

        $this->batchfile->set('status', $this->batchfile::STATUS_PROCESSING);
        $this->batchfile->set('qtylines', $qtylines);
        $this->batchfile->save();

        $csvloaderror = $csvimport->get_error();
        if (!empty($csvloaderror)) {
            mtrace("Error al cargar el archivo {$this->batchfile->get('id')}: {$csvloaderror}");
            $this->batchfile->set('status', $this->batchfile::STATUS_FAILED);
            $this->batchfile->save();
        }

        // 1. Validate headers.
        $headers = $csvimport->get_columns();
        $this->validate_headers($headers);

        // 2. Validate content and get courseid.
        $courseid = $this->batchfile->get('courseid');
        mtrace("Procesando archivo {$this->batchfile->get('id')}, asociado al curso {$courseid}, con {$qtylines} líneas.");

        // Get a new import code for updating to the grade book.
        $importcode = get_new_importcode();

        // 3. Find user mapping column and prepare form data for grade import API.
        $useridentifierheaders = ['username'];
        $usermappingindex = -1;

        foreach ($headers as $index => $header) {
            $cleanheader = strtolower(trim($header));
            if (in_array($cleanheader, $useridentifierheaders)) {
                $usermappingindex = $index;
                break;
            }
        }

        if ($usermappingindex === -1) {
            throw new moodle_exception('errornouserid', 'tool_eabcetlbridge', '', null, implode(', ', $useridentifierheaders));
        }

        $formdata = (object) $this->get_grade_import_mapping([
            'courseid' => $courseid,
            'headers' => $headers
        ]);
        $formdata->mapfrom = (string) $usermappingindex;
        $formdata->mapto = 'username';
        $formdata->id = $courseid;
        $formdata->iid = $iid;
        $formdata->importcode = $importcode;
        $formdata->verbosescales = 1; // Show grades as text if available.
        $formdata->forceimport = 0; // Do not force import.
        $formdata->submitbutton = 'eABC import';

        // 4. Use Moodle's grade import API to process the data.
        $gradeimport = new gradeimport_csv_load_data($this->batchfile);
        $status = $gradeimport->prepare_import_grade_data(
            $headers,
            $formdata,
            $csvimport,
            $courseid,
            false, // Groupid.
            false, // Create users.
            $formdata->verbosescales,
        );

        // At this stage if things are all ok, we commit the changes from temp table.
        if ($status) {
            grade_import_commit($courseid, $importcode, true, false);
            $this->batchfile->set('status', $this->batchfile::STATUS_COMPLETED);
            $this->batchfile->save();
        } else {
            // Handle errors during import.
            $importerrors = $gradeimport->get_gradebookerrors();
            $importerrors[] = get_string('importfailed', 'grades');
            mtrace(implode("\n", $importerrors));
            $this->batchfile->set('status', $this->batchfile::STATUS_FAILED);
            $this->batchfile->save();
        }

        if ($gradeimport->nonexistentusers) {
            $size = count($gradeimport->nonexistentusers);
            mtrace("#### Cantidad de usuarios que no existen: {$size} usuarios. ####");
            foreach ($gradeimport->nonexistentusers as $username) {
                mtrace("- El usuario con username no existe: {$username}");
            }
        }

        if ($gradeimport->processedusers) {
            $size = count($gradeimport->processedusers);
            mtrace("#### Cantidad de usuarios procesados {$size} usuarios. ####");
            foreach ($gradeimport->processedusers as $userid) {
                mtrace("- El usuario con userid ha sido procesado: {$userid} ");
            }
            if (    $this->batchfile::STATUS_COMPLETED &&
                    $this->batchfile->get('type') == $this->batchfile::TYPE_AUTOMATED &&
                    $size > 0
                ) {
                $task = \tool_eabcetlbridge\tasks\adhoc\mark_processed_users::instance(
                    $this->batchfile->get('id'),
                    $this->batchfile->get('courseid'),
                    $gradeimport->processedusers
                );
                $id = \core\task\manager::queue_adhoc_task($task);
                if ($id) {
                    mtrace("Tarea {$id} para marcado de estudiantes procesados enviada a la cola");
                }
            }
        }

        $size = count($gradeimport->usersinfile);
        if (    $this->batchfile::STATUS_COMPLETED &&
                $this->batchfile->get('type') == $this->batchfile::TYPE_AUTOMATED &&
                $size > 0
            ) {

            $task = \tool_eabcetlbridge\tasks\adhoc\register_users_in_a_file::instance(
                $this->batchfile->get('id'),
                $this->batchfile->get('courseid'),
                $gradeimport->usersinfile
            );
            $id = \core\task\manager::queue_adhoc_task($task);
            if ($id) {
                mtrace("Tarea adhoc {$id} para registrar los usuarios en el archivo de importación enviada a la cola");
            }
        }

        return $status;

    }

}
