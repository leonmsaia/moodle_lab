<?php

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_sso_company' => array(
        'classname'    => 'local_sso\external\sso',
        'methodname'   => 'get_company',
        'description'  => 'Consultar información de empresa de un usuario',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    'local_sso_validate_login' => array(
        'classname'    => 'local_sso\external\sso',
        'methodname'   => 'validate_login',
        'description'  => 'Login',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    'local_sso_validate_get_user_username' => array(
        'classname'    => 'local_sso\external\sso',
        'methodname'   => 'get_user_username',
        'description'  => 'conultar el nombre de usuario de un usuario',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    'local_sso_create_user' => array(
        'classname'    => 'local_sso\external\sso',
        'methodname'   => 'create_user',
        'description'  => 'api para crear usuario',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    ),
    'local_sso_update_password_user' => array(
        'classname'    => 'local_sso\external\sso',
        'methodname'   => 'update_password_user',
        'description'  => 'api para actualizar la contraseña de un usuario',
        'type'         => 'write',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax' => true,
        'loginrequired' => false
    )
    
        
);


// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'sso' => array(
        'functions' => array(
            'local_sso_company',
            'local_sso_validate_login',
            'local_sso_validate_get_user_username',
            'local_sso_create_user',
            'local_sso_update_password_user'
        ),
        'component' => 'local_sso',
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_sso_ws'
    )
);

