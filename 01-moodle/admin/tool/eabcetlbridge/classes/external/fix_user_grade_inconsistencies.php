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

namespace tool_eabcetlbridge\external;

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/lib/grade/grade_item.php');
require_once($CFG->dirroot . '/lib/grade/grade_grade.php');

use core_external\external_api;
use core_external\external_value;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core\context\system as context_system;
use core\exception\moodle_exception;
use grade_item;
use grade_grade;
use stdClass;

/**
 * Fix user grade inconsistencies web service.
 *
 * @package   tool_eabcetlbridge
 * @category  external
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fix_user_grade_inconsistencies extends external_api {

    /**
     * Returns parameters for the execute method.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'username' => new external_value(
                PARAM_RAW,
                'The username of the user.',
                VALUE_DEFAULT
            ),
            'courseshortname' => new external_value(
                PARAM_RAW,
                'The course shortname to filter by.',
                VALUE_DEFAULT
            ),
            'itemid' => new external_value(
                PARAM_INT,
                'The grade_item id to filter by.',
                VALUE_DEFAULT
            ),
            'gradeid' => new external_value(
                PARAM_INT,
                'The grade_grades id to fix.',
                VALUE_DEFAULT
            ),
            'limit' => new external_value(
                PARAM_INT,
                'The maximum number of records to process in one batch.',
                VALUE_DEFAULT,
                500
            ),
            'doregrade' => new external_value(
                PARAM_BOOL,
                'If true, performs a full regrade of the final grade. Defaults to true.',
                VALUE_DEFAULT,
                true
            ),
            'countremaining' => new external_value(
                PARAM_BOOL,
                'If true, counts the remaining inconsistencies. Can be slow. Defaults to false.',
                VALUE_DEFAULT,
                false
            )
        ]);
    }

    /**
     * Web service that fixes grade inconsistencies for a user.
     *
     * @param string $username
     * @param string|null $courseshortname
     * @param int|null $itemid
     * @param int|null $gradeid
     * @return array
     * @throws moodle_exception
     */
    public static function execute(
        $username = null,
        $courseshortname = null,
        $itemid = null,
        $gradeid = null,
        $limit = 500,
        $doregrade = true,
        $countremaining = false
    ) {
        global $DB, $USER, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'username' => $username,
            'courseshortname' => $courseshortname,
            'itemid' => $itemid,
            'gradeid' => $gradeid,
            'limit' => $limit,
            'doregrade' => $doregrade,
            'countremaining' => $countremaining
        ]);

        $isbatchmode = empty($params['username']) && empty($params['courseshortname']) &&
                       empty($params['itemid']) && empty($params['gradeid']);

        if (!$isbatchmode && empty($params['username'])) {
            throw new moodle_exception(
                'missingparameter', 'webservice', '', 'username is required when filtering by course, item, or grade'
            );
        }

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        // Construcción de la consulta SQL optimizada
        // Nota: Obtenemos solo lo necesario para identificar la inconsistencia y corregirla.
        $gradeobject = new grade_grade();
        $allgradefields = array_merge(
            $gradeobject->required_fields,
            array_keys($gradeobject->optional_fields)
        );
        $gradefields = self::get_custom_sql_fields('grade_grades', $allgradefields, 'gg', 'gg_');

        // Campos del ítem necesarios para la corrección.
        $itemfields = "gi.id AS gi_id,
                       gi.grademax AS gi_grademax,
                       gi.grademin AS gi_grademin,
                       gi.scaleid AS gi_scaleid,
                       gi.courseid AS gi_courseid";

        $sql = "SELECT $gradefields, $itemfields
                  FROM {grade_grades} gg
                  JOIN {grade_items} gi ON gi.id = gg.itemid
                  JOIN {course} c ON gi.courseid = c.id
                  JOIN {user} u ON gg.userid = u.id";

        $where = [];
        $sqlparams = [];

        if (!$isbatchmode) {
            $user = $DB->get_record('user', [
                'username' => $params['username'],
                'mnethostid' => $CFG->mnet_localhost_id
            ], 'id', MUST_EXIST);
            $where[] = "gg.userid = :userid";
            $sqlparams['userid'] = $user->id;
        }

        // Condición de inconsistencia.
        $grademaxnoteq = $DB->sql_equal('gg.rawgrademax', 'gi.grademax', true, true, true);
        $grademinetreq = $DB->sql_equal('gg.rawgrademin', 'gi.grademin', true, true, true);
        $scaleidnoteq = $DB->sql_equal('gg.rawscaleid', 'gi.scaleid', true, true, true);
        $inconsistencies = [
            "$grademaxnoteq",
            "$grademinetreq",
            "$scaleidnoteq"
        ];
        $where[] = "(" . implode(" OR ", $inconsistencies) . ")";

        if (!empty($params['gradeid'])) {
            $where[] = "gg.id = :gradeid";
            $sqlparams['gradeid'] = $params['gradeid'];
        } else if (!empty($params['itemid'])) {
            $where[] = "gg.itemid = :itemid";
            $sqlparams['itemid'] = $params['itemid'];
        } else if (!empty($params['courseshortname'])) {
            $course = $DB->get_record('course', [
                'shortname' => $params['courseshortname']
            ], 'id', MUST_EXIST);
            $where[] = "gi.courseid = :courseid";
            $sqlparams['courseid'] = $course->id;
        }

        $sql .= " WHERE " . implode(" AND ", $where);

        // Limitar resultados para evitar memory leaks en procesos masivos.
        $limitfrom = 0;
        $limitnum = $isbatchmode ? $params['limit'] : 0;

        $gradestofix = $DB->get_records_sql($sql, $sqlparams, $limitfrom, $limitnum);

        $fixedgrades = [];
        $itemstoregrade = []; // Estructura: [itemid => [userid1, userid2...]].
        $now = time();

        // 3. INICIO TRANSACCIÓN - Corrección de Metadatos
        $transaction = $DB->start_delegated_transaction();

        try {
            foreach ($gradestofix as $record) {
                $gradedata = self::custom_extract_record('grade_grades', $record, 'gg_');

                // Usamos grade_grade para asegurar que se disparen los hooks correctos al actualizar.
                $grade = new grade_grade($gradedata, false);

                $oldfinalgrade = $grade->finalgrade;

                // Sincronizar valores desde los datos del grade_item (sin instanciar objeto completo todavía)
                // Usamos los alias definidos en $itemfields.
                $grade->rawgrademax = $record->gi_grademax;
                $grade->rawgrademin = $record->gi_grademin;
                $grade->rawscaleid  = $record->gi_scaleid;

                // Metadata de modificación
                $grade->usermodified = $USER->id;
                $grade->timemodified = $now;

                // Actualizamos el registro en BD.
                // update('restore') evita validaciones excesivas pero guarda historia si está activa.
                // Usamos 'inconsistency_fix' como source para rastreo en logs.
                $grade->update('inconsistency_fix');

                // Guardamos referencia para la fase de recalificación diferida.
                if (!isset($itemstoregrade[$grade->itemid])) {
                    $itemstoregrade[$grade->itemid] = [];
                }
                $itemstoregrade[$grade->itemid][] = $grade->userid;

                $fixedgrades[] = [
                    'gradeid' => (int)$grade->id,
                    'itemid' => (int)$grade->itemid,
                    'userid' => (int)$grade->userid,
                    'oldfinalgrade' => $oldfinalgrade,
                    // Nota: newfinalgrade aún no se ha recalculado, se hará en el paso siguiente.
                    'newfinalgrade' => null 
                ];

                unset($grade); // Liberar memoria.
            }

            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw new moodle_exception('dbupdatefailed', 'error', '', $e->getMessage());
        }

        // 4. FASE DE RECALIFICACIÓN (Fuera de la transacción del lote para evitar bloqueos largos)
        // Esto asegura que la nota final (finalgrade) se recalcule con los nuevos límites (max/min).
        if ($doregrade) {
            // Aumentar tiempo y memoria para esta fase pesada.
            \core_php_time_limit::raise();
            raise_memory_limit(MEMORY_EXTRA);

            foreach ($itemstoregrade as $itemid => $userids) {
                // Instanciamos el grade_item UNA SOLA VEZ por grupo.
                $gradeitem = grade_item::fetch(['id' => $itemid]);

                if (!$gradeitem) {
                    continue;
                }

                foreach ($userids as $userid) {
                    // Recalcular calificación final para este usuario específico.
                    // Este método calcula la nota final basada en el rawgrade y los (ahora corregidos) min/max.
                    $gradeitem->regrade_final_grades($userid);

                }
                unset($gradeitem);
            }
        }

        // 5. Cálculo de restantes y construcción del mensaje de respuesta.
        $remaining = 0;
        $message = count($fixedgrades) . ' grade inconsistencies fixed.';

        if ($params['doregrade']) {
            $message .= ' Grades were regraded.';
        } else {
            $message .= ' Regrading was skipped.';
        }

        if ($isbatchmode && $params['countremaining']) {
            $countsql = "SELECT COUNT(gg.id)
                           FROM {grade_grades} gg
                           JOIN {grade_items} gi ON gi.id = gg.itemid
                          WHERE " . implode(" AND ", $where);
            unset($sqlparams['userid']);
            $remaining = (int)$DB->count_records_sql($countsql, $sqlparams);
            $message .= ' Remaining inconsistencies: ' . $remaining;
        }

        return [
            'status' => 'success',
            'message' => $message,
            'fixedgrades' => $fixedgrades
        ];
    }

    /**
     * Define the return structure for the web service.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_TEXT, 'The status of the operation (e.g., success).'),
                'message' => new external_value(PARAM_TEXT, 'A summary message of the operation.'),
                'fixedgrades' => new external_multiple_structure(
                    new external_single_structure([
                        'gradeid' => new external_value(PARAM_INT, 'The grade_grades record id.'),
                        'itemid' => new external_value(PARAM_INT, 'The grade_item id.'),
                        'userid' => new external_value(PARAM_INT, 'The user id.'),
                        'oldfinalgrade' => new external_value(PARAM_FLOAT, 'The raw grade before fixing.'),
                        'newfinalgrade' => new external_value(PARAM_FLOAT, 'The final grade after fixing.')
                    ]),
                    'List of grades that were fixed.',
                    VALUE_OPTIONAL
                )
            ]
        );
    }

    /**
     * Extract a record from a row of data.
     *
     * Most likely used in combination with {@link self::get_sql_fields()}. This method is
     * simple enough to be used by non-persistent classes, keep that in mind when modifying it.
     *
     * e.g. persistent::extract_record($row, 'user'); should work.
     *
     * @param string $tablename The name of the table.
     * @param stdClass $row The row of data.
     * @param string $prefix The prefix the data fields are prefixed with, defaults to the table name followed by underscore.
     * @return stdClass The extracted data.
     */
    public static function custom_extract_record($tablename, $row, $prefix = null) {
        if ($prefix === null) {
            $prefix = str_replace('_', '', $tablename) . '_';
        }
        $prefixlength = strlen($prefix);

        $data = new stdClass();
        foreach ($row as $property => $value) {
            if (strpos($property, $prefix) === 0) {
                $propertyname = substr($property, $prefixlength);
                $data->$propertyname = $value;
            }
        }

        return $data;
    }

    /**
     * Return the list of fields for use in a SELECT clause.
     *
     * Having the complete list of fields prefixed allows for multiple persistents to be fetched
     * in a single query. Use {@link self::custom_extract_record()} to extract the records from the query result.
     *
     * @param string $tablename The name of the table.
     * @param array $properties The properties/fields of the table.
     * @param string $alias The alias used for the table.
     * @param string $prefix The prefix to use for each field, defaults to the table name followed by underscore.
     * @param array $options Additional options.
     *  - propertiesdefinition: If true, use the properties definition instead of the property name.
     * @return string The SQL fragment.
     */
    public static function get_custom_sql_fields($tablename, $properties, $alias, $prefix = null, $options = []) {
        global $CFG;
        $fields = array();

        if ($prefix === null) {
            $prefix = str_replace('_', '', $tablename) . '_';
        }

        $propertiesdefinition = $options['propertiesdefinition'] ?? false;

        foreach ($properties as $key => $property) {
            $text = '';
            $as = '';
            if ($propertiesdefinition) {
                $text = "{$alias}.{$key}";
            } else {
                $as = $prefix . $property;
                $text = $alias . '.' . $property . ' AS ' . $as;
            }
            $fields[] = $text;

            // Warn developers that the query will not always work.
            if (!empty($as) && $CFG->debugdeveloper && strlen($as) > 30) {
                throw new \coding_exception("The alias '$as' for column '$alias.$property' exceeds 30 characters" .
                    " and will therefore not work across all supported databases.");
            }
        }

        return implode(', ', $fields);
    }
}
