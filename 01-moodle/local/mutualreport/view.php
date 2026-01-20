<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
use local_mutualreport\url;

// Get context.
$context = \context_system::instance();

require_capability('local/mutualreport:view', $context);

$url = url::view_report_elsa_v1();
$pagetitle = get_string('report_elsa_v1', 'local_mutualreport');
$pageheading = get_string('report_elsa_v1_heading', 'local_mutualreport');

/** @var \core_renderer $OUTPUT */
/** @var \moodle_page $PAGE */
global $OUTPUT, $PAGE, $CFG, $SESSION, $USER;
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagetype('local_mutualreport');
$PAGE->set_title($pagetitle);

require_login();

$defaultto = time();
$defaultfrom = $defaultto - 60 * 60 * 24 * 30;
$defaultcompany = '';
$defaultrut = '';
$defaultcourse = '';
$defaultrutcompany = '';
$defaultadherente = '';

$download = optional_param('download', '', PARAM_ALPHA);
$first = optional_param('tifirst', 'empty', PARAM_RAW);
$last = optional_param('tilast', 'empty', PARAM_RAW);

$form = new \local_mutualreport\form\reportform(null, ['userid' => $USER->id]);

if ($data = $form->get_data()) {
    $SESSION->fromdate = $data->fromdate;
    $SESSION->todate = $data->todate;
    $SESSION->company = $data->company;
    $SESSION->rut = $data->rut;
    $SESSION->course = $data->course;
    $SESSION->rut_company = $data->rut_company;
    $SESSION->adherente = $data->adherente;
}


if (!empty($first) && $first !== 'empty') {
    $SESSION->firstletter = $first;
} else if($first == '') {
    $SESSION->firstletter = '';
}

if (!empty($last) && $last !== 'empty') {
    $SESSION->lastletter = $last;
} else if($last == '') {
    $SESSION->lastletter = '';
}

if (empty($SESSION->firstletter)) {
    $firstletter = '';
} else {
    $firstletter = $SESSION->firstletter;
}

if (empty($SESSION->lastletter)) {
    $lastletter = '';
} else {
    $lastletter = $SESSION->lastletter;
}

if (empty($SESSION->fromdate)) {
    $timefrom = $defaultfrom;
} else {
    $timefrom = $SESSION->fromdate;
}

if (empty($SESSION->todate)) {
    $timeto = $defaultto;
} else {
    $timeto = $SESSION->todate;
}

if (empty($SESSION->company)) {
    $company = $defaultcompany;
} else {
    $company = $SESSION->company;
}

if (empty($SESSION->rut)) {
    $rut = $defaultrut;
} else {
    $rut = $SESSION->rut;
}

if (empty($SESSION->rut_company)) {
    $rut_company = $defaultrutcompany;
} else {
    $rut_company = $SESSION->rut_company;
}

if (empty($SESSION->adherente)) {
    $adherente = $defaultadherente;
} else {
    $adherente = $SESSION->adherente;
}

if (empty($SESSION->course)) {
    $course = $defaultcourse;
} else {
    $course = $SESSION->course;
}

$fields = \local_mutualreport\utils::get_fields_sql();
$from = \local_mutualreport\utils::get_from_sql();
$params = [
    'timefrom' => $timefrom,
    'timeto' => $timeto,
    'firstletter' => $firstletter,
    'lastletter' => $lastletter,
    'company' => $company,
    'rut' => $rut,
    'course' => $course,
    'rut_company' => $rut_company,
    'adherente' => $adherente,
];
$where = \local_mutualreport\utils::get_where_sql($params);


$table = new \local_mutualreport\table('mutualreport');
$table->set_sql($fields, $from, $where);
$table->define_baseurl($url);
$table->is_downloadable(true);
$table->is_downloading($download, 'reporteusuarios', 'Reporte usuarios');
$table->define_headers(\local_mutualreport\utils::get_headers());
$table->define_columns(\local_mutualreport\utils::get_columns());
$table->show_download_buttons_at(array(TABLE_P_TOP, TABLE_P_BOTTOM));


if (empty($download)) {
    /** @var \core\output\core_renderer $OUTPUT */
    echo $OUTPUT->header();
    $actionbar = new local_mutualreport\output\elsa_action_bar(
        $context,
        $url,
        $pagetitle,
        $pageheading,
    );
    if ($actionbar) {
        echo $OUTPUT->render_from_template(
            $actionbar->get_template(),
            $actionbar->export_for_template($OUTPUT)
        );
    }

    $form->set_data([
        "fromdate" => $timefrom,
        "todate" => $timeto,
        "company" => $company,
        "rut" => $rut,
        "rut_company" => $rut_company,
        "adherente" => $adherente
    ]);

    $form->display();
    echo $OUTPUT->initials_bar($firstletter, 'firstinitial', get_string('firstname'), $table->request[4], $table->baseurl);
    echo $OUTPUT->initials_bar($lastletter, 'lastinitial', get_string('lastname'), $table->request[5], $table->baseurl);
}

$table->out(50, true);

if (empty($download)) {
    echo $OUTPUT->footer();
}
