<?php

function auth_plugin_externalcheck() {
    // No hacemos nada aquí para no interferir con el método principal
}

// Hook: Se ejecuta DESPUÉS de que Moodle valide al usuario (sin modificar el flujo existente)
function auth_externalcheck_user_authenticated_hook($user, $username, $password) {
    global $DB;

    // // 1. Verifica si el usuario existe en la API externa (sin almacenar la contraseña)
    // $api_url = 'https://api-otra-plataforma.com/validate';
    // $response = file_get_contents($api_url . '?username=' . urlencode($username));
    
    // // 2. Si la API confirma que existe, ejecuta tu lógica de integración
    // if ($response === 'EXISTS') {
    //     // Ejemplo: Registrar el acceso en una tabla personalizada
    //     $DB->insert_record('auth_externalcheck_logs', [
    //         'userid' => $user->id,
    //         'time' => time()
    //     ]);
        
    //     // O sincronizar datos con la otra plataforma (ej: vía curl)
    //     // ...
    // }
    error_log("auth_externalcheck_user_authenticated_hook: User authenticated: $username");
    error_log("auth_externalcheck_user_authenticated_hook: User ID: " . $password);
}