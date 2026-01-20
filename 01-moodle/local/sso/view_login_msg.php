<?php
require('../../config.php');
// require_login();

// require_login();
$PAGE->set_url('/local/sso/view_login_msg.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Recuperar datos en sucursal Virtual');
$PAGE->set_heading('Recuperar datos  sucursal Virtual');

echo $OUTPUT->header();

echo '<div class="login-msg-content">';
echo '<h2>Bienvenido a la plataforma</h2>';
echo '<p></p>';
echo '</div>';

$actionurl = new moodle_url('https://www.mutual.cl/portal/publico/mutual/inicio/cuenta-trab/olvido-clave/'); // Cambia esto a la URL de tu acción
        $cancelurl  = new moodle_url('/login/index.php');
        //despues de confirmar o rechazar debo simular el comportamiento nativo para guardar la nota del usuario esta accion de submit es antes de guardar la nota
        // echo $OUTPUT->confirm(
        //     "Contraseña errónea. Recuerde que debe utilizar usuario y contraseña de su cuenta en sucursal virtual",
        //     new single_button($actionurl, 'Ir a sucursal virtual', 'post'),
        //     new single_button($cancelurl, 'Continuar a mis cursos', 'get')
        // );

        echo '
        <div class="container mt-5">
  <div class="card text-center">
  
    <div class="card-body">
      <p class="card-text ">
        👋 ¡Bienvenido!
<br><br>
Ingresa con el mismo usuario y contraseña que utilizas para acceder a tu Sucursal Virtual Trabajador.
<br><br><br>
🔒 ¿Olvidaste tu contraseña? <a href="https://www.mutual.cl/portal/publico/mutual/inicio/cuenta-trab/olvido-clave/" class="">Recupérala aquí</a>
<br><br><br>
🚀 Nueva funcionalidad
<br>
Ahora puedes acceder directamente a tus cursos de capacitación desde la Sucursal Virtual Trabajador. ¡Todo en un solo lugar!
      </p>
    </div>
    <div class="card-footer d-flex justify-content-center gap-2">
      
      <a href="' . $cancelurl . '" class="btn btn-light border  ml-2">Continuar en mis cursos</a>
    </div>
  </div>
</div>
        ';

echo $OUTPUT->footer();