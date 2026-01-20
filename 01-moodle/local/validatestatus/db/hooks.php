<?php

defined('MOODLE_INTERNAL') || die();

return [
    'core\hook\output\before_standard_top_of_body_html_generation' =>
        \local_validatestatus\hook\output\before_standard_top_of_body_html_generation::class,
];
