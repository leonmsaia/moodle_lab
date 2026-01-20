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


define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
global $CFG;
if(get_config('local_pubsub', 'toemailreport')){

    $course_completion_sql = "SELECT 
    IFNULL(COUNT(cp.timecompleted), 0) as data
    FROM {course_completions} AS cp
    where cp.timecompleted IS NOT NULL
    AND cp.timecompleted 
    BETWEEN (UNIX_TIMESTAMP()  - (24*60*60)) AND UNIX_TIMESTAMP()";
    $course_completion = $DB->get_record_sql($course_completion_sql);
    
    $inscripcion_elearning_sql = "SELECT 
    IFNULL(COUNT(ieb.id), 0) as data
    FROM {inscripcion_elearning_back} ieb
    WHERE UNIX_TIMESTAMP(ieb.createdat)  
    BETWEEN (UNIX_TIMESTAMP()  - (24*60*60)) AND UNIX_TIMESTAMP()";
    $inscripcion_elearning = $DB->get_record_sql($inscripcion_elearning_sql);
    
    $finalizado_enviado_sql = "SELECT 
    IFNULL(COUNT(ceb.id), 0) as data
    FROM {cierre_elearning_back_log} ceb
    WHERE ceb.createdat 
    BETWEEN (UNIX_TIMESTAMP()  - (24*60*60)) AND UNIX_TIMESTAMP()";
    $finalizado_enviado = $DB->get_record_sql($finalizado_enviado_sql);
    
    $finalizados_pendientes_sql = "SELECT 
    IFNULL(COUNT(cc.id), 0) as data
    FROM {inscripcion_elearning_back} AS ieb
    JOIN {course_completions} AS cc ON 
        ieb.id_curso_moodle = cc.course 
        AND ieb.id_user_moodle = cc.userid
    WHERE 
    cc.timecompleted IS NOT null
    AND ieb.timereported = 0 
    AND cc.timecompleted
    BETWEEN (UNIX_TIMESTAMP()  - (24*60*60)) AND UNIX_TIMESTAMP()";
    $finalizados_pendientes = $DB->get_record_sql($finalizados_pendientes_sql);
    
    $datas = array(
        "Cantidad de alumnos inscritos" => $course_completion->data,
        "Cantidad de alumnos con cursos finalizados" => $inscripcion_elearning->data,
        "Cantidad de cursos finalizados al enviados al back" => $finalizado_enviado->data,
        "Cantidad de cursos finalizados pendientes de sincronizar al back" => $finalizados_pendientes->data
    );
    
    
    $file = create_file($datas);
    if (!empty($file)) {
        $fileurl = \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            true
        );
        $url_file = $fileurl->out();
    
        
        /* echo "<br>== <br>";
        $file_storage = $CFG->dirroot . "/filedir/" . substr($file->get_contenthash(), 0, 2) . "/" . substr($file->get_contenthash(), 2, 2) . "/" . $file->get_contenthash();
    
        echo $url_file;
        echo "<br>== <br>"; */
        $msg_html = "Para ver el reporte haga clic en el <a href='" . $url_file . "'>link</a>";
        $email = get_config('local_pubsub', 'toemailreport');
        foreach(explode(",", get_config('local_pubsub', 'toemailreport')) as $mail_senders){
            $success = email_to_user(generate_email_user_s($mail_senders), $CFG->smtpuser, "Reporte Mutual", "", $msg_html);
                var_dump($url_file);
            if ($success == 1) {
                echo "Enviado al correo " . $mail_senders;
            } else {
                echo "No Enviado" . $mail_senders;
            }
        }
        
        
    }
    
} else {
    echo "Correo no configurado";
}


function generate_email_user_s($email, $name = '', $id = -99)
{
    $emailuser = new stdClass();
    $emailuser->email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailuser->email = '';
    }
    $name = format_text($name, FORMAT_HTML, array('trusted' => false, 'noclean' => false));
    $emailuser->firstname = trim(filter_var($name, FILTER_SANITIZE_STRING));
    $emailuser->lastname = '';
    $emailuser->maildisplay = true;
    $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML emails.
    $emailuser->id = $id;
    $emailuser->firstnamephonetic = '';
    $emailuser->lastnamephonetic = '';
    $emailuser->middlename = '';
    $emailuser->alternatename = '';
    return $emailuser;
}

function create_file($data)
{
    $strfile = '';
    $filearray = [];
    // proceso la columna solamente
    foreach ($data as $key => $dato) {
        $filearray[] = $key;
    }
    $strfile .= implode(';', $filearray) . "\n";

    foreach ($data as $datos) {
            $rowarray[] = $datos;
    }
        $strfile .= implode(';', $rowarray) . "\n";

    $fs = get_file_storage();
    $filerecord = [
        "contextid" => \context_system::instance()->id,
        "component" => "local_enrolcompany",
        "filearea" => "inscripciones",
        "filepath" => "/",
        "itemid" => time(),
        "filename" => "Archivo" . (string)time() . '.csv'
    ];

    return $fs->create_file_from_string($filerecord, $strfile);
}