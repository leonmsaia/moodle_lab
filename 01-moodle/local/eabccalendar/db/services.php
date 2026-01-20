<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_wseabccalendar_agenda' => array(
        'classname'   => 'local_eabccalendar_external',
        'methodname'  => 'set_bloqueo',
        'classpath'   => 'local/eabccalendar/externallib.php',
        'description' => 'Metodo para registrar un bloqueo de Agenda del Facilitador.',
        'type'        => 'read',
        'ajax'        => true
    ),
    'local_wseabccalendar_get_bloqueo_agenda' => array(
        'classname'   => 'local_eabccalendar_external',
        'methodname'  => 'get_bloqueo',
        'classpath'   => 'local/eabccalendar/externallib.php',
        'description' => 'Metodo para mostrar los bloqueos de Agenda del Facilitador.',
        'type'        => 'read',
        'ajax'        => true
    ),
    'local_wseabccalendar_delete_bloqueo_agenda' => array(
        'classname'   => 'local_eabccalendar_external',
        'methodname'  => 'delete_bloqueo',
        'classpath'   => 'local/eabccalendar/externallib.php',
        'description' => 'Metodo eliminar un bloqueos de la Agenda del Facilitador.',
        'type'        => 'read',
        'ajax'        => true
    ),

);

$services = array(
    'Eabccalendar' => array(
        'functions' => array(
            'local_wseabccalendar_agenda',
            'local_wseabccalendar_get_bloqueo_agenda',
            'local_wseabccalendar_delete_bloqueo_agenda',
        ),
        'component' => 'local_eabccalendar',
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_eabccalendar_ws'
    )
);
