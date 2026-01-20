<?php
namespace local_download_cert\hook\output;


class custom_before_footer_html_generation {
    public static function callback(\core\hook\output\before_footer_html_generation $hook): void {
        global $PAGE, $CFG;

        if ($PAGE->url->out(false) === $CFG->wwwroot . '/login/index.php') {
            $html = "<div id='validador_diplomas' style='text-align: center' class='col-12'>
                        <a href='{$CFG->wwwroot}/local/download_cert/validator_certificate.php'>Validador de Diplomas</a>
                     </div>";
            $hook->add_html($html);
        }
    }
}
