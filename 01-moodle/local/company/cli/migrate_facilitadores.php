<?php
// migrate_facilitadores.php
// Script para asignar rol "facilitador" a usuarios en cursos usando un CSV.
// Si el usuario no existe en Moodle 4.5, lo crea automáticamente y lo matricula.
//
// CSV esperado: username,firstname,lastname,email,course_shortname

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/enrol/manual/lib.php');

global $DB;

// Configuración
$csvFile = __DIR__ . '/migrate_facilitadores.csv'; // ruta al CSV exportado de 3.5
$roleShortname = 'facilitador';                 // shortname del rol en 4.5

// Buscar rol
$role = $DB->get_record('role', ['shortname' => $roleShortname], '*', MUST_EXIST);

// Abrir CSV
if (!file_exists($csvFile)) {
    die("No se encuentra el archivo CSV: $csvFile\n");
}

if (($handle = fopen($csvFile, "r")) !== false) {
    $header = fgetcsv($handle); // leer cabecera
    $line = 1;

    while (($data = fgetcsv($handle)) !== false) {
        $line++;
        $row = array_combine($header, $data);

        $username = trim($row['username'] ?? '');
        $courseshortname = trim($row['course_shortname'] ?? '');
        $firstname = trim($row['firstname'] ?? 'NA');
        $lastname  = trim($row['lastname'] ?? 'NA');
        $email     = trim($row['email'] ?? ($username . '@example.com'));

        if (!$username || !$courseshortname) {
            echo "Línea $line inválida, se omite\n";
            continue;
        }

        // Buscar usuario en destino
        $user = $DB->get_record('user', ['username' => $username]);

        // Crear usuario si no existe
        if (!$user) {
            echo "Usuario '$username' no existe, creando...\n";
            $newuser = new stdClass();
            $newuser->auth        = 'manual';
            $newuser->confirmed   = 1;
            $newuser->mnethostid  = $CFG->mnet_localhost_id;
            $newuser->username    = $username;
            $newuser->firstname   = $firstname ?: 'NA';
            $newuser->lastname    = $lastname ?: 'NA';
            $newuser->email       = $email;
            $newuser->password    = $username; // Moodle lo hashea internamente
            $newuser->timecreated = time();
            $newuser->timemodified = time();

            try {
                $newuserid = user_create_user($newuser);
                $user = $DB->get_record('user', ['id' => $newuserid], '*', MUST_EXIST);
                echo " -> Usuario creado con id={$user->id}\n";
            } catch (\Exception $e) {
                echo "Error creando usuario '$username': {$e->getMessage()}\n";
                continue; // Saltar este usuario y seguir con el siguiente
            }

        }

        // Buscar curso en destino
        $course = $DB->get_record('course', ['shortname' => $courseshortname]);
        if (!$course) {
            echo "Curso '$courseshortname' no existe en destino, se omite (línea $line)\n";
            continue;
        }

        // Contexto del curso
        $context = context_course::instance($course->id);

        // Matricular usuario en curso
        $manualenrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
        if (!$manualenrol) {
            echo "No hay enrolamiento manual en curso '$courseshortname'\n";
            continue;
        }

        $existing_enrol = $DB->get_record('user_enrolments', [
            'enrolid' => $manualenrol->id,
            'userid'  => $user->id
        ]);

        if (!$existing_enrol) {
            echo "Matriculando usuario '$username' en curso '$courseshortname'...\n";
            $enrol_plugin = enrol_get_plugin('manual');
            $enrol_plugin->enrol_user($manualenrol, $user->id, $role->id);
        } else {
            echo "Usuario '$username' ya matriculado en curso '$courseshortname'\n";
        }

        // 2️⃣ Asignar rol facilitador explícitamente
        if (!user_has_role_assignment($user->id, $role->id, $context->id)) {
            echo "Asignando rol facilitador a '$username' en curso '$courseshortname'\n";
            role_assign($role->id, $user->id, $context->id);
        } else {
            echo "Usuario '$username' ya tiene rol facilitador en curso '$courseshortname'\n";
        }
    }

    fclose($handle);
}

echo "Proceso finalizado.\n";
