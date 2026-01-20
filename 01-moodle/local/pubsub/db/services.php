<?php

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_pubsub_upsert_course' => array(
        'classname'    => 'local_pubsub\external\curso',
        'methodname'   => 'upsert_course',
        'description'  => 'Actualizar o crear un curso',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    'local_pubsub_create_sesion' => array(
        'classname'    => 'local_pubsub\external\sesion',
        'methodname'   => 'create_sesion',
        'description'  => 'Crea una sesion en moodle',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    // Temporal hasta migrar lo de 3.5
    'local_pubsub_create_sesion_migrate' => array(
        'classname'    => 'local_pubsub\external\sesion_migrate',
        'methodname'   => 'create_sesion',
        'description'  => 'Crea una sesion en moodle',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    'local_pubsub_update_sesion' => array(
        'classname'    => 'local_pubsub\external\sesion',
        'methodname'   => 'update_sesion',
        'description'  => 'Actualiza una sesion en moodle',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    // Temporal hasta migrar lo de 3.5
    'local_pubsub_update_sesion_migrate' => array(
        'classname'    => 'local_pubsub\external\sesion_migrate',
        'methodname'   => 'update_sesion',
        'description'  => 'Actualiza una sesion en moodle',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    'local_pubsub_create_facilitador' => array(
        'classname'    => 'local_pubsub\external\facilitador',
        'methodname'   => 'create_facilitador',
        'description'  => 'crea un facilitador',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    'local_pubsub_attendance_user' => array(
        'classname'    => 'local_pubsub\external\attendance',
        'methodname'   => 'user',
        'description'  => 'Registrar asistencia',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    'local_pubsub_enrolsesion_user' => array(
        'classname'    => 'local_pubsub\external\inscripcion',
        'methodname'   => 'inscribir',
        'description'  => 'Inscribir usuario a sesion',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    'local_pubsub_enrolparticipantes_elearning' => array(
        'classname'    => 'local_pubsub\external\inscripcion_elearning',
        'methodname'   => 'inscribirelearning',
        'description'  => 'Inscribir participante elearning',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    'local_pubsub_get_documento' => array(
        'classname'    => 'local_pubsub\external\documento',
        'methodname'   => 'get_documento',
        'description'  => 'obtener certificado y diploma',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    'local_pubsub_inscripcion_masiva_sesion_user' => array(
            'classname'    => 'local_pubsub\external\inscripcion_masiva',
            'methodname'   => 'inscribir_masiva',
            'description'  => 'Inscribir usuario a sesion de inscripcion masiva',
            'type'         => 'write',
            'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
            'ajax' => true,
            'loginrequired' => false
        ),
    'local_pubsub_inscripcion_masiva_notificacion' => array(
            'classname'    => 'local_pubsub\external\inscripcion_masiva_notif',
            'methodname'   => 'inscripcion_masiva_notif',
            'description'  => 'Notificacion final de inscripcion masiva',
            'type'         => 'write',
            'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
            'ajax' => true,
            'loginrequired' => false
        ),
        
);


// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'pubsub' => array(
        'functions' => array(
            'local_pubsub_upsert_course',
            'local_pubsub_create_sesion',
            'local_pubsub_update_sesion',
            // Temporal hasta migrar lo de 3.5
            'local_pubsub_update_sesion_migrate',
            'local_pubsub_create_sesion_migrate',
            // Fin: Temporal hasta migrar lo de 3.5
            'local_pubsub_create_facilitador',
            'local_pubsub_attendance_user',
            'local_pubsub_enrolsesion_user',
            'local_pubsub_enrolparticipantes_elearning',
            'local_pubsub_get_documento',
            'local_pubsub_inscripcion_masiva_sesion_user',
            'local_pubsub_inscripcion_masiva_notificacion'
        ),
        'component' => 'local_pubsub',
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_pubsub_ws'
    )
);
