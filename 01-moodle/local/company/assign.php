<?php
require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/company/classes/metodos_comunes.php');

require_login();

$companyid = required_param('companyid', PARAM_INT);
$PAGE->set_url(new \moodle_url('/local/company/assign.php', array('companyid' => $companyid)));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('assignusers', 'local_company'));
// styles
$PAGE->add_body_class('local-company');
$PAGE->requires->css(new \moodle_url('/local/company/styles.css'));

echo $OUTPUT->header();

// Fetch company name for display
$companies = \local_company\metodos_comunes::get_all_companies();
$companyname = '';
$companyshort = '';
$companycontrato = '';
if (isset($companies[$companyid])) {
    $company = $companies[$companyid];
    $companyname = format_string($company->name);
    $companyshort = isset($company->shortname) ? format_string($company->shortname) : '';
    $companycontrato = isset($company->contrato) ? format_string($company->contrato) : '';
}

// Build preserved params for back link if they exist
$backparams = array();
if (!empty($_GET['search'])) { $backparams['search'] = $_GET['search']; }
if (isset($_GET['page'])) { $backparams['page'] = (int)$_GET['page']; }
if (isset($_GET['perpage'])) { $backparams['perpage'] = (int)$_GET['perpage']; }
$backurl = new \moodle_url('/local/company/index.php', $backparams);

// Breadcrumb: Home > Companies > Assign users: Company Name (with shortname/contrato)
echo html_writer::start_tag('div', array('class' => 'local-company-breadcrumb', 'style' => 'margin-bottom: 40px;'));
echo html_writer::link(new \moodle_url('/'), get_string('home')) . ' &raquo; ';
echo html_writer::link($backurl, get_string('managecompanies', 'local_company')) . ' &raquo; ';
// main title with shortname/contrato
$titleparts = array();
$titleparts[] = get_string('assignusers', 'local_company');
if ($companyname) { $titleparts[] = s($companyname); }
if ($companyshort) { $titleparts[] = '(' . s($companyshort) . ')'; }
if ($companycontrato) { $titleparts[] = '[' . s($companycontrato) . ']'; }
echo html_writer::tag('strong', implode(' ', $titleparts));
echo html_writer::end_tag('div');

// Link to CSV upload page for bulk add
echo html_writer::start_tag('div', array('style' => 'margin-bottom: 20px;'));
echo html_writer::link(new \moodle_url('/local/company/upload_csv.php', array('companyid' => $companyid)), 'Subir CSV y asignar usuarios a esta empresa');
echo html_writer::end_tag('div');

// Handle POST assign/unassign
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // protect against CSRF
    require_sesskey();

    // bulk assign
    if (!empty($_POST['assign_userids']) && is_array($_POST['assign_userids'])) {
        $userids = array_map('intval', $_POST['assign_userids']);
        $count = \local_company\metodos_comunes::assign_users_to_company($companyid, $userids);
        echo $OUTPUT->notification(get_string('usersassigned', 'local_company', $count), 'notifysuccess');
    }

    // bulk unassign
    if (!empty($_POST['unassign_userids']) && is_array($_POST['unassign_userids'])) {
        $userids = array_map('intval', $_POST['unassign_userids']);
        $count = \local_company\metodos_comunes::unassign_users_from_company($companyid, $userids);
        echo $OUTPUT->notification(get_string('usersunassigned', 'local_company', $count), 'notifysuccess');
    }

    // single unassign (from per-row action)
    if (!empty($_POST['unassign_single'])) {
        $userid = intval($_POST['unassign_single']);
        $count = \local_company\metodos_comunes::unassign_users_from_company($companyid, array($userid));
        echo $OUTPUT->notification(get_string('usersunassigned', 'local_company', $count), 'notifysuccess');
    }
}

// Unassigned users
$search = optional_param('search', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 10, PARAM_INT);

$result = \local_company\metodos_comunes::get_unassigned_users($search, $page, $perpage);
$users = $result['list'];
$total = $result['total'];

// Assigned users (separate paging/search params to avoid conflicts)
$asearch = optional_param('assigned_search', '', PARAM_TEXT);
$apage = optional_param('assigned_page', 0, PARAM_INT);
$aperpage = optional_param('assigned_perpage', 10, PARAM_INT);

$aresult = \local_company\metodos_comunes::get_assigned_users($companyid, $asearch, $apage, $aperpage);
$ausers = $aresult['list'];
$atotal = $aresult['total'];

echo html_writer::start_tag('form', array('method' => 'get', 'action' => new \moodle_url('/local/company/assign.php')));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'companyid', 'value' => $companyid));
echo html_writer::empty_tag('input', array('type' => 'text', 'name' => 'search', 'value' => s($search), 'placeholder' => get_string('search', 'local_company')));
echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('searchbutton', 'local_company')));
echo html_writer::end_tag('form');

