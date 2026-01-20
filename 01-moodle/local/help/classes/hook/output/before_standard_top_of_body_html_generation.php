<?php
namespace local_help\hook\output;

defined('MOODLE_INTERNAL') || die();

use core\output\mustache_template_finder;
use renderer_base;

class before_standard_top_of_body_html_generation {
    public static function callback(): ?string {
        global $OUTPUT, $CFG;

        if (get_config("local_help", "activepluginhelp") == 1) {
            // Retorna el mismo contenido que antes, usando el template mustache.
            return $OUTPUT->render_from_template('local_help/help', ["wwwroot" => $CFG->wwwroot]);
        }

        return null;
    }
}
