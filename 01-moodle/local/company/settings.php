<?php
defined('MOODLE_INTERNAL') || die();

$settings = new admin_externalpage(
    'local_company_manage',
    get_string('managecompanies', 'local_company'),
    new moodle_url('/local/company/index.php')
);

$ADMIN->add('localplugins', $settings);