if ($users) {
    echo '<form method="post">';
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    echo html_writer::start_tag('table', array('class' => 'users'));
    // added select-all checkbox in header for bulk assign
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'select_all_unassigned')) . ' ' . get_string('select'));
    echo html_writer::tag('th', get_string('fullname'));
    echo html_writer::tag('th', get_string('username'));
    echo html_writer::tag('th', get_string('email'));
    echo html_writer::end_tag('tr');
    foreach ($users as $u) {
        $fullname = fullname($u);
    // checkbox for assignment (bulk)
    echo html_writer::tag('tr', html_writer::tag('td', html_writer::empty_tag('input', array('type' => 'checkbox', 'class' => 'assign_checkbox', 'name' => 'assign_userids[]', 'value' => $u->id))) . html_writer::tag('td', s($fullname)) . html_writer::tag('td', s($u->username)) . html_writer::tag('td', s($u->email)));
    }
    echo html_writer::end_tag('table');
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'companyid', 'value' => $companyid));
    echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('assignselected', 'local_company')));
    echo '</form>';

    // Preserve assigned users filters when paging unassigned list
    $pagingurl = new \moodle_url('/local/company/assign.php', array(
        'companyid' => $companyid,
        'search' => $search,
        'perpage' => $perpage,
        'assigned_search' => $asearch,
        'assigned_perpage' => $aperpage
    ));
    // standard page/perpage params for the unassigned list
    echo $OUTPUT->paging_bar($total, $page, $perpage, $pagingurl, 'page', 'perpage');
} else {
    echo html_writer::tag('p', get_string('nousersunassigned', 'local_company'));
}

// Assigned users section
echo "<br><br>";
echo html_writer::tag('h3', get_string('assignedusers', 'local_company'));
// Assigned users search (GET)
echo html_writer::start_tag('form', array('method' => 'get', 'action' => new \moodle_url('/local/company/assign.php')));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'companyid', 'value' => $companyid));
echo html_writer::empty_tag('input', array('type' => 'text', 'name' => 'assigned_search', 'value' => s($asearch), 'placeholder' => get_string('search', 'local_company')));
echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('searchbutton', 'local_company')));
echo html_writer::end_tag('form');

if ($ausers) {
    // Post form for bulk unassign
    echo html_writer::start_tag('form', array('method' => 'post'));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'companyid', 'value' => $companyid));
    echo html_writer::start_tag('table', array('class' => 'users'));
    // added select-all checkbox in header for bulk unassign
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', html_writer::empty_tag('input', array('type' => 'checkbox', 'id' => 'select_all_assigned')) . ' ' . get_string('select'));
    echo html_writer::tag('th', get_string('fullname'));
    echo html_writer::tag('th', get_string('username'));
    echo html_writer::tag('th', get_string('email'));
    echo html_writer::tag('th', get_string('actions'));
    echo html_writer::end_tag('tr');
    foreach ($ausers as $au) {
        $fullname = fullname($au);
        // checkbox for bulk unassign
        $removebutton = html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'unassign_single', 'value' => get_string('remove'), 'class' => 'btn-assign', 'formnovalidate' => 'formnovalidate'));

    echo html_writer::tag('tr', html_writer::tag('td', html_writer::empty_tag('input', array('type' => 'checkbox', 'class' => 'unassign_checkbox', 'name' => 'unassign_userids[]', 'value' => $au->id))) . html_writer::tag('td', s($fullname)) . html_writer::tag('td', s($au->username)) . html_writer::tag('td', s($au->email)) . html_writer::tag('td', $removebutton));
    }
    echo html_writer::end_tag('table');
    echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('unassignselected', 'local_company')));
    echo html_writer::end_tag('form');

    // Preserve unassigned users filters when paging assigned list
    $apagingurl = new \moodle_url('/local/company/assign.php', array(
        'companyid' => $companyid,
        'assigned_search' => $asearch,
        'assigned_perpage' => $aperpage,
        'search' => $search,
        'perpage' => $perpage
    ));
    // use distinct param names so the assigned paginator doesn't conflict with the unassigned one
    echo $OUTPUT->paging_bar($atotal, $apage, $aperpage, $apagingurl, 'assigned_page', 'assigned_perpage');
} else {
    echo html_writer::tag('p', get_string('nousersassigned', 'local_company'));
}

echo $OUTPUT->footer();

// Inline JS to handle select all checkboxes for both lists
echo '<script>';
echo "document.addEventListener('DOMContentLoaded', function(){\n";
echo "  var sa = document.getElementById('select_all_unassigned');\n";
echo "  if(sa){ sa.addEventListener('change', function(){ var boxes = document.querySelectorAll('.assign_checkbox'); for(var i=0;i<boxes.length;i++){ boxes[i].checked = sa.checked; } }); }\n";
echo "  var sb = document.getElementById('select_all_assigned');\n";
echo "  if(sb){ sb.addEventListener('change', function(){ var boxes = document.querySelectorAll('.unassign_checkbox'); for(var i=0;i<boxes.length;i++){ boxes[i].checked = sb.checked; } }); }\n";
echo "});";
echo '</script>';
