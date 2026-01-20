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
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/lib/grade/grade_item.php');

use csv_import_reader;
use grade_item;
use tool_eabcetlbridge\strategies\grades_strategy;

/**
 * Concrete strategy for migrating Moodle Grades data for a single user from a CSV content.
 *
 * @package   tool_eabcetlbridge
 * @category  strategies
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_grades_strategy extends grades_strategy {

    protected $csvcontent = null;

    /** @var array|null */
    protected $gradesdata = null;

    /**
     * Normaliza un string para comparación, convirtiéndolo a minúsculas,
     * eliminando acentos y caracteres no alfanuméricos.
     *
     * @param string $string El string a normalizar.
     * @return string El string normalizado.
     */
    private function normalize_for_comparison(string $string): string {
        // Convertir a minúsculas.
        $string = mb_strtolower($string, 'UTF-8');
        // Transliterar para quitar acentos.
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        // Eliminar cualquier caracter que no sea letra, número o espacio.
        $string = preg_replace('/[^a-z0-9\s]/', '', $string);
        return trim($string);
    }

    /**
     * {@inheritdoc}
     */
    public static function get_name() {
        return 'Migración de Calificaciones por Usuario (desde CSV en memoria)';
    }

    /**
     * Returns the expected headers for the CSV file.
     *
     * @return array
     */
    public static function get_expected_csv_headers(): array {
        return ['username', 'courseshortname', 'itemname', 'itemtype', 'graderaw'];
    }


    /**
     * Sets the CSV content for the strategy to process from a string.
     *
     * @param string $csvcontent The raw CSV content.
     */
    public function set_csv_content(string $csvcontent) {
        $this->csvcontent = $csvcontent;
    }

    /**
     * Sets the grades data for the strategy to process from an array.
     *
     * @param array $data The structured grades data.
     */
    public function set_grades_data(array $data) {
        $this->gradesdata = $data;
    }

    /**
     * Process the CSV content directly from a string.
     *
     * @return array A summary of the import.
     */
    public function process_csv() {
        global $DB;

        if (is_null($this->csvcontent)) {
            throw new \moodle_exception('csvcontentnotset', 'tool_eabcetlbridge');
        }

        // 1. Parsear CSV y agregar identificadores únicos para la carga masiva.
        $iid = csv_import_reader::get_new_iid('user_grades_strategy');
        $csvimport = new csv_import_reader($iid, 'user_grades_strategy');
        $csvimport->load_csv_content($this->csvcontent, 'UTF-8', 'comma');

        $headers = $csvimport->get_columns();
        $this->validate_headers($headers);

        $groupeddata = [];
        $uniqueusernames = [];
        $uniqueshortnames = [];
        $warnings = [];

        $lineindex = 1;
        $csvimport->init();
        while ($row = $csvimport->next()) {
            $lineindex++;
            $line = array_combine($headers, $row);

            $username = trim($line['username']);
            $courseshortname = trim($line['courseshortname']);

            if (empty($username) || empty($courseshortname)) {
                $warnings[] = [
                    'warningcode' => 'missing_key_data',
                    'message' => "Línea {$lineindex} ignorada: Faltan 'username' o 'courseshortname'."
                ];
                continue;
            }

            $uniqueusernames[$username] = true;
            $uniqueshortnames[$courseshortname] = true;

            $groupeddata[$courseshortname][$username][] = [
                'itemname' => trim($line['itemname']),
                'itemtype' => trim($line['itemtype']),
                'graderaw' => $line['graderaw'], // Se procesará más adelante.
                'line' => $lineindex
            ];
        }

        if (empty($groupeddata)) {
            return ['courses_processed' => 0, 'grades_updated' => 0, 'warnings' => $warnings];
        }

        // 2. Carga Masiva de Datos (Batch Pre-fetching).

        // Obtener IDs de todos los cursos y usuarios relevantes en una sola consulta para cada uno.
        /** @var \moodle_database $DB */
        list($usql, $uparams) = $DB->get_in_or_equal(array_keys($uniqueusernames), SQL_PARAMS_NAMED);
        $usersmap = $DB->get_records_sql_menu("SELECT username, id FROM {user} WHERE username $usql", $uparams);

        list($csql, $cparams) = $DB->get_in_or_equal(array_keys($uniqueshortnames), SQL_PARAMS_NAMED);
        $coursesmap = $DB->get_records_sql_menu("SELECT shortname, id FROM {course} WHERE shortname $csql", $cparams);

        if (empty($coursesmap) || empty($usersmap)) {
            $warnings[] = [
                'warningcode' => 'no_valid_courses_or_users',
                'message' => "No se encontró ningún curso o usuario válido de los proporcionados en el CSV."
            ];
            return ['courses_processed' => 0, 'grades_updated' => 0, 'warnings' => $warnings];
        }

        // Obtener todos los items de calificación para los cursos involucrados.
        list($courseidsql, $courseidparams) = $DB->get_in_or_equal(array_values($coursesmap));
        $allitems = $DB->get_records_sql("SELECT * FROM {grade_items} WHERE courseid $courseidsql", $courseidparams);
        $itemsmap = []; // Mapa: $courseid -> $itemtype -> $itemname -> grade_item instance.
        $scaleids = [];
        foreach ($allitems as $item) {
            if ($item->gradetype == GRADE_TYPE_SCALE && !empty($item->scaleid)) {
                $scaleids[$item->scaleid] = true;
            }
            // Usamos un objeto grade_item para acceder a sus métodos más tarde.
            $gradeiteminstance = new \grade_item($item, false);
            $keyname = $gradeiteminstance->itemname ?? ''; // El item de total del curso tiene itemname null.
            $itemsmap[$item->courseid][$item->itemtype][$keyname] = $gradeiteminstance;
        }

        // Obtener todas las escalas necesarias en una sola consulta.
        $scalesmap = [];
        if (!empty($scaleids)) {
            list($scalesql, $scaleparams) = $DB->get_in_or_equal(array_keys($scaleids));
            $scales = $DB->get_records_sql("SELECT id, scale FROM {scale} WHERE id $scalesql", $scaleparams);
            foreach ($scales as $scale) {
                $options = array_map('trim', explode(',', $scale->scale));
                array_unshift($options, '-'); // Las claves de las escalas comienzan en 1.
                $scalesmap[$scale->id] = $options;
            }
        }

        // Obtener todas las calificaciones existentes para los usuarios y items involucrados.
        list($useridsql, $useridparams) = $DB->get_in_or_equal(array_values($usersmap));
        list($itemidsql, $itemidparams) = $DB->get_in_or_equal(array_keys($allitems));
        $sql = "SELECT userid, itemid, finalgrade FROM {grade_grades} WHERE userid $useridsql AND itemid $itemidsql";
        $gradesrs = $DB->get_recordset_sql($sql, array_merge($useridparams, $itemidparams));
        $existinggradesmap = []; // Mapa: $userid -> $itemid -> finalgrade.
        foreach ($gradesrs as $grade) {
            $existinggradesmap[$grade->userid][$grade->itemid] = $grade->finalgrade;
        }
        $gradesrs->close();

        // 3. Procesamiento en Memoria.
        // Ahora iteramos sobre los datos del CSV y usamos nuestros mapas para una validación y procesamiento rápidos.
        $gradesupdated = 0;
        $coursesprocessed = [];
        $userstoregrade = []; // Mapa: $courseid -> $userid -> true.

        foreach ($groupeddata as $courseshortname => $usersdata) {
            // Validar curso.
            if (!isset($coursesmap[$courseshortname])) {
                $warnings[] = [
                    'warningcode' => 'coursenotfound',
                    'message' => "Curso '{$courseshortname}' no encontrado. Se omiten sus calificaciones."];
                continue;
            }
            $courseid = $coursesmap[$courseshortname];

            foreach ($usersdata as $username => $graderecords) {
                // Validar usuario.
                if (!isset($usersmap[$username])) {
                    $warnings[] = [
                        'warningcode' => 'usernotfound',
                        'message' => "Usuario '{$username}' no encontrado. Se omiten sus calificaciones."];
                    continue;
                }
                $userid = $usersmap[$username];

                // Validar matriculación (is_enrolled es costoso, una consulta directa es más eficiente aquí).
                $context = \context_course::instance($courseid);
                if (!is_enrolled($context, $userid)) {
                    $warnings[] = [
                        'warningcode' => 'usernotenrolled',
                        'message' => "Usuario '{$username}' no está matriculado en '{$courseshortname}'. Se omiten sus calificaciones."];
                    continue;
                }

                foreach ($graderecords as $record) {
                    $itemname = $record['itemname'];
                    $itemtype = $record['itemtype'];
                    $keyname = ($itemtype === 'course') ? '' : $itemname;

                    // Validar item de calificación.
                    if (!isset($itemsmap[$courseid][$itemtype][$keyname])) {
                        $warnings[] = [
                            'warningcode' => 'itemnotfound',
                            'message' => "Línea {$record['line']}: Item '{$itemname}' (tipo: {$itemtype}) no encontrado en '{$courseshortname}'."];
                        continue;
                    }
                    /** @var \grade_item $gradeitem */
                    $gradeitem = $itemsmap[$courseid][$itemtype][$keyname];

                    $newgradevalue = null;
                    $rawvalue = $record['graderaw'];

                    // Procesar y validar la calificación según el tipo (escala o valor).
                    if ($gradeitem->gradetype == GRADE_TYPE_SCALE) {
                        if (isset($scalesmap[$gradeitem->scaleid])) {
                            $scaleoptions = $scalesmap[$gradeitem->scaleid];
                            $key = array_search(trim($rawvalue), $scaleoptions);
                            if ($key !== false) {
                                $newgradevalue = (float) $key;
                            }
                        }
                    } else { // GRADE_TYPE_VALUE o GRADE_TYPE_TEXT.
                        if ($rawvalue !== '' && $rawvalue != '-') {
                            $validfloat = unformat_float($rawvalue, true);
                            if ($validfloat !== false) {
                                $newgradevalue = $validfloat;
                            }
                        }
                    }

                    if (is_null($newgradevalue)) {
                        $warnings[] = [
                            'warningcode' => 'badgrade',
                            'message' => "Línea {$record['line']}: Valor de calificación '{$rawvalue}' inválido para el item '{$itemname}' en '{$courseshortname}'."];
                        continue;
                    }

                    // Lógica de actualización: solo si la nueva calificación es mayor.
                    $existinggrade = $existinggradesmap[$userid][$gradeitem->id] ?? null;

                    if (is_null($existinggrade) || $newgradevalue > $existinggrade) {
                        if ($this->bulk_update_final_grade($gradeitem, $userid, $newgradevalue)) {
                            $gradesupdated++;
                            $userstoregrade[$courseid][$userid] = $gradeitem;
                        } else {
                            $warnings[] = [
                                'warningcode' => 'updatefailed',
                                'message' => "Línea {$record['line']}: Error al actualizar la calificación para '{$username}' en el item '{$itemname}'."];
                        }
                    }
                }
            }
            $coursesprocessed[$courseid] = true;
        }

        // 4. Recalcular Calificaciones Finales.
        // Esto es crucial para que los totales del curso se actualicen correctamente.
        foreach ($userstoregrade as $courseid => $data) {
            foreach ($data as $userid => $gradeitem) {
                grade_regrade_final_grades($courseid, $userid, $gradeitem);
            }
        }

        return [
            'courses_processed' => count($coursesprocessed),
            'grades_updated' => $gradesupdated,
            'warnings' => $warnings
        ];

    }

    /**
     * Process the grades data directly from an array.
     *
     * @return array A summary of the import.
     * @param bool $forceregrade If true, all grades will be updated if are
     * greater than the current value.
     * @param bool $fullregrade If true, a full regrade is triggered after update.
     * @param bool $forceoverride If true, sets the final grade as overridden.
     */
    public function process_grades_data($forceregrade = false, $fullregrade = false, $forceoverride = false) {
        global $DB;

        if (is_null($this->gradesdata)) {
            throw new \moodle_exception('gradesdatanotset', 'tool_eabcetlbridge');
        }

        $username = $this->gradesdata['username'];
        $coursesdata = $this->gradesdata['courses'];
        $warnings = [];

        if (empty($username) || empty($coursesdata)) {
            $warnings[] = [
                'warningcode' => 'missing_key_data',
                'message' => "Datos de entrada inválidos: Faltan 'username' o 'courses'."
            ];
            return ['courses_processed' => 0, 'grades_updated' => 0, 'warnings' => $warnings];
        }

        $uniqueshortnames = array_map(function($course) {
            return $course['courseshortname'];
        }, $coursesdata);

        // 2. Carga Masiva de Datos (Batch Pre-fetching).

        // Obtener ID del usuario.
        $user = $DB->get_record('user', ['username' => $username], 'id, username');
        if (!$user) {
            $warnings[] = ['warningcode' => 'usernotfound', 'message' => "Usuario '{$username}' no encontrado."];
            return ['courses_processed' => 0, 'grades_updated' => 0, 'warnings' => $warnings];
        }
        $userid = $user->id;

        // Obtener IDs de todos los cursos relevantes.
        list($csql, $cparams) = $DB->get_in_or_equal($uniqueshortnames, SQL_PARAMS_NAMED);
        $coursesmap = $DB->get_records_sql_menu(
            "SELECT shortname, id
               FROM {course}
              WHERE shortname $csql",
            $cparams
        );

        if (empty($coursesmap)) {
            $warnings[] = [
                'warningcode' => 'no_valid_courses',
                'message' => "No se encontró ningún curso válido de los proporcionados."
            ];
            return ['courses_processed' => 0, 'grades_updated' => 0, 'warnings' => $warnings];
        }

        // Obtener todos los items de calificación para los cursos involucrados.
        list($courseidsql, $courseidparams) = $DB->get_in_or_equal(array_values($coursesmap));
        $allitems = $DB->get_records_sql(
            "SELECT *
               FROM {grade_items}
              WHERE courseid $courseidsql",
              $courseidparams
        );

        $itemsmap = []; // Mapa: $courseid -> $itemtype -> $itemname -> grade_item instance.
        $normalizeditemsmap = []; // Mapa con nombres normalizados.
        $itemsmapbyidnumber = []; // Mapa: $courseid -> $idnumber -> grade_item instance.
        $scaleids = [];
        foreach ($allitems as $item) {
            if ($item->gradetype == GRADE_TYPE_SCALE && !empty($item->scaleid)) {
                $scaleids[$item->scaleid] = true;
            }
            $gradeiteminstance = new \grade_item($item, false);
            if (!empty($item->idnumber)) {
                $itemsmapbyidnumber[$item->courseid][$item->idnumber] = $gradeiteminstance;
            }
            $keyname = $gradeiteminstance->itemname ?? '';
            $itemsmap[$item->courseid][$item->itemtype][$keyname] = $gradeiteminstance;

            $normalizedkey = $this->normalize_for_comparison($keyname);
            $normalizeditemsmap[$item->courseid][$item->itemtype][$normalizedkey] = $gradeiteminstance;
        }

        // Obtener todas las escalas necesarias.
        $scalesmap = [];
        if (!empty($scaleids)) {
            list($scalesql, $scaleparams) = $DB->get_in_or_equal(array_keys($scaleids));
            $scales = $DB->get_records_sql("SELECT id, scale FROM {scale} WHERE id $scalesql", $scaleparams);
            foreach ($scales as $scale) {
                $options = array_map('trim', explode(',', $scale->scale));
                array_unshift($options, '-');
                $scalesmap[$scale->id] = $options;
            }
        }

        // Obtener todas las calificaciones existentes para el usuario y los items involucrados.
        list($itemidsql, $itemidparams) = $DB->get_in_or_equal(array_keys($allitems), SQL_PARAMS_NAMED, 'itemid');
        $sql = "SELECT itemid, finalgrade
                  FROM {grade_grades}
                 WHERE userid = :userid
                       AND itemid $itemidsql
                       AND finalgrade IS NOT NULL";
        $params = array_merge(['userid' => $userid], $itemidparams);
        $existinggradesmap = $DB->get_records_sql_menu($sql, $params);

        // 3. Procesamiento en Memoria.
        $gradesupdated = 0;
        $coursesprocessed = [];
        $userstoregrade = []; // Mapa: $courseid -> $userid -> grade_item.

        foreach ($coursesdata as $courseinfo) {
            $courseshortname = $courseinfo['courseshortname'];

            // Validar curso.
            if (!isset($coursesmap[$courseshortname])) {
                $warnings[] = [
                    'warningcode' => 'coursenotfound',
                    'message' => "Curso '{$courseshortname}' no encontrado. Se omiten sus calificaciones."];
                continue;
            }
            $courseid = $coursesmap[$courseshortname];

            // Validar matriculación.
            $context = \context_course::instance($courseid);
            if (!is_enrolled($context, $userid)) {
                $warnings[] = [
                    'warningcode' => 'usernotenrolled',
                    'message' => "Usuario '{$username}' no está matriculado en '{$courseshortname}'. Se omiten sus calificaciones."];
                continue;
            }

            foreach ($courseinfo['gradeitems'] as $record) {
                if ($record instanceof \stdClass) {
                    $record = (array) $record;
                }

                $itemname = $record['itemname'];
                $itemtype = $record['itemtype'];
                $idnumber = $record['idnumber'] ?? null;

                // Use itemname for mapping, but handle category and course items.
                $keyname = $itemname;
                if ($itemtype === 'course') {
                    $keyname = ''; // Course total item has a null/empty itemname.
                }

                // Validar item de calificación.
                $gradeitem = $itemsmap[$courseid][$itemtype][$keyname] ?? null;
                if (!$gradeitem && !empty($idnumber)) {
                    // Fallback to idnumber if itemname is not found or empty, crucial for categories.
                    $gradeitem = $itemsmapbyidnumber[$courseid][$idnumber] ?? null;
                }

                // Fallback a comparación normalizada si aún no se encuentra.
                if (!$gradeitem) {
                    $normalizedkey = $this->normalize_for_comparison($keyname);
                    $gradeitem = $normalizeditemsmap[$courseid][$itemtype][$normalizedkey] ?? null;
                }

                if (!$gradeitem) {
                    $warnings[] = [
                        'warningcode' => 'itemnotfound',
                        'message' => "Item '{$itemname}' (tipo: {$itemtype}, idnumber: {$idnumber}) no encontrado en '{$courseshortname}'."];
                    continue;
                }

                $newgradevalue = null;
                $rawvalue = $record['graderaw'];

                // Procesar y validar la calificación.
                if ($gradeitem->gradetype == GRADE_TYPE_SCALE) {
                    if (isset($scalesmap[$gradeitem->scaleid])) {
                        $scaleoptions = $scalesmap[$gradeitem->scaleid];
                        $key = array_search(trim($rawvalue), $scaleoptions);
                        if ($key !== false) {
                            $newgradevalue = (float) $key;
                        }
                    }
                } else { // GRADE_TYPE_VALUE o GRADE_TYPE_TEXT.
                    if ($rawvalue !== '' && $rawvalue != '-') {
                        $validfloat = unformat_float($rawvalue, true);
                        if ($validfloat !== false) {
                            $newgradevalue = $validfloat;
                        }
                    }
                }

                if (is_null($newgradevalue)) {
                    $warnings[] = [
                        'warningcode' => 'badgrade',
                        'message' => "Valor de calificación '{$rawvalue}' inválido para el item '{$itemname}' en '{$courseshortname}'."];
                    continue;
                }

                // Lógica de actualización: solo si la nueva calificación es mayor.
                $existinggrade = $existinggradesmap[$gradeitem->id] ?? null;

                // Garantizar que siempre se conserve la nota más alta.
                // Si forcemigrate está activo, se actualiza si la nueva nota es >=.
                // Si no, solo se actualiza si es estrictamente >.
                $shouldupdate = is_null($existinggrade) ||
                                ($forceregrade && $newgradevalue >= $existinggrade) ||
                                (!$forceregrade && $newgradevalue > $existinggrade);

                if ($shouldupdate) {
                    if ($this->bulk_update_final_grade($gradeitem, $userid, $newgradevalue, $forceoverride)) {
                        $gradesupdated++;
                        $userstoregrade[$courseid][$userid] = $gradeitem;
                    } else {
                        $warnings[] = [
                            'warningcode' => 'updatefailed',
                            'message' => "Error al actualizar la calificación para '{$username}' en el item '{$itemname}'."];
                    }
                }

                if ($fullregrade) {
                    grade_regrade_final_grades($courseid, $userid, $gradeitem);
                }

            }
            $coursesprocessed[$courseid] = true;
        }

        // 4. Recalcular Calificaciones Finales para los usuarios afectados.
        // Esto es crucial para que los totales del curso se actualicen correctamente.
        if (!$fullregrade) {
            foreach ($userstoregrade as $courseid => $data) {
                foreach ($data as $userid => $gradeitem) {
                    grade_regrade_final_grades($courseid, $userid, $gradeitem);
                }
            }
        }

        return [
            'courses_processed' => count($coursesprocessed),
            'grades_updated' => $gradesupdated,
            'warnings' => $warnings
        ];
    }

    /**
     * Performs a direct, lightweight update or insert of a final grade.
     *
     * This method is optimized for bulk operations. It bypasses event triggering
     * and does not set the 'overridden' flag, making it much faster for
     * mass grade updates.
     *
     * @param grade_item $gradeitem The grade item to update the grade for.
     * @param int $userid The user's ID.
     * @param float $finalgrade The new final grade.
     * @param bool $overridden If true, sets the overridden flag.
     * @return bool True on success, false on failure.
     */
    private function bulk_update_final_grade(
            \grade_item $gradeitem,
            int $userid,
            float $finalgrade,
            bool $overridden = false): bool {
        global $DB, $USER;

        $grade = new \grade_grade(['itemid' => $gradeitem->id, 'userid' => $userid]);

        // Prepare the record for DB operation.
        $record = new \stdClass();
        $record->itemid = $gradeitem->id;
        $record->userid = $userid;
        $record->rawgrade = $finalgrade; // Actualizar también la nota en bruto.

        // Synchronize the scale values ​​from the grade_item.
        // This is crucial for the correct calculation of the final grade.
        $record->rawgrademin = $gradeitem->grademin;
        $record->rawgrademax = $gradeitem->grademax;
        $record->rawscaleid  = $gradeitem->scaleid;
        $record->finalgrade = $finalgrade;
        $record->usermodified = $USER->id;
        $record->timemodified = time();

        // Only apply override if it's a course total grade item and the flag is true.
        $record->overridden = ($gradeitem->itemtype === 'course' && $overridden) ? 1 : 0;

        if (empty($grade->id)) {
            // Insert a new grade record.
            $record->timecreated = time();
            return (bool)$DB->insert_record('grade_grades', $record, false);
        } else {
            // Update existing grade record.
            $record->id = $grade->id;
            return $DB->update_record('grade_grades', $record);
        }
    }
}
