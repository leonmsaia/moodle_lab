<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    
    'local/eabcpanelcursos:view' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            
        ),      
    ),
);
