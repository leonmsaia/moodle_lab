Modulo Eabcattendance, modificacion del modulo Attendance para moodle.

Actividad para AHORA
Extraer del core de moodledata el archivo /es/attendance.php para incluirlo en el plugin mod attendance en un nuevo directorio (es) dentro de lang. Traducción más adecuada a que tenga el plugin (cuando cambiemos el nombre el plugin este archivo también tiene que ser cambiado, si no lo agregamos no va a tomar el lenguaje).
Modificar el attendance module, “eabcattendance” para que:
Cuando entras a eabcattendance: 
Agregar un buscador para tomar asistencias	(actualmente pagina)
Agregar pestaña al lado de “Temporary users” que agregue a un usuario nuevo de no existir.
Auto-complete es deseable pero no es urgente. 
Debe buscar el usuario, a ver si exista en el sistema según su username.
Debe indicar si existe en el sistema y está matriculado (informar ambos).
Si existe en el sistema, lo puede agregar al curso. Si no está asignado a un grupo, se lo asigna al que corresponde. Si ya tiene un grupo lo dejamos como esta.   Vos perteneces a este grupo, vas a participar a este curso, en estas secciones. 
Si no existe en el sistema debe registrarse y matricularse en el curso automáticamente. después se le asigna un grupo. 
El usuario va a estar en un grupo, un grupo puede ver una cosa otro grupo puede ver otra cosa, en una determinada actividad:
El usuario pertenece a un grupo, y la sección pertenece a un grupo. 
Los grupos se crean por CURSO
En restricciones de acceso puedes seleccionar el grupo que pertenece la actividad. ejemplo attendance, la persona puede ver attendance. 
Curso editar las propiedades de curso, activo los grupos ( por defecto no está activado) luego agregamos la actividad asistencia y la actividad va a tener esta restricción. 
Usar addmember to group, o funciones de moodle. No usar la query, usar la api te salva de como ver pensar y demás.


Códigos a copiar:

ABM users: https://github.com/moodle/moodle/blob/01aa126848377b5a17c0b57f3b81d1ab430dad86/user/externallib.php

ABM Sessions/Attendance:
https://github.com/danmarsden/moodle-mod_attendance/blob/master/externallib.php



#ABOUT [![Build Status](https://travis-ci.org/danmarsden/moodle-mod_eabcattendance.svg?branch=master)](https://travis-ci.org/danmarsden/moodle-mod_eabcattendance)

The Eabcattendance module is supported and maintained by Dan Marsden http://danmarsden.com

The Eabcattendance module was previously developed by
    Dmitry Pupinin, Novosibirsk, Russia,
    Artem Andreev, Taganrog, Russia.

#PURPOSE
The Eabcattendance module allows teachers to maintain a record of eabcattendance, replacing or supplementing a paper-based eabcattendance register.
It is primarily used in blended-learning environments where students are required to attend classes, lectures and tutorials and allows
the teacher to track and optionally provide a grade for the students eabcattendance.

Sessions can be configured to allow students to record their own eabcattendance and a range of different reports are available.

Documentacion Ws Front utilizando postman, los servicios que se incluyen en la documentación son los siguientes: 
Crear planificación
Registrar usuario
Agregar sesión
Agregar modulo de asistencia

La ruta de la documentación es la siguiente:
https://documenter.getpostman.com/view/4389646/SW7ezmBA
