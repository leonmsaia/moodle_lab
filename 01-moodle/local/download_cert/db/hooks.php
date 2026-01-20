<?php
return [
    [
        'hook' => \core\hook\output\before_footer_html_generation::class,
        'callback' => \local_download_cert\hook\output\before_footer_html_generation::class . '::callback',
    ],
];
