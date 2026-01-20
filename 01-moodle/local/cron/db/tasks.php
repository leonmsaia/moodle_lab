<?php

$tasks = array(
    array(
        'classname' => 'local_cron\task\finalizarCapacitacionElearnging',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '17',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'       
    ),
    array(
        'classname' => 'local_cron\task\finalizarCapacitacionElearngingClearTable',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '17',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'       
    ),
    array(
        'classname' => 'local_cron\task\mark_reaggregate_completion',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '17',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'       
    ),
    array(
        'classname' => 'local_cron\task\clear_local_cron',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '17',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'       
    )
);
