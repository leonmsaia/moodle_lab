<?php

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
global $PAGE, $OUTPUT, $DB, $USER, $CFG;

$imgcontacto = $CFG->wwwroot . '/local/mutualnotifications/pix/contact_mutual.png';
$imglogo = $CFG->wwwroot . '/local/mutualnotifications/pix/logo_mutual.jpg';
$imgfirma = $CFG->wwwroot . '/local/mutualnotifications/pix/firma_mutual.jpg';

echo $OUTPUT->header();

$enrol = '<table WIDTH="550" style="font-family:"Helvetica""> 
	
        <tr>
            <td colspan=""><img src="'.$imglogo.'" align="center"></td>
        </tr>
        <tr>
            <td colspan="">
                <p style="font-family:"Helvetica"">Estimado(a) Patricio Garcia.<br/>
                Te damos la más cordial bienvenida al curso e-learning <b>Fundamentos de Higiene Industrial.</b>, el que se encuentra disponible para que lo comiences a partir de hoy: {a->fromdate}, y para el cual tienes plazo para terminarlo el día {a->todate}<br/><br/>

                Para acceder, debes ingresar al siguiente link: <a href="miscursos.mutual.cl" target="_blank">miscursos.mutual.cl</a><br/>
                Y entrar con tus datos:<br/>
                <ul>
                    <li type="square">Usuario: Rut. Si eres extranjero digita tu número de documento</li>
                    <li type="square">Contraseña: Rut. Si eres extranjero digita tu número de documento</li>
                </ul>
                Ejemplo: usuario 12345678-9 contraseña 12345678-9<br/>
                <b style="font-size:9.5pt;font-family:"Helvetica"">En caso de que tengas RUT, este debe ir sin puntos con guion (K en mayúscula)</b><br/>

                Ante dudas y/o consultas puede contactarse con nuestra mesa de ayuda al 22-8870600 o escribirnos a:<br/> miscursos@mutual.cl<br/> 
                <b>Horario de atención:</b><br/>
                Lunes a jueves de 9:00 a 18:00 hrs<br/> 
                Viernes: de 9:00 a 17:00 hrs<br/><br/> 

                ¡Saludos y éxito en tu curso!<br/> 
                Atentamente,<br/>
                </p>
            </td>
        </tr>
        <tr>
            <td colspan="" style="text-align: center;"><img src="'.$imgfirma.'" align="center" width="270" height="139"></td>
        </tr>
        <tr>
            <td colspan="" style="text-align: center;">
                Rafael Olmos Hernández<br/> 
                Gerente de Prevención de Riesgos.<br/>
                Gerencia Corporativa de SST <br/>
            </td>
        </tr>
    </table>';

$avance = '<table WIDTH="550" style="font-family:"Helvetica""> 
	
        <tr>
            <td colspan=""><img src="'.$imglogo.'" align="center"></td>
        </tr>
        <tr>
            <td colspan="">
                Estimado(a) {$a->user}<br>
                Tu disponibilidad del curso de e-learning {$a->course}, ha llegado al {$a->percent}% de los {$a->days} días para poder completarlo. <br><br>Te recordamos que para acceder, debes ingresar al siguiente link: <a href="miscursos.mutual.cl" target="_blank">miscursos.mutual.cl</a><br><br>Ante dudas y/o consultas puede contactarse con nuestra mesa de ayuda<br><br>¡Saludos y éxito en tu curso!<br><br>Atentamente
                </p>
            </td>
        </tr>
        <tr>
            <td colspan="" style="text-align: center;"><img src="'.$imgfirma.'" align="center" width="270" height="139"></td>
        </tr>
        <tr>
            <td colspan="" style="text-align: center;">
                Rafael Olmos Hernández<br/> 
                Gerente de Prevención de Riesgos.<br/>
                Gerencia Corporativa de SST <br/>
            </td>
        </tr>
    </table>';

$fin = '<table WIDTH="550" style="font-family:"Helvetica""> 
	
        <tr>
            <td colspan=""><img src="'.$imglogo.'" align="center"></td>
        </tr>
        <tr>
            <td colspan="">
                Estimado(a) {$a->user} <br>
                Has completado exitosamente el curso de e-learning {$a->course}.<br><br>
                ¡Te felicitamos por la finalización del curso!<br><br>Atentamente
            </td>
        </tr>
        <tr>
            <td colspan="" style="text-align: center;"><img src="'.$imgfirma.'" align="center" width="270" height="139"></td>
        </tr>
        <tr>
            <td colspan="" style="text-align: center;">
                Rafael Olmos Hernández<br/> 
                Gerente de Prevención de Riesgos.<br/>
                Gerencia Corporativa de SST <br/>
            </td>
        </tr>
    </table>';

echo $enrol;
echo $OUTPUT->footer();
