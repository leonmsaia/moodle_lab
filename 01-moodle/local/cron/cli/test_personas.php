<?php
/*
Script para probar funcion get_personas nominativo
*/

define('CLI_SCRIPT', true);

require_once($CFG->dirroot . '/config.php');
require_once($CFG->dirroot . '/local/cron/lib.php');

$rut = '42181413-9';
//$rut = '57444859-K';
//$rut = '72327511-3';

$persona = get_personas($rut, 1);

var_dump($persona);