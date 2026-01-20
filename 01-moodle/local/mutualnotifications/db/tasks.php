<?php

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_mutualnotifications\task\cron_task',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '01',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'       
    ),
    array(
        'classname' => 'local_mutualnotifications\task\cron_start_course',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '02',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'       
    ),
    array(
        'classname' => 'local_mutualnotifications\task\cron_advance_course',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '03',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'       
    ),
    array(
        'classname' => 'local_mutualnotifications\task\cron_course_completed',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '03',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'       
    )
);