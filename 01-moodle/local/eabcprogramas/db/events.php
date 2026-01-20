<?php
$observers = array( 
    array(
        'eventname'   => '\core\event\user_enrolment_created',
        'callback'    => '\local_eabcprogramas\utils::enrol_observer',
        'priority'    => 200,
        'internal'    => false
    ),
    array(
        'eventname'   => '\core\event\user_enrolment_deleted',
        'callback'    => '\local_eabcprogramas\utils::unenrol_observer',
        'priority'    => 200,
        'internal'    => false
    )  
);
