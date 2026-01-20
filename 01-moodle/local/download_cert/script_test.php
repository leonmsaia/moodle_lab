<?php
require_once('../../config.php');

/** Include essential files */
require_once($CFG->libdir . '/grade/constants.php');

require_once($CFG->libdir . '/grade/grade_category.php');
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/grade/grade_scale.php');
require_once($CFG->libdir . '/grade/grade_outcome.php');

require_once($CFG->libdir . '/gradelib.php');

global $DB;

// This may take a very long time and extra memory.
\core_php_time_limit::raise();
raise_memory_limit(MEMORY_EXTRA);

// Obtener los parámetros desde la URL
$desdeCursoId = isset($_GET['desdeCursoId']) ? (int) $_GET['desdeCursoId'] : 0;
$hastaCursoId = isset($_GET['hastaCursoId']) ? (int) $_GET['hastaCursoId'] : 0;

// Validar parámetros
if ($desdeCursoId <= 0 || $hastaCursoId <= 0 || $desdeCursoId > $hastaCursoId) {
    die("❌ Error: Debes proporcionar un rango válido de IDs de cursos (desdeCursoId y hastaCursoId).\n");
}

// Obtener los cursos en el rango especificado
$courses = $DB->get_records_sql("
    SELECT c.id 
    FROM {course} c
    INNER JOIN {curso_back} cb ON c.id = cb.id_curso_moodle
    WHERE c.id BETWEEN ? AND ? 
    AND (cb.tipomodalidad = '100000000' OR cb.modalidaddistancia = '201320001')
    ", 
    [$desdeCursoId, $hastaCursoId]
);

if (!$courses) {
    die("❌ No se encontraron cursos en el rango especificado.\n");
}

echo "🔍 Procesando cursos desde ID $desdeCursoId hasta ID $hastaCursoId...\n<br>";

// Fórmula base con placeholders
$formula_base = '=((##gi{ASISTENCIA}## - 99) / abs(##gi{ASISTENCIA}## - 99)) * ##gi{TOTAL_CATEGORIA}##';

foreach ($courses as $course) {
    $course_id = $course->id;
    echo "📌 Procesando curso ID: $course_id...\n<br>";

    // Buscar ítems de calificación en 'grade_items'
    $asistencia = $DB->get_record('grade_items', ['courseid' => $course_id, 'itemname' => 'Asistencia']);
    $total_categoria = $DB->get_record_sql("
        SELECT gi.* 
        FROM {grade_items} gi
        WHERE gi.courseid = ? 
        AND gi.itemtype = 'category'
        AND gi.iteminstance = (SELECT id FROM {grade_categories} WHERE fullname = 'Calificación más alta' AND courseid = ?)
        LIMIT 1", [$course_id, $course_id]);

    $total_curso = $DB->get_record('grade_items', ['courseid' => $course_id, 'itemtype' => 'course']);

    
    echo "  🔹 nuevo ': " . print_r($total_curso, true) . PHP_EOL;

    if ($asistencia && $total_categoria && $total_curso) {
        echo "  🔹 ID de grade_items: 'Asistencia': " . $asistencia->id . PHP_EOL;
        echo "  🔹 ID de grade_items: 'Total categoría': " . $total_categoria->id . PHP_EOL;
        echo "  🔹 ID de grade_items: 'Total del curso': " . $total_curso->id . PHP_EOL;

        // Actualizar idnumber para "Asistencia" y "Total categoría"
        $DB->update_record('grade_items', ['id' => $asistencia->id, 'idnumber' => '1']);
        $DB->update_record('grade_items', ['id' => $total_categoria->id, 'idnumber' => '2']);

        echo "  ✅ IDNumbers actualizados: Asistencia=1, Total Categoría=2\n<br>";

        // Reemplazar en la fórmula los IDs reales con el formato ##gi{ID}##
        $formula_final = str_replace(
            ['{ASISTENCIA}', '{TOTAL_CATEGORIA}'], 
            [$asistencia->id, $total_categoria->id], 
            $formula_base
        );

        // Asignar la fórmula al total del curso
        $DB->update_record('grade_items', [
            'id' => $total_curso->id,
            'calculation' => $formula_final
        ]);

        $check_calculation = $DB->get_record('grade_items', ['id' => $total_curso->id], 'id, calculation');
        echo "  🔍 Fórmula guardada en BD: " . $check_calculation->calculation . "<br>";

        $DB->execute("UPDATE {grade_items} SET needsupdate = 1 WHERE courseid = ?", [$course_id]);

        // 🔥 Forzar recalculación de calificaciones
        $regrade = grade_regrade_final_grades($course_id);
        
        echo "  🔹 regrade: " . $regrade . PHP_EOL;

        rebuild_course_cache($course_id, true);

        $cachecoursemodinfo = \cache::make('core', 'coursemodinfo');
        $cachecoursemodinfo->delete($course_id);
        course_modinfo::clear_instance_cache($course_id);
        echo "  🔹 course_id: " . $course_id . PHP_EOL;

        echo "  ✅ Fórmula asignada correctamente en el curso ID: $course_id\n<br>";

        // Obtener iteminstance del ítem de asistencia y actualizar en course_modules
        if ($asistencia->iteminstance) {
            $DB->execute("
                UPDATE {course_modules} 
                SET idnumber = 1
                WHERE course = ? 
                AND instance = ?
                AND module = (SELECT id FROM {modules} WHERE name = 'eabcattendance' LIMIT 1)",
                [$course_id, $asistencia->iteminstance]
            );

            echo "  ✅ IDNumber actualizado en course_modules con iteminstance: 1 \n<br>";


            
        $regrade = grade_regrade_final_grades($course_id);
        } else {
            echo "  ❌ No se encontró iteminstance para Asistencia en el curso ID: $course_id\n<br>";
        }
    } else {
        echo "  ❌ No se encontraron todos los ítems requeridos en el curso ID: $course_id\n<br>";
    }
}

echo "🎯 Proceso finalizado.\n<br>";
?>
