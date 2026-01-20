<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(    
    'message/emma:viewreports' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        )
    ),
);
