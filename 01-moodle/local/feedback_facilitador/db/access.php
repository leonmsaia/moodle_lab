<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    
    'local/feedback_facilitador:view' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            
        ),      
    ),
);
