<?php

/**
 * Adding eabcattendance sessions
 *
 * @package    mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG, $DB, $PAGE, $OUTPUT, $SESSION, $USER;
require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/import_form_user_session.php');
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/grade/constants.php');
require_once($CFG->dirroot.'/mod/eabcattendance/upload_excel_helper.php');
//require_once($CFG->libdir . '/eventslib.php');

$pageparams = new mod_eabcattendance_sessions_page_params();

$id                     = required_param('id', PARAM_INT);
$pageparams->action     = required_param('action', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);

if (optional_param('deletehiddensessions', false, PARAM_TEXT)) {
    $pageparams->action = mod_eabcattendance_sessions_page_params::ACTION_DELETE_HIDDEN;
}

if (empty($pageparams->action)) {
    $url = new moodle_url('/mod/eabcattendance/view.php', array('id' => $id));
    redirect($url, get_string('invalidaction', 'mod_eabcattendance'), 2);
}

$cm     = get_coursemodule_from_id('eabcattendance', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$attr   = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/eabcattendance:manageeabcattendances', $context);

$att = new mod_eabcattendance_structure($attr, $cm, $course, $context, $pageparams);
$url = $att->url_upload_excel_participants_attendance(array('action' => $pageparams->action, 'sessionid' => $sessionid));
$PAGE->set_url($url);
$PAGE->set_title($course->shortname. ": ".$att->name);
$PAGE->set_heading($course->fullname);
$PAGE->force_settings_menu(true);
$PAGE->set_cacheable(true);
$PAGE->navbar->add($att->name);

$currenttab = eabcattendance_tabs::TAB_EXCEL_IMPORT_ATTENDANCE;
$formparams = array('course' => $course, 'cm' => $cm, 'modcontext' => $context, 'att' => $att);

$output = $PAGE->get_renderer('mod_eabcattendance');
$tabs = new eabcattendance_tabs($att, $currenttab);
echo $output->header();
echo $output->heading(get_string('eabcattendanceforthecourse', 'eabcattendance').' :: ' .format_string($course->fullname));
echo $output->render($tabs);

?>
<!-- Spinner -->
 <div id="loading-spinner" style="display: none; text-align: center; margin: 20px 0; ">
    <img src="<?php echo $CFG->wwwroot; ?>/mod/eabcattendance/pix/spinner3.gif" alt="Loading..." style="width: 30%;">
</div>

<?php 
if (empty($entry->id)) {
    $entry = new stdClass;
    $entry->id = 0;
}

$filemanageropts = array('accepted_types' => array('.xlsx'), 'subdirs' => 0, 'maxbytes' => '0', 'maxfiles' => 1, 'context' => $context);
$customdata = array('filemanageropts' => $filemanageropts, 'sessionid' => $sessionid);

$mform = new SimplehtmlFormUploadImportExcelParticipants($url, $customdata);


$itemid = $sessionid;
 
$total_actualizaciones = 0;
$total_inscripciones = 0; 
 
$itemidsesionid = 'attachments_session_'.$sessionid;
$draftitemid = file_get_submitted_draft_itemid($itemidsesionid);

file_prepare_draft_area($draftitemid, $context->id, 'mod_eabcattendance', 'attachment', $sessionid, $filemanageropts);

$entry = new stdClass();
$entry->$itemidsesionid = $draftitemid; 
$mform->set_data($entry);

$guid_roles = explode("\n", get_config('eabcattendance', 'guidroles'));

if (!$mform->get_data()){
    $SESSION->attendance_sess = \mod_eabcattendance\metodos_comunes::get_quota_session_by_sessionid($sessionid);
}

$attendance_sesions = $SESSION->attendance_sess;

$conditions = [
    'id_sesion_moodle' => $sessionid,
    'recibido' => 0,
    'es_update' => 0
];

$cupos_sesion = $DB->get_records('eabcattendance_carga_masiva', $conditions);

//$attendance_sesions->maximoSession = $attendance_sesions->maximoSession - count($cupos_sesion);

$url_descarga = 'Formato carga masiva v_6.xlsx';
$url_ayuda = 'Guía para completar el archivo de carga masiva V1.pdf';

echo "<br><br><div id='cant-cupos'><span style='font-weight: 800;color: #1f4a79;'>Importante: </span><span>Cantidad de cupos disponibles en la sesión: </span><span style='font-family: gothambookregular,Arial,Helvetica,sans-serif;font-weight: 800;color: #1f4a79;'>".$attendance_sesions->maximoSession."</span></div><br><br>";

echo "<span style='font-weight: 800;color: #1f4a79;'>Formato excel carga masiva: </span><span>Descargar excel <a href='$url_descarga' target='_blank'>aquí ".$OUTPUT->action_icon($url_descarga, new pix_icon('f/spreadsheet', 'Planilla'))."</a>  </span><br><br><br>";

echo "<span style='font-weight: 800;color: #1f4a79;'>Guia para completar excel: </span><span>Descargar Guía <a href='$url_ayuda' target='_blank'>aquí ".$OUTPUT->action_icon($url_ayuda, new pix_icon('f/pdf', 'Ayuda'))."</a>  </span><br><br><br>";

echo "<span style='font-weight: 800;color: #1f4a79;'>Consultar historial de carga masiva para esta sesión: </span><span><a href='upload_view_carga_masiva.php?groupid=$attendance_sesions->groupid&id=$id&sessionid=$sessionid&action=$pageparams->action'>aquí </a> </span><br><br><br>";

if ($attendance_sesions->maximoSession <= 0) {
    echo "<br><br><span style='font-weight: 800;background-color:#BB4444;color:#fff;'> No quedan cupos disponibles en esta sesión.</span>";
    echo "<br><span style='font-weight: 800;color: #1f4a79;'>Por favor solicite al administrador ampliar el cupo de esta sesión</span><br><br>";
    echo $OUTPUT->footer();
    exit();
}

 if ($mform->is_cancelled()) {
    redirect($url);
} elseif ($fromform = $mform->get_data()) {
    
    echo '<script>document.getElementById("loading-spinner").style.display = "block";</script>';

    file_save_draft_area_files($draftitemid, $context->id, 'mod_eabcattendance', 'attachment', $sessionid, $filemanageropts);

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_eabcattendance', 'attachment', $sessionid, 'id DESC', false);

    if ($files) {
        $file = reset($files);
        
        $records = read_and_validate_excel($file, $sessionid, $attendance_sesions, $cm, $attr, $course, $context);
        
        try {
            // 1️⃣ Filtrar registros únicos
            $unique_records = [];
            foreach ($records as $record) {
                $unique_key = $record->tipo_documento . '-' . $record->numero_documento;
                if (!isset($unique_records[$unique_key])) {
                    $unique_records[$unique_key] = $record;
                }
            }

            // 2️⃣ Preparar datos para consultas masivas
            $usernames = array_map(fn($r) => $r->numero_documento, $unique_records);
            list($placeholders, $params) = $DB->get_in_or_equal($usernames, SQL_PARAMS_NAMED);

            // 3️⃣ Traer todos los usuarios existentes
            $existing_users = $DB->get_records_select('user', "username $placeholders", $params);

            // 4️⃣ Traer todos los miembros del grupo existentes
            $group_members_params = array_merge(['groupid' => $attendance_sesions->groupid], $params);
            $existing_group_members = $DB->get_records_select('groups_members', "groupid = :groupid AND userid $placeholders", $group_members_params);

            // 5️⃣ Contar registros nuevos para validar cupo
            $count_user_news = 0;
            foreach ($unique_records as $record) {
                $user_existe = $existing_users[$record->numero_documento] ?? null;
                $user_existe_session = ($user_existe && isset($existing_group_members[$user_existe->id])) ? $existing_group_members[$user_existe->id] : null;

                if (!$user_existe || !$user_existe_session) {
                    $count_user_news++;
                }
            }

            if ($attendance_sesions->maximoSession < $count_user_news) {
                throw new moodle_exception('Sin cupo: La cantidad de registros nuevos supera el cupo disponible.');
            }

            // 6️⃣ Traer sesión back
            $sesion_back = $DB->get_record('sesion_back', ['id_sesion_moodle' => $sessionid]);
            if (empty($sesion_back)) {
                throw new moodle_exception("Error: no existe la sesión con id: $sessionid");
            }

            // 7️⃣ Traer registros pendientes de eabcattendance_carga_masiva
            $conditions_pending = [
                'guid_sesion' => $sesion_back->idsesion,
                'guid_evento' => $sesion_back->idevento,
                'id_sesion_moodle' => $sessionid,
                'recibido' => 0,
                'es_update' => 0
            ];
            $pending_records = $DB->get_records('eabcattendance_carga_masiva', $conditions_pending);

            // 8️⃣ Preparar arrays de inserción y actualización
            $to_insert = [];
            $to_update = [];
            $data_post = [];
            $identificador_proceso = uniqid();

            // 9️⃣ Mapear géneros y tipo documento
            $generos_map = ['H' => 1, 'M' => 2, 'O' => 3];
            $tipodoc_map = ['RUT' => 1]; // default 2 para otro
            $valoresRol = [];
            foreach ($guid_roles as $r) {
                if (!empty($r)) {
                    $option = explode("/", $r);
                    $valoresRol[$option[0]] = strtoupper(trim($option[1]));
                }
            }

            foreach ($unique_records as $record) {
                $user = $existing_users[$record->numero_documento] ?? null;
                $user_session = ($user && isset($existing_group_members[$user->id])) ? $existing_group_members[$user->id] : null;

                // Asistencia status
                $attendance_statuses = $DB->get_record('eabcattendance_statuses', [
                    'eabcattendanceid' => $attendance_sesions->eabcattendanceid,
                    'grade' => $record->asistencia
                ]);

                // Genero y tipo documento
                $genero = $generos_map[strtoupper(trim($record->genero))] ?? 1;
                $tipodoc = strtoupper(trim($record->tipo_documento)) === 'RUT' ? 1 : 2;
                $pais = (strtoupper($record->nacionalidad) === 'EXTRANJERA') ? 2 : 1;

                // Rol participante
                $key = array_search(strtoupper($record->rol), $valoresRol);
                $rol_participante = $key ?? '56b5d471-fe15-ea11-a811-000d3a4f6db7';

                // Preparar objeto para Dynamics
                $fecha_nacimiento = strtotime(str_replace("/", "-", $record->fecha_nac));
                $userInscrip = (object)[
                    'tipo' => 'inscripcion',
                    'username' => $record->numero_documento,
                    'firstname' => $record->nombres,
                    'lastname' => $record->apellido_paterno,
                    'apellidomaterno' => $record->apellido_materno,
                    'email' => $record->correo,
                    'participantefechanacimiento' => $fecha_nacimiento,
                    'participantesexo' => $genero,
                    'rol' => $rol_participante,
                    'pais' => $pais,
                    'nroadherente' => $record->num_adherente,
                    'empresarut' => $record->rut_adherente,
                    'tipodoc' => $tipodoc
                ];
                $data_post[] = $userInscrip;

                // Preparar carga masiva
                $carga_masiva = (object)[
                    'user_id' => $user->id ?? null,
                    'guid_sesion' => $attendance_sesions->guid,
                    'guid_evento' => $sesion_back->idevento,
                    'id_curso_moodle' => $course->id,
                    'id_grupo_moodle' => $attendance_sesions->groupid,
                    'id_sesion_moodle' => $sessionid,
                    'tipo_documento' => $tipodoc,
                    'numero_documento' => $record->numero_documento,
                    'nombres' => trim($record->nombres),
                    'apellido_paterno' => trim($record->apellido_paterno),
                    'apellido_materno' => trim($record->apellido_materno),
                    'correo' => $record->correo,
                    'ciudad' => $record->ciudad,
                    'nacionalidad' => $pais,
                    'genero' => $genero,
                    'fecha_nac' => $fecha_nacimiento,
                    'rol' => $rol_participante,
                    'num_adherente' => $record->num_adherente,
                    'rut_adherente' => $record->rut_adherente,
                    'calificacion' => $record->calificacion,
                    'asistencia' => $record->asistencia,
                    'enviado' => true,
                    'fecha_envio' => date("Y-m-d H:i:s"),
                    'identificador_proceso' => $identificador_proceso,
                    'user_statuses_att_id' => $attendance_statuses->id ?? null,
                    'cmid' => $cm->id,
                    'user_id_upload' => $USER->id
                ];

                // Verificar existencia en memoria
                $existing = null;
                foreach ($pending_records as $p) {
                    if ($p->numero_documento == $carga_masiva->numero_documento) {
                        $existing = $p;
                        break;
                    }
                }

                if ($existing) {
                    $carga_masiva->id = $existing->id;
                    $carga_masiva->count_update = $existing->count_update + 1;
                    $to_update[] = $carga_masiva;
                    $total_actualizaciones++;
                } else {
                    $carga_masiva->count_update = 0;
                    $to_insert[] = $carga_masiva;
                    $total_inscripciones++;
                }
            }

            // 10️⃣ Transacción por lote
            $transaction = $DB->start_delegated_transaction();
            try {
                if (!empty($to_insert)) {
                    $DB->insert_records('eabcattendance_carga_masiva', $to_insert);
                }
                foreach ($to_update as $record) {
                    $DB->update_record('eabcattendance_carga_masiva', $record);
                }
                $transaction->allow_commit();
            } catch (Exception $e) {
                $transaction->rollback($e);
                debugging("Error durante inserción/actualización por lotes: " . $e->getMessage(), DEBUG_DEVELOPER);
                throw $e;
            }

            // 11️⃣ Enviar datos a Dynamics
            $ret = \mod_eabcattendance\metodos_comunes::post_user_fields_multi($data_post, $attendance_sesions->guid, $sesion_back->idevento, $identificador_proceso);

            if ($ret['status'] > 299) {
                $DB->delete_records('eabcattendance_carga_masiva', ['identificador_proceso' => $identificador_proceso]);
                throw new moodle_exception('Error en respuesta de back: ' . $ret['data']->Mensaje);
            }

            // Guardar evento
            $dataEvent = [
                'datasend' => json_encode($data_post),
                'response' => json_encode($ret)
            ];
            \mod_eabcattendance\metodos_comunes::save_event_register_participant($context, $dataEvent);

            // Notificación
            $group = $DB->get_record('groups', ['id' => $attendance_sesions->groupid]);
            $user = $DB->get_record('user', ['id' => $USER->id]);
            $eventdata = new \core\message\message();
            $eventdata->courseid = SITEID;
            $eventdata->component = 'moodle';
            $eventdata->name = 'instantmessage';
            $eventdata->userfrom = \core_user::get_noreply_user();
            $eventdata->userto = $user;
            $eventdata->subject = "INSCRIPCION MASIVA";
            $eventdata->fullmessage = count($records)." participante(s) enviados a la sesión: ".$group->name;
            $eventdata->fullmessageformat = FORMAT_HTML;
            $eventdata->fullmessagehtml = count($records)." participante(s) enviados a la sesión: <strong>".$group->name."</strong>";
            $eventdata->notification = 1;
            message_send($eventdata);

        } catch (Exception $e) {
            debugging('Error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            echo '<div class="alert alert-danger">Error al procesar el archivo: ' . $e->getMessage() . '</div>';
            echo '<script>document.getElementById("loading-spinner").style.display = "none";</script>';
            exit();
        }
    
       // --- Guardar archivos adjuntos ---
        //file_save_draft_area_files(0, $context->id, 'mod_eabcattendance', 'attachment', $sessionid, $filemanageropts);

        // --- URL de destino ---
        $redirect_url = new moodle_url('/mod/eabcattendance/upload_view_carga_masiva.php', [
            'groupid' => $attendance_sesions->groupid,
            'id' => $id,
            'action' => $pageparams->action,
            'sessionid' => $sessionid
        ]);

        $message = '<div class="alert alert-success" role="alert" style="text-align:center;">'
         .'<p>Total de participantes enviados: <b>'.count($records).'</b></p>'
         .'<p>Inscripciones: <b>'.$total_inscripciones.'</b></p>'
         .'<p>Actualizaciones: <b>'.$total_actualizaciones.'</b></p>'
         .'<p>Cuando se haya procesado la inscripción recibirá una notificación.</p>'
         .'<p>Esta pagina se redigirá automáticamente en 15 segundos.</p>'
         .'<p><a href="'.$redirect_url->out(false).'">Ir a Historial</a></p>'
         .'</div>';

        echo $message;
        
        echo '<script>
            // Ocultar spinner
            var spinner = document.getElementById("loading-spinner");
            if(spinner) { spinner.style.display = "none"; }
            var cantCupos = document.getElementById("cant-cupos");
            if(cantCupos) { cantCupos.style.display = "none"; }

            // Redirigir después de 15 segundos
            setTimeout(function() {
                window.location.href = "'.$redirect_url->out(false).'";
            }, 15000);
        </script>';
    
    } else {
        echo "<div style='background-color:#BB4444;color:#fff;text-align: center;'><h5 style='color:#fff;'>No se adjuntó ningún archivo</h5></div>";
        echo '<script>document.getElementById("loading-spinner").style.display = "none";</script>';
    }
}else {
   // Asegurarse de que el filepicker esté vacío al mostrar el formulario inicialmente
   $draftitemid = file_get_unused_draft_itemid();
   file_prepare_draft_area($draftitemid, $context->id, 'mod_eabcattendance', 'attachment', 0, $filemanageropts);

   $entry = new stdClass();
   $entry->$itemidsesionid = $draftitemid;
   $mform->set_data($entry);
}

$mform->display();

?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form');
    form.addEventListener('submit', function() {
        document.getElementById('loading-spinner').style.display = 'block';
    });
});
</script>

<?php

function excelDateToDateTime($excelDateValue) {
    $unixDate = ($excelDateValue - 25569) * 86400; // Convertir el valor de Excel a Unix timestamp
    return gmdate("d/m/Y", $unixDate); // Convertir el timestamp Unix a la fecha en formato "dd/mm/yyyy"
}

// Función para validar y convertir fecha
function validarYConvertirFecha($fecha) {
    // Expresión regular para validar el formato dd/mm/yyyy o dd-mm-yyyy
    $regex = '/^(\d{2})[\/-](\d{2})[\/-](\d{4})$/';

    if (preg_match($regex, $fecha, $matches)) {
        // Extraer día, mes y año de la fecha
        $dia = $matches[1];
        $mes = $matches[2];
        $anio = $matches[3];

        // Convertir al formato dd-mm-yyyy
        return sprintf('%02d-%02d-%04d', $dia, $mes, $anio);
    } elseif (is_numeric($fecha)) {
        // Convertir el número de serie de Excel a una fecha
        $fechaConvertida = excelDateToDateTime($fecha);
        // Convertir al formato dd-mm-yyyy
        $dateTime = DateTime::createFromFormat('d/m/Y', $fechaConvertida);
        return $dateTime->format('d-m-Y');
    } else {
        // Si la fecha no tiene el formato esperado, retornar false o manejar el error como prefieras
        return false;
    }
}


echo $OUTPUT->footer();
