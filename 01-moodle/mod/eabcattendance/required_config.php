<?php

/////////////////////////////////////
////////////////////////////////////
//////////(5/12/2019 FHS)///////////
////it makes custom user fiels//////
////////////////////////////////////

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $DB, $OUTPUT, $PAGE;


$returno = \mod_eabcattendance\metodos_comunes::make_custom_user_fields();

set_config('passwordpolicy', 0);

var_dump($returno);
