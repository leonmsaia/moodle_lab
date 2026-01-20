<?php

$string['pluginname'] = 'Notificaciones de cursos para usuarios';
$string['crontask'] = 'Notificación de avance desde el inicio del curso';
$string['cron_start_course'] = 'Notificación de inicio de curso';
$string['cron_advance_course'] = 'Notificación de avance de curso desde fecha de matriculación';
$string['messageprovider:posts'] = 'Notificaciones de cursos';

$string['enrolment'] = 'Alerta de incripción en el curso {$a->fullname} ';
$string['startcourse'] = 'Alerta de inicio del curso {$a->fullname}';
$string['fiftypercent'] = 'Alerta de 50% de avance del curso {$a->fullname}';
$string['seventyfivepercent'] = 'Alerta de 75% de avance del curso {$a->fullname}';
$string['finished'] = 'Alerta de finalización del curso {$a->fullname}';
$string['fiftypercentfromenrolment'] = 'Alerta de 50% de avance en el curso {$a->fullname} desde la fecha de matriculación';
$string['seventyfivepercentfromenrolment'] = 'Alerta de 75% de avance en el curso {$a->fullname} desde la fecha de matriculación';

$string['subject'] = 'Avance de {$a->percent}% en el curso ELearning Mutual de Seguridad {$a->course}';
$string['messagehtml'] = 'Estimado(a) {$a->user}<br><br> La disponibilidad del curso de e-learning {$a->course}, ha llegado al {$a->percent}% de los 30 días para poder completarlo. <br><br>Te recordamos que para acceder, debes ingresar al siguiente link: <a href="miscursos.mutual.cl" target="_blank">miscursos.mutual.cl</a><br><br>Ante dudas y/o consultas puede contactarse con nuestra mesa de ayuda<br><br>¡Saludos y éxito en tu curso!<br><br>Atentamente';
$string['subjectenrolment'] = 'Inscripción Curso {$a->tipo_curso} Mutual de Seguridad {$a->course}';
$string['messagehtmlenrolment'] = '
    <table WIDTH="600" style="font-family:"Helvetica""> 
        <tr>
            <td style="text-align: center;"><img src="{$a->imglogo}"></td>
        </tr>
        <tr>
            <td>
                <p style="font-family:"Helvetica"">Estimado(a) {$a->user}.<br/>
                Te doy la más cordial bienvenida al curso MUTUAL <b>{$a->course}</b>, <br/><br/>

                Para garantizar la seguridad de tus datos, al iniciar sesión por primera vez en la plataforma <a href="{$a->urlsite}" target="_blank">{$a->urlsite}</a>
                con el número de documento: {$a->username}  y la contraseña {$a->password}, se te solicitará cambiar tu clave, la que debe cumplir con las siguientes reglas:
                <br/>

                <br/>La clave debe tener al menos 8 caracteres o más.
                <br/>La clave debe contener al menos un número.
                <br/>La clave debe contener al menos un carácter especial (*#$%&/()). 
                <br/>
                Recuerda que estas reglas son importantes para proteger tus datos personales y evitar posibles vulneraciones de seguridad.
                <br/><br/>
                                
                Cualquier duda o consulta que tengas llama al Contact center Mutual de Seguridad al 600 2000 555<br> 

                ¡Saludos y éxito en tu curso!<br/><br/> 
                Atentamente,<br/>
                </p>
            </td>
        </tr>
        <tr>
            <td style="text-align: center;"><img src="{$a->imgfirma}" align="center" width="270" height="139"></td>
        </tr>
        <tr>
            <td style="text-align: center;">
                RAFAEL OLMOS HERNÁNDEZ<br/> 
                Gerente de Prevención de Riesgos.<br/>
                Gerencia Corporativa de SST <br/>
            </td>
        </tr>
    </table>';
// CURSO PRESENCIAL
$string['messagehtmlpresencial'] = '
    <table WIDTH="600" style="font-family:"Helvetica""> 
        <tr>
            <td style="text-align: center;"><img src="{$a->imglogo}"></td>
        </tr>
        <tr>
            <td>
                <p style="font-family:"Helvetica"">Estimado(a) {$a->user}.<br/>
                Te doy la más cordial bienvenida al curso MUTUAL <b>{$a->course}</b>, <br/><br/>

                Fecha de la actividad: {$a->todate}.<br/>
                Hora: {$a->hora} <br/>
                Dirección: {$a->direccion} <br/>
                <br/>

                Para garantizar la seguridad de tus datos, al iniciar sesión por primera vez en la plataforma <a href="{$a->urlsite}" target="_blank">{$a->urlsite}</a>
                con el número de documento: {$a->username}  y la contraseña {$a->password}, se te solicitará cambiar tu clave, la que debe cumplir con las siguientes reglas:
                <br/>

                <br/>La clave debe tener al menos 8 caracteres o más.
                <br/>La clave debe contener al menos un número.
                <br/>La clave debe contener al menos un carácter especial (*#$%&/()). 
                <br/>
                Recuerda que estas reglas son importantes para proteger tus datos personales y evitar posibles vulneraciones de seguridad.
                <br/><br/>
                                
                Cualquier duda o consulta que tengas llama al Contact center Mutual de Seguridad al 600 2000 555<br> 

                ¡Saludos y éxito en tu curso!<br/><br/> 
                Atentamente,<br/>
                </p>
            </td>
        </tr>
        <tr>
            <td style="text-align: center;"><img src="{$a->imgfirma}" align="center" width="270" height="139"></td>
        </tr>
        <tr>
            <td style="text-align: center;">
                RAFAEL OLMOS HERNÁNDEZ<br/> 
                Gerente de Prevención de Riesgos.<br/>
                Gerencia Corporativa de SST <br/>
            </td>
        </tr>
    </table>';
// FIN CURSO PRESENCIAL 

// CURSO STREAMING
$string['messagehtmlstreaming'] = '
    <table WIDTH="600" style="font-family:"Helvetica""> 
        <tr>
            <td style="text-align: center;"><img src="{$a->imglogo}"></td>
        </tr>
        <tr>
            <td>
                <p style="font-family:"Helvetica"">Estimado(a) {$a->user}.<br/>
                Te doy la más cordial bienvenida al curso MUTUAL <b>{$a->course}</b>, <br/><br/>
                
                Fecha de la actividad: {$a->todate}.<br/>
                Hora: {$a->hora} <br/>
                <br/>

                Para garantizar la seguridad de tus datos, al iniciar sesión por primera vez en la plataforma <a href="{$a->urlsite}" target="_blank">{$a->urlsite}</a>
                con el número de documento: {$a->username}  y la contraseña {$a->password}, se te solicitará cambiar tu clave, la que debe cumplir con las siguientes reglas:
                <br/>

                <br/>La clave debe tener al menos 8 caracteres o más.
                <br/>La clave debe contener al menos un número.
                <br/>La clave debe contener al menos un carácter especial (*#$%&/()). 
                <br/>
                Recuerda que estas reglas son importantes para proteger tus datos personales y evitar posibles vulneraciones de seguridad.
                <br/><br/>

                Cualquier duda o consulta que tengas llama al Contact center Mutual de Seguridad al 600 2000 555<br> 

                ¡Saludos y éxito en tu curso!<br/><br/> 
                Atentamente,<br/>
                </p>
            </td>
        </tr>
        <tr>
            <td style="text-align: center;"><img src="{$a->imgfirma}" align="center" width="270" height="139"></td>
        </tr>
        <tr>
            <td style="text-align: center;">
                RAFAEL OLMOS HERNÁNDEZ<br/> 
                Gerente de Prevención de Riesgos.<br/>
                Gerencia Corporativa de SST <br/>
            </td>
        </tr>
    </table>';
// FIN CURSO STREAMING

$string['subjectcoursecompletion'] = 'Finalización curso {$a->course}, Mutual de Seguridad';
$string['messagehtmlcoursecompletion'] = '
    <table WIDTH="550" style="font-family:"Helvetica""> 
        <tr>
            <td><img src="{$a->imglogo}" align="center"></td>
        </tr>
        <tr>
            <td><p>
                Estimado(a) {$a->user} <br>
                Te informamos que el plazo de ejecución del curso {$a->tipo_curso} {$a->course}, ha finalizado.<br><br>
                Para saber el resultado de esta actividad, te invitamos a revisar el estado de finalización del curso, ingresando con tus credenciales a la plataforma de aprendizaje <a href="https://miscursos.mutual.cl">https://miscursos.mutual.cl</a>, disponible en el apartado de cursos y diplomas o bien a través de la sucursal virtual en <a href="www.mutual.cl">www.mutual.cl</a> en la descarga de certificados y diplomas.<br><br>
                Atentamente</p>
            </td>
        </tr>
        <tr>
            <td style="text-align: center;"><img src="{$a->imgfirma}" align="center" width="270" height="139"></td>
        </tr>
        <tr>
            <td style="text-align: center;">
                RAFAEL OLMOS HERNÁNDEZ<br/> 
                Gerente de Prevención de Riesgos.<br/>
                Gerencia Corporativa de SST <br/>
            </td>
        </tr>
    </table>';
$string['subjectstartcourse'] = 'Inicio Curso ELearning Mutual de Seguridad {$a->course}';
$string['messagehtmlstartcourse'] = 'Estimado(a) {$a->user}<br><br> El curso {$a->course} ha iniciado<br><br>Estimado(a) {$a->user} <br><br> Te damos la más cordial bienvenida al curso de e-learning {$a->course}, el que se encuentra disponible para que lo comiences a partir de hoy y para el cual tienes plazo para terminarlo de 30 días. <br><br> Para acceder, debes ingresar al siguiente link: <a href="miscursos.mutual.cl" target="_blank">miscursos.mutual.cl</a> <br><br>Y registrarte con tus datos:<br><br>Usuario: Digita tu número de documento<br><br>Contraseña: Digita tu número de documento<br><br>En caso de que tengas RUT, este debe ir sin puntos con guión<br><br>Ante dudas y/o consultas puede contactarse con nuestra mesa de ayuda<br><br>¡Saludos y éxito en tu curso!<br><br>Atentamente';
$string['subject_advancefromenrole'] = 'Recordatorio curso ELearning {$a->course}, ha llegado al {$a->percent}% de los 30 días para poder completarlo.';
$string['messagehtml_advancefromenrole'] = '
    <table WIDTH="550" style="font-family:"Helvetica""> 
        <tr>
            <td><img src="{$a->imglogo}" align="center"></td>
        </tr>
        <tr>
            <td>
                <p style="font-family:"Helvetica"">Estimado(a) {$a->user}<br>
                Te recordamos que para el curso de e-learning {$a->course}, quedan {$a->last_days} días para que el proceso concluya. <br><br>
                Para poder acceder al curso y poder finalizar el proceso, debes ingresar al siguiente link <a href="{$a->urlsite}" target="_blank">{$a->urlsite}</a><br><br>
                Cualquier duda o consulta que tengas llama al Contact center Mutual de Seguridad al 600 2000 555.<br><br>
                Si ya finalizó la actividad favor no considerar esta información.<br><br>
                ¡Saludos y éxito en tu curso!<br><br>
                Atentamente
                </p>
            </td>
        </tr>
        <tr>
            <td style="text-align: center;"><img src="{$a->imgfirma}" align="center" width="270" height="139"></td>
        </tr>
        <tr>
            <td style="text-align: center;">
                RAFAEL OLMOS HERNÁNDEZ<br/> 
                Gerente de Prevención de Riesgos.<br/>
                Gerencia Corporativa de SST <br/>
            </td>
        </tr>
    </table>';
$string['course_select'] = 'Seleccione el curso a modificar';
$string['course_select_all'] = 'Todos lo cursos';
$string['config_notify'] = 'Configuración de Notificaciones';
$string['cron_course_completed'] = 'Notificación de curso completado';
