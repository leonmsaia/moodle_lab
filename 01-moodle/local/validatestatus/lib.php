<?php


/* function local_validatestatus_add_htmlattributes()
{
    global $COURSE, $DB, $PAGE;

    if (\context_course::instance($COURSE->id)){
        $url = $PAGE->url;
        $search = 'mod/assign';
        
        if (strpos($url, $search)){
            $suspend = $DB->get_record('format_eabctiles_suspendgrou',array('courseid' => $COURSE->id ));
            if (!empty($suspend)){
                return array(
                    'class' => 'suspendall',
                );
            }else{
                return array(); 
            }        
        }   
        else{
            return array(); 
        }        
    }     
} */