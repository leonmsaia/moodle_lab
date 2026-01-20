<?php
	
	
	require_once(dirname(__FILE__).'/../../config.php');
	global $CFG, $DB, $OUTPUT, $PAGE, $COURSE;
	require_once($CFG->dirroot.'/mod/eabcattendance/locallib.php');
	require_once($CFG->dirroot.'/group/lib.php');
	
	
	if (\mod_eabcattendance\metodos_comunes::make_custom_user_fields() == 1){
			
		echo "CAMPOS CREADOS";
	}else{
	
		echo "Hubo un problema";
	}

?>
