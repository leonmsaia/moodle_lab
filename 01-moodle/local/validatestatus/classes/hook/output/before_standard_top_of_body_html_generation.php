<?php
namespace local_validatestatus\hook\output;

class before_standard_top_of_body_html_generation {
    public static function execute(): string {
        global $COURSE, $DB, $PAGE;

        if (!isset($COURSE->id)) {
            return '';
        }

        $url = $PAGE->url->out_as_local_url();
        if (strpos($url, 'mod/assign') !== false) {
            $suspend = $DB->get_record('format_eabctiles_suspendgrou', ['courseid' => $COURSE->id]);
            if (!empty($suspend)) {
                return "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.body.classList.add('suspendall');
                    });
                </script>";
            }
        }

        return '';
    }
}
