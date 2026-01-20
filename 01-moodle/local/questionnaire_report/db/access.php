<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    
    'local/questionnaire_report:view' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(            
        ),      
    ),
);
