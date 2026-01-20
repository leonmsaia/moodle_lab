<?php

namespace local_eabcpanelcursos\hook;

use navigation_node;
use moodle_url;

class before_http_headers {
   
    /**
     * Render menu
     *
     * @return void
     */
    public static function render_menu() {
        /** @var \moodle_page $PAGE */
        global $PAGE, $SESSION;

        $mostrarMenu = false;  
        $mostrarMenu = (isset($SESSION->facilitador) && ($SESSION->facilitador)) ? true : self::check_facilitador_perfil();
        if ($mostrarMenu) {

            /** @var \core\navigation\views\primary $primarynav */
            $primarynav = $PAGE->primarynav;

            $url = new moodle_url('/local/eabcpanelcursos/view.php');

            $node = $primarynav->add_node(
                new navigation_node([
                    'text' => get_string('pluginname', 'local_eabcpanelcursos'),
                    'action' => $url,
                    'key' => 'local_eabcpanelcursos',
                ])
            );

            $node->showinflatnavigation = true;

            if ($PAGE->pagetype == 'local-eabcpanelcursos-view') {
                if ($active = $primarynav->find_active_node()) {
                    $active->make_inactive();
                }
                $node->make_active();
            }
        }
    }

    static function  check_facilitador_perfil(){
        global $USER, $SESSION;
    
        $SESSION->facilitador = false;
        $enrol_courses = enrol_get_all_users_courses($USER->id);
    
        foreach($enrol_courses as $enrol_course){
            $context = \context_course::instance($enrol_course->id);
                if (has_capability('local/eabccalendar:view', $context, $USER->id)) { 
                    $SESSION->facilitador = true;
                    break;
                }
        }
        
        return $SESSION->facilitador;
    }
    
}
