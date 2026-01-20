<?php

$tasks = [
    [
        'classname' => 'local_pubsub\task\get_message',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_pubsub\task\get_sessions',
        'blocking' => 0,
        'minute' => '*/1',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_pubsub\task\session_participants',
        'blocking' => 0,
        'minute' => '*/1',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_pubsub\task\register_facilitator',
        'blocking' => 0,
        'minute' => '*/1',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_pubsub\task\update_sessions',
        'blocking' => 0,
        'minute' => '*/1',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_pubsub\task\finalizar_inscripcion_elearning',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '17',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'       
    ],
    [
        'classname' => 'local_pubsub\task\finalizar_pendientes_elearning',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '17',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'       
    ],
];
