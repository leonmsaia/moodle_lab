<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/local/sso/lib.php');

                error_log('paso 1 ');
global $DB;

$shortopts = "";
$longopts  = [
    "file:",           // Ruta al archivo CSV
    "encoding::",      // Opcional
    "delimiter::",     // Opcional
    "migrado::",       // Opcional
    "migradovalue::",  // Opcional
];
                // error_log('paso 2 ');
$options = getopt($shortopts, $longopts);

                // error_log('paso 3');
if (empty($options['file'])) {
    echo "Uso: php process_csv_migrado.php --file=/ruta/archivo.csv [--encoding=UTF-8 --delimiter=comma --migrado=migradologin --migradovalue=1]\n";
    exit(1);
}

                error_log('paso 4 ');
$filepath = $options['file'];
$encoding = $options['encoding'] ?? 'UTF-8';
$delimiter = $options['delimiter'] ?? 'semicolon';
$migrado = $options['migrado'] ?? 'migradologin';
$migradovalue = $options['migradovalue'] ?? '1';

$iid = csv_import_reader::get_new_iid('uploaduser');
$cir = new csv_import_reader($iid, 'uploaduser');
$content = file_get_contents($filepath);
$readcount = $cir->load_csv_content($content, $encoding, $delimiter);
unset($content);

if ($readcount === false || $readcount == 0) {
    echo "Error: archivo vacío o inválido\n";
    exit(1);
}

$cir->init();
$linenum = 1;
$actualizados = 0;
$errornum = 0;

$login = new \local_sso\login();

                // error_log('dentro del csv: ');
// error_log('Processing CSV file: ' . $filepath);
while ($line = $cir->next()) {
    $linenum++;
    try {
        $data_row = $login->process_data_row_migrado($line, $cir->get_columns());
        $data_row_set = $data_row['data'];

        

        // error_log('data_row_set: ' . print_r($data_row_set, true));

        $get_user = $DB->get_record("user", ['username' => $data_row_set['username']]);

        

        // error_log('get_user: ' );
        // error_log( print_r($get_user, true));

        if (!empty($get_user)) {
            $actualizados++;
            set_user_preference($migrado, $migradovalue, $get_user);
        } else {
            $data = [];
            $data['username'] = $data_row_set['username'];
            $data['password'] = $data_row_set['username'];
            $data['firstname'] = $data_row_set['firstname'];
            $data['lastname'] = $data_row_set['lastname'];
            $data['email'] = $data_row_set['email'];


            // Create user.
            $newuserObj = $login->create_user($data_row_set, true);

            // Set user preference.
            set_user_preference($migrado, $migradovalue, $newuserObj);

            $array_aditional_files = array(
                "empresarut" => (string) $data_row_set['profile_field_empresarut'],
                "empresarazonsocial" => (string) $data_row_set['profile_field_empresarazonsocial'],
                "empresacontrato" => (string) $data_row_set['profile_field_empresacontrato'],
            );

            //Guardo en campos personalizados del usuario
            profile_save_custom_fields($newuserObj->id, $array_aditional_files);

            // Add to created users.
            // $createdusers[] = $newuserid;
            if (!empty($data_row_set['raw_password'])) {
                error_log('raw_password: ' . print_r($data_row_set['raw_password'], true));
                $DB->execute("UPDATE {user} SET password = ? WHERE id = ?", [$data_row_set['password'], $newuserObj->id]);
            }

            $procesados++;
        }
    } catch (Exception $e) {
        $errornum++;
        echo "Error en línea $linenum: " . $e->getMessage() . "\n";
    }
}

$cir->close();
$cir->cleanup(true);

// echo "✅ Usuarios actualizados: $actualizados\n";
// echo "❌ Errores: $errornum\n";
