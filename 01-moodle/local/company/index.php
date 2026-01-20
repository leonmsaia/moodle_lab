<?php

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/company/classes/metodos_comunes.php');
require_once($CFG->dirroot.'/local/company/classes/form/company_form.php');


require_login();
require_capability('moodle/site:config', \context_system::instance());


$PAGE->set_url(new \moodle_url('/local/company/index.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('managecompanies', 'local_company'));
// add body class and styles
$PAGE->add_body_class('local-company');
$PAGE->requires->css(new \moodle_url('/local/company/styles.css'));

echo $OUTPUT->header();

// Handle edit parameter
$editid = optional_param('edit', 0, PARAM_INT);

$mform = new \local_company\classes\form\company_form();

if ($mform->is_cancelled()) {
    redirect(new \moodle_url('/'));
} else if ($data = $mform->get_data()) {
    // If id present, update
    if (!empty($data->id)) {
        $company = new stdClass();
        $company->id = $data->id;
        $company->name = $data->name;
        $company->shortname = $data->shortname;
    $company->contrato = isset($data->contrato) ? $data->contrato : '';
        $company->city = isset($data->city) ? $data->city : '';
        $company->rut = isset($data->rut) ? $data->rut : '';
        $updated = \local_company\metodos_comunes::update_company($company);
        if ($updated) {
            echo $OUTPUT->notification(get_string('companyupdated', 'local_company'), 'notifysuccess');
        } else {
            echo $OUTPUT->notification(get_string('companyupdateerror', 'local_company'), 'notifyproblem');
        }
    } else {
        $record = new stdClass();
        $record->name = $data->name;
        $record->shortname = $data->shortname;
    $record->contrato = isset($data->contrato) ? $data->contrato : '';
        $record->city = isset($data->city) ? $data->city : '';
        $record->rut = isset($data->rut) ? $data->rut : '';
        $record->country = get_string('defaultcountry', 'local_company') ?: 'CL';
        $record->maildisplay = 2;
        $record->mailformat = 1;
        $record->maildigest = 0;
        $record->autosubscribe = 1;
        $record->trackforums = 0;
        $record->htmleditor = 1;
        $record->screenreader = 0;
        $record->timezone = 99;
        $record->lang = current_language();
        $record->theme = '';
        $record->category = 0;
        $record->profileid = 0;
        $record->suspended = 0;

        $companyid = \local_company\metodos_comunes::create_company($record);

        if ($companyid) {
            echo $OUTPUT->notification(get_string('companycreated', 'local_company'), 'notifysuccess');
        } else {
            echo $OUTPUT->notification(get_string('companycreateerror', 'local_company'), 'notifyproblem');
        }
    }    
}

// If editing, preload form data
if ($editid) {
    $companies = \local_company\metodos_comunes::get_all_companies();
    if (isset($companies[$editid])) {
        $mform->set_data($companies[$editid]);
    }
}

echo html_writer::start_tag('h2', array('class' => '', 'style' => 'margin-bottom: 20px;'));
echo get_string('managecompanies', 'local_company');
echo html_writer::end_tag('h2');
$mform->display();

// Search and pagination parameters
$search = optional_param('search', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);

echo html_writer::start_tag('div', array('class' => 'company-search'));
    $searchform = new \moodle_url('/local/company/index.php');
    $s = html_writer::start_tag('form', array('method' => 'get', 'action' => $searchform));
    $s .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'search', 'value' => s($search), 'placeholder' => get_string('search', 'local_company')));
    $s .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('searchbutton', 'local_company')));
    $s .= html_writer::end_tag('form');
    echo $s;
echo html_writer::end_tag('div');


// Fetch paginated results
$result = \local_company\metodos_comunes::get_companies_paginated($search, $page, $perpage);
$companies = $result['list'];
$total = $result['total'];

if ($companies) {
    $table = new html_table();
    $table->head = array(get_string('companyname', 'local_company'), get_string('companyshortname', 'local_company'), get_string('companyrut', 'local_company'), get_string('companycontrato', 'local_company'), get_string('actions', 'local_company'));
    foreach ($companies as $c) {
        $editurl = new \moodle_url('/local/company/index.php', array('edit' => $c->id, 'search' => $search, 'page' => $page, 'perpage' => $perpage));
        $assignurl = new \moodle_url('/local/company/assign.php', array('companyid' => $c->id));
        $actions = html_writer::link($editurl, get_string('edit', 'local_company')) . ' | ' . html_writer::link($assignurl, get_string('assignusers', 'local_company'));
        $table->data[] = array(format_string($c->name), format_string($c->shortname), isset($c->rut) ? format_string($c->rut) : '', isset($c->contrato) ? format_string($c->contrato) : '', $actions);
    }
    echo html_writer::table($table);

    // Paging bar
    $pagingurl = new \moodle_url('/local/company/index.php', array('search' => $search, 'perpage' => $perpage));
    echo $OUTPUT->paging_bar($total, $page, $perpage, $pagingurl);
} else {
    echo html_writer::tag('p', get_string('nocompanies', 'local_company'));
}

echo $OUTPUT->footer();
