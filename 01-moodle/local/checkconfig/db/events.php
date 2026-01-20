<?php

defined('MOODLE_INTERNAL') || die();


$observers = array(
 
    array(
        'eventname'   => '\core\event\course_viewed',
        'callback'    => '\local_checkconfig\observer::course_viewed',
    )
);
