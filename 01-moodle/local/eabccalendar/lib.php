<?php


/* 
function local_eabccalendar_before_http_headers() {
    global $SESSION;

    $mostrarMenu = false;  
    $mostrarMenu = (isset($SESSION->facilitador) && ($SESSION->facilitador)) ? true : check_facilitador();
    if ($mostrarMenu) {
        $hook = new \local_eabccalendar\hook\before_http_headers();
        $hook->render_menu();
    }
} */

// function local_eabccalendar_extend_navigation(global_navigation $navigation) {   
//     global $SESSION;

//     $mostrarMenu = false;  
//     $mostrarMenu = (isset($SESSION->facilitador) && ($SESSION->facilitador)) ? true : check_facilitador();
        
//     if ($mostrarMenu) {
//         $url = new moodle_url('/local/eabccalendar/view.php');
//         $node = navigation_node::create(
//             get_string('pluginname', 'local_eabccalendar'),
//             $url,
//             navigation_node::TYPE_SETTING,
//             null,
//             null,
//             new pix_icon('i/scheduled', '')
//         );
//         $node->showinflatnavigation = true;
//         $node->classes = array('localeabccalendar');
//         $node->key = 'localeabccalendar';

//         if (isset($node) && isloggedin()) {
//             $navigation->add_node($node);
//         }
//         $nod = $navigation->find('calendar',navigation_node::TYPE_UNKNOWN);
//         $nod->remove(); 
//     }    



// }

function check_facilitador(){
    global $USER, $SESSION;

    $SESSION->facilitador = false;
    $enrol_courses = enrol_get_all_users_courses($USER->id);

    foreach($enrol_courses as $enrol_course){
        $context = context_course::instance($enrol_course->id);
            if (has_capability('local/eabccalendar:view', $context, $USER->id)) { 
                $SESSION->facilitador = true;
                break;
            }
    }
    
    return $SESSION->facilitador;
}
