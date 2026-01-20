# mutual notifications
Para ejecutar envío de notificaciones de finalización de curso, primero se debe ejecutar las tareas programadas en el siguiente orden:
1. Marcado diario de finalización de actividades y cursos
2. Marcado continuo de finalización de actividades y cursos.
3. Notificación de curso completado.

Para ejecutar envío de notificaciones de avance desde el inicio del curso hasta la fecha fin de curso, se debe ejecutar la siguiente tarea:1. 
1. Notificación de avance desde el inicio del curso

Para ejecutar envío de notificaciones de avance desde la fecha de matriculación hasta la fecha fin de curso, se debe ejecutar la siguiente tarea:
1. Notificación de avance de curso desde fecha de matriculación

Para ejecutar envío de notificaciones deinicio de curso, se debe ejecutar la siguiente tarea:
1. Notificación de inicio de curso

Agregar la siguiente variable de configuracion en el archivo config.php
$CFG->local_mutualnotifications_available_days=30

Si se desea desinstalar y reinstalar el plugin, y además se desea mantener la configuración, hacer lo siguiente:
1. Realizar un backup de la tabla mdl_mutual_log_notifications: Allí se guardan los registros de las notificaciones que ya han sido enviadas a los usuarios
2. Realizar un backup de la tabla mdl_config_plugins: Allí se guardan los registros de la configuración de las notificaciones a enviar.

