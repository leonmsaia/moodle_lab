<?php

$observers = array(
        
    array( // Observer para usuario matriculado
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => '\local_mutualnotifications\utils::enrole_observer',
        'priority' => 200,
        'internal' => false
    ),
    array( //Observer para cuando se da de baja a un usuario de curso
        'eventname' => '\core\event\user_enrolment_deleted',
        'callback' => '\local_mutualnotifications\utils::user_unenrolled',
        'priority' => 200,
        'internal' => false
    )
    
);
