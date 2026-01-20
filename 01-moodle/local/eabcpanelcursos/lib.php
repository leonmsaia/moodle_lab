<?php
/* 
function check_facilitador_perfil(){
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
} */
/* 
 */
/* function local_eabcpanelcursos_before_http_headers() {
    global $SESSION;

    $mostrarMenu = false;  
    $mostrarMenu = (isset($SESSION->facilitador) && ($SESSION->facilitador)) ? true : check_facilitador_perfil();
    if ($mostrarMenu) {
        $hook = new \local_eabcpanelcursos\hook\before_http_headers();
        $hook->render_menu();
    }
}
 */
/* 
function local_eabcpanelcursos_extend_navigation(global_navigation $navigation) {   
    global $SESSION;

    $mostrarMenu = false;  
    $mostrarMenu = (isset($SESSION->facilitador) && ($SESSION->facilitador)) ? true : check_facilitador_perfil();

    if ($mostrarMenu) {
        $url = new moodle_url('/local/eabcpanelcursos/view.php');
        $node = navigation_node::create(
            get_string('pluginname', 'local_eabcpanelcursos'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/report', '')
        );
        $node->showinflatnavigation = true;
        $node->classes = array('localeabcpanelcursos');
        $node->key = 'localeabcpanelcursos';

        if (isset($node) && isloggedin()) {
            $navigation->add_node($node);
        }
    }    
}
 */


