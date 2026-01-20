<?php

/**
 * Read and validate Excel file (optimized)
 *
 * @param stored_file $file The file to read
 * @param object $attendance_sesions Attendance session object
 * @param object $cm Course module object
 * @param object $attr Attendance attribute object
 * @param object $course Course object
 * @param object $context Context object
 * @return array List of unique records and errors
 */
function read_and_validate_excel($file, $sessionid, $attendance_sesions, $cm, $attr, $course, $context) {
    global $OUTPUT;

    require_once('./classes/excel/SpreadsheetReader.php');
    $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));

    // ⚙️ Crear archivo temporal (más seguro)
    $tempdir = make_temp_directory('phpexcel');
    $temp_file = $tempdir . '/moodle_' . time() . '.' . $extension;
    file_put_contents($temp_file, $file->get_content());

    $Reader = new SpreadsheetReader($temp_file);
    $Reader->ChangeSheet(0);

    $rowcount = 1;
    $errors = [];
    $records = [];
    $unique_records = [];

    // ✅ Mover la creación de objetos Moodle fuera del bucle (eran recreados por fila)
    $pageparams2 = new mod_eabcattendance_manage_page_params();
    $pageparams2->init($cm);
    $pageparams2->sessionid = $sessionid;
    $pageparams2->grouptype = $attendance_sesions->groupid;
    $att2 = new mod_eabcattendance_structure($attr, $cm, $course, $context, $pageparams2);
    $filtercontrols = new eabcattendance_filter_controls($att2);

    // 📋 Cabeceras requeridas (se usarán también para validación)
    $required_columns = [
        'Tipo de documento', 'Numero de documento', 'Nombres', 'Apellido paterno', 'Apellido materno',
        'Dirección de correo', 'Ciudad', 'Nacionalidad', 'Genero', 'Fecha de nacimiento', 'ROL',
        'Numero adherente', 'Rut adherente', 'Calificacion', 'Asistencia'
    ];

    // 📘 Leer filas
    foreach ($Reader as $Key => $row) {

        // Eliminar espacios en blanco en todas las columnas
        $row = array_map('trim', $row);

        // Leer cabecera
        if ($Key == 0) {
            foreach ($required_columns as $column) {
                if (!in_array($column, $row)) {
                    $errors[] = "Falta la columna requerida: '$column'";
                }
            }
            if (!empty($errors)) {
                break;
            }
            continue;
        }

        // Verificar si las dos primeras columnas están vacías
        if (empty($row[0]) && empty($row[1])) {
            continue;
        }

        $rowcount++;
        $record = new stdClass();
        $record->tipo_documento = $row[0];
        $record->numero_documento = strtolower($row[1]);
        $record->nombres = substr($row[2], 0, 25);
        $record->apellido_paterno = substr($row[3], 0, 25);
        $record->apellido_materno = substr($row[4], 0, 25);
        $record->correo = $row[5];
        $record->ciudad = $row[6];
        $record->nacionalidad = $row[7];
        $record->genero = $row[8];
        $record->fecha_nac = validarYConvertirFecha($row[9]);
        $record->rol = strtoupper($row[10]);
        $record->num_adherente = $row[11];
        $record->rut_adherente = $row[12];
        $record->calificacion = $row[13];
        $record->asistencia = $row[14];

        // Validar columnas vacías (excepto Ciudad)
        foreach ($required_columns as $index => $column) {
            if ((!isset($row[$index]) || $row[$index] === '') && $index != 6) {
                $errors[] = "Columna vacía '$column' en la fila: $rowcount";
            }
        }

        // ⚙️ Validaciones rápidas en memoria
        $valid_tipo_doc = ['RUT', 'PASAPORTE'];
        if (!in_array(strtoupper($record->tipo_documento), $valid_tipo_doc)) {
            $errors[] = "Valor no permitido en 'Tipo documento' (fila $rowcount): $record->tipo_documento. Permitidos: " . implode(', ', $valid_tipo_doc);
        }

        if (!$record->fecha_nac) {
            $errors[] = "Fecha inválida en 'Fecha de nacimiento' (fila $rowcount): $row[9]. Formato esperado dd/mm/aaaa";
        }

        if (strtoupper($record->tipo_documento) == 'RUT' && !\mod_eabcattendance\metodos_comunes::validar_rut($record->numero_documento)) {
            $errors[] = "RUT no válido en 'Número documento' (fila $rowcount): $record->numero_documento";
        }

        if (!\mod_eabcattendance\metodos_comunes::validar_rut($record->rut_adherente)) {
            $errors[] = "RUT adherente no válido (fila $rowcount): $record->rut_adherente";
        }

        if (!is_numeric($record->num_adherente)) {
            $errors[] = "Número adherente no numérico (fila $rowcount): $record->num_adherente";
        }

        if ($record->calificacion < 0 || $record->calificacion > 100) {
            $errors[] = "Calificación fuera de rango (fila $rowcount): $record->calificacion (mín 0, máx 100)";
        }

        $valid_asistencia = [0, 25, 50, 75, 100];
        if (!in_array($record->asistencia, $valid_asistencia)) {
            $errors[] = "Valor no permitido en 'Asistencia' (fila $rowcount): $record->asistencia. Permitidos: " . implode(', ', $valid_asistencia);
        }

        $valid_nacionalidad = ['CHILENA', 'EXTRANJERA'];
        if (!in_array(strtoupper($record->nacionalidad), $valid_nacionalidad)) {
            $errors[] = "Valor no permitido en 'Nacionalidad' (fila $rowcount): $record->nacionalidad. Permitidos: " . implode(', ', $valid_nacionalidad);
        }

        $valid_genero = ['H', 'M', 'O'];
        if (!in_array(strtoupper($record->genero), $valid_genero)) {
            $errors[] = "Valor no permitido en 'Género' (fila $rowcount): $record->genero. Permitidos: " . implode(', ', $valid_genero);
        }

        $valid_rol = ['TRABAJADOR', 'DIRIGENTE SINDICAL', 'EMPLEADOR', 'MIEMBRO COMITé PARITARIO', 'MONITOR O DELEGADO', 'PROFESIONAL SST'];
        if (!in_array(strtoupper(trim($record->rol)), $valid_rol)) {
            $errors[] = "Valor no permitido en 'ROL' (fila $rowcount): $record->rol. Permitidos: " . implode(', ', $valid_rol);
        }

        if (!filter_var($record->correo, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email no válido (fila $rowcount): $record->correo";
        }

        // ✅ Agregar registro al array (solo si no hay errores específicos en esta fila)
        $records[] = $record;
    }

    // 🧹 Eliminar archivo temporal
    unlink($temp_file);

    // Mostrar errores acumulados
    if (!empty($errors)) {
        echo "\n<div style='background-color:#BB4444;color:#fff;padding:10px;'>"
            . "<h4>Errores encontrados:</h4>";
        foreach ($errors as $idx => $val) {
            echo "<div>" . ($idx + 1) . " - " . nl2br($val) . "</div>";
        }
        echo "</div>";
        echo '<script>document.getElementById("loading-spinner").style.display = "none";</script>';
        echo "<div style='text-align: center; margin: 20px 0;'>
                <a href='javascript:history.back();'>Volver a importar archivo</a>
              </div>";
        echo $OUTPUT->footer();
        exit();
    }

    // Filtrar registros únicos
    foreach ($records as $record) {
        $unique_key = $record->tipo_documento . '-' . $record->numero_documento;
        if (!isset($unique_records[$unique_key])) {
            $unique_records[$unique_key] = $record;
        }
    }

    return $unique_records;
}