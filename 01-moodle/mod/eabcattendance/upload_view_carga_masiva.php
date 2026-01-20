<?php
global $CFG, $DB, $PAGE, $OUTPUT, $SESSION, $USER;
require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

require_login();
$context = context_system::instance();

$groupid = required_param('groupid', PARAM_INT);
$id      = required_param('id', PARAM_INT);
$action  = required_param('action', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);

$PAGE->set_url(new moodle_url('/upload_view_carga_masiva.php'));
$PAGE->set_context($context);
$PAGE->set_title('Carga Masiva de Asistencia');
$PAGE->set_heading('Carga Masiva de Asistencia');

global $DB;
$records = $DB->get_records('eabcattendance_carga_masiva',array('id_grupo_moodle' => $groupid));

$groups = $DB->get_record('groups',array('id' => $groupid));

echo $OUTPUT->header();
echo $OUTPUT->heading('Historial carga masiva sesión: '.$groups->name);

echo "<br><div style='text-align: left; margin: 20px 0; '><a href='upload_excel_participants_attendance.php?id=$id&sessionid=$sessionid&action=$action'> << Regresar</a></div>";

echo '<table border="1" cellpadding="5" cellspacing="0" style="font-size: 14px;">';
echo '<tr>';
echo '<th>N°</th>';
echo '<th>Numero Documento</th>';
echo '<th>Nombre</th>';
echo '<th>Correo</th>';
echo '<th>Calificación enviada</th>';
echo '<th>Asistencia enviada</th>';
echo '<th>Fecha Envío</th>';
echo '<th>Recibido</th>';
echo '<th>Fecha Recibido</th>';
echo '<th>Resultado envio</th>';
echo '<th>Mensaje recibido</th>';
echo '<th>Es actualización</th>';
echo '</tr>';

$i = 0;

foreach ($records as $record) {
    $i++;
    $color = ($record->resultado == 100) ? 'red' : 'inherit';
    $error = ($record->resultado == 100) ? 'Error: <br>' : '';
    $recibidofecha = ($record->fecha_recibido) ? date('d-m-Y H:i:s', strtotime($record->fecha_recibido)) : '';
    echo '<tr>';
    echo '<td>' . $i . '</td>';
    echo '<td>' . $record->numero_documento . '</td>';
    echo '<td>' . $record->nombres .' ' .$record->apellido_paterno. ' ' . $record->apellido_materno . '</td>';
    echo '<td>' . $record->correo . '</td>';
    echo '<td>' . $record->calificacion . '</td>';
    echo '<td>' . $record->asistencia . '</td>';
    echo '<td>' . date('d-m-Y H:i:s', strtotime($record->fecha_envio)) . '</td>';
    echo '<td>' . ($record->recibido ? 'Sí' : 'No') . '</td>';
    echo '<td>' .  $recibidofecha . '</td>';
    echo '<td>' . $record->resultado . '</td>';
    echo '<td style="color:' . $color . ';">' . $error . $record->mensaje . '</td>';
    echo '<td>' . ($record->es_update ? 'Sí, N° '. $record->count_update : 'No') . '</td>';
    echo '</tr>';
}

echo '</table>';

echo "<br><div style='text-align: left; margin: 20px 0; '><a href='upload_excel_participants_attendance.php?id=$id&sessionid=$sessionid&action=$action'> << Regresar</a></div>";

echo $OUTPUT->footer();