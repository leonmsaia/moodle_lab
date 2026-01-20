<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
  array(
	  'eventname'   => '\core\event\user_loggedin',
    'callback'    => 'local_sso_observer::user_loggedin',
	),
  array(
	  'eventname'   => '\core\event\user_loggedout',
    'callback'    => 'local_sso_observer::user_loggedout',
	),
  array(
	  'eventname' => '\core\event\user_login_failed',
    'callback' => '\local_sso_observer::login_failed',
	),
];