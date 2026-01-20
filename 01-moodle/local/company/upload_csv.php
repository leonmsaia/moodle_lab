<?php
require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/company/classes/metodos_comunes.php');

require_login();

$companyid = required_param('companyid', PARAM_INT);
$PAGE->set_url(new \moodle_url('/local/company/upload_csv.php', array('companyid' => $companyid)));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title('Subir CSV - Asignar usuarios');
$PAGE->add_body_class('local-company');
$PAGE->requires->css(new \moodle_url('/local/company/styles.css'));

echo $OUTPUT->header();

// Fetch company name for display
$companies = \local_company\metodos_comunes::get_all_companies();
$companyname = '';
if (isset($companies[$companyid])) {
    $companyname = format_string($companies[$companyid]->name);
}

// Build preserved params for back link if they exist
$backparams = array();
if (!empty($_GET['search'])) { $backparams['search'] = $_GET['search']; }
if (isset($_GET['page'])) { $backparams['page'] = (int)$_GET['page']; }
if (isset($_GET['perpage'])) { $backparams['perpage'] = (int)$_GET['perpage']; }
$backurl = new \moodle_url('/local/company/index.php', $backparams);

// Breadcrumb: Home > Companies > Assign users: Company Name > Subir CSV
echo html_writer::start_tag('div', array('class' => 'local-company-breadcrumb', 'style' => 'margin-bottom: 20px;'));
echo html_writer::link(new \moodle_url('/'), get_string('home')) . ' &raquo; ';
echo html_writer::link($backurl, get_string('managecompanies', 'local_company')) . ' &raquo; ';
echo html_writer::link(new \moodle_url('/local/company/assign.php', array('companyid' => $companyid)), get_string('assignusers', 'local_company')) . ' &raquo; ';
echo html_writer::tag('strong', 'Subir CSV');
echo html_writer::end_tag('div');

echo html_writer::tag('h2', 'Subir CSV y asignar usuarios a: ' . s($companyname));

// Handle POST upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    if (empty($_FILES['csvfile']) || $_FILES['csvfile']['error'] !== UPLOAD_ERR_OK) {
        echo $OUTPUT->notification('No se subió ningún archivo o hubo un error en la subida', 'notifyerror');
    } else {
        $tmpname = $_FILES['csvfile']['tmp_name'];
        $handle = fopen($tmpname, 'r');
        if ($handle === false) {
            echo $OUTPUT->notification('No se pudo leer el archivo subido', 'notifyerror');
        } else {
            global $DB;
            $validuserids = array();
            $notfound = array();
            $already = array();
            $line = 0;
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $line++;
                // Expect single column 'username'
                if (!isset($data[0])) {
                    continue;
                }
                // strip BOM and whitespace
                $username = preg_replace('/\x{FEFF}/u', '', $data[0]);
                $username = trim($username);
                if ($username === '') { continue; }
                // skip header row if present (single column named 'username')
                if ($line === 1 && strtolower($username) === 'username') { continue; }
                // find user
                $user = $DB->get_record('user', array('username' => $username), 'id', IGNORE_MISSING);
                if (!$user) {
                    $notfound[] = $username;
                    continue;
                }
                // check if user already has company
                if ($DB->record_exists('company_users', array('userid' => $user->id))) {
                    $already[] = $username;
                    continue;
                }
                $validuserids[] = $user->id;
            }
            fclose($handle);

            $assigned = 0;
            if (!empty($validuserids)) {
                $assigned = \local_company\metodos_comunes::assign_users_to_company($companyid, $validuserids);
            }

            // Present summary
            $msg = "Asignados: $assigned. Usuarios no encontrados: " . count($notfound) . ". Ya con empresa: " . count($already) . ".";
            echo $OUTPUT->notification($msg, 'notifysuccess');

            if (!empty($notfound)) {
                echo html_writer::tag('h4', 'Usuarios no encontrados');
                echo html_writer::alist($notfound);
            }
            if (!empty($already)) {
                echo html_writer::tag('h4', 'Usuarios que ya tenían empresa');
                echo html_writer::alist($already);
            }
        }
    }
}

// Upload form
echo html_writer::start_tag('form', array('method' => 'post', 'enctype' => 'multipart/form-data'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'companyid', 'value' => $companyid));
echo html_writer::tag('p', 'Archivo CSV: columna única "username" (sin headers)');
echo html_writer::empty_tag('input', array('type' => 'file', 'name' => 'csvfile', 'accept' => '.csv,text/csv'));
echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Subir y asignar'));
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
