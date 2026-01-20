<?php

require_once('../../config.php');

global $PAGE, $OUTPUT, $CFG, $USER;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

require_login();
$context = context_system::instance();
require_capability('local/migrategrades:upload', $context);

$url = new moodle_url('/local/migrategrades/company_assign.php');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('companyassigntitle', 'local_migrategrades'));
$PAGE->set_heading(get_string('pluginname', 'local_migrategrades'));

$mform = new \local_migrategrades\uploadrutcsv_form($url, null);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('companyassigntitle', 'local_migrategrades'));

echo $OUTPUT->single_button(new moodle_url('/local/migrategrades/index.php'), get_string('backtoupload', 'local_migrategrades'));

if ($mform->is_cancelled()) {
    echo $OUTPUT->footer();
    die;
}

if ($formdata = $mform->get_data()) {
    $iid = csv_import_reader::get_new_iid('migrategrades_company');
    $cir = new csv_import_reader($iid, 'migrategrades_company');

    $content = $mform->get_file_content('userfile');
    $readcount = $cir->load_csv_content(
        $content,
        $formdata->encoding,
        $formdata->delimiter_name,
        '\\local_migrategrades\\rut_csv_utils::validate_columns'
    );
    unset($content);

    if ($readcount === false) {
        echo $OUTPUT->notification($cir->get_error(), 'notifyproblem');
        echo $OUTPUT->footer();
        die;
    }
    if ($readcount == 0) {
        echo $OUTPUT->notification(get_string('fileempty', 'local_migrategrades'), 'notifyproblem');
        echo $OUTPUT->footer();
        die;
    }

    $cir->init();
    $columns = $cir->get_columns();

    $processed = 0;
    $linked = 0;
    $skipped = 0;
    $errors = 0;

    $rowsok = array();
    $rowserr = array();

    $client = new \local_migrategrades\soap_personas_client();

    $linenum = 1; // header.
    while ($line = $cir->next()) {
        $linenum++;
        $processed++;

        $mapped = \local_migrategrades\rut_csv_utils::map_row($line, $columns);
        $username = trim($mapped['username']);

        try {
            if ($username === '') {
                throw new moodle_exception('invalidcsv', 'local_migrategrades', '', null, 'username vacío');
            }

            $user = $DB->get_record('user', array('username' => $username), 'id,username', IGNORE_MISSING);
            if (!$user) {
                $skipped++;
                $rowsok[] = array($username, get_string('companyassign_user_missing', 'local_migrategrades'));

                \local_migrategrades\company_logger::log_row(array(
                    'timecreated' => time(),
                    'actorid' => (int)$USER->id,
                    'username' => $username,
                    'status' => 'skipped',
                    'message' => get_string('companyassign_user_missing', 'local_migrategrades'),
                ));
                continue;
            }

            $resp = $client->fetch_persona_by_identificador($username);
            if (!empty($resp['error']) && (string)$resp['error'] !== '0') {
                $skipped++;
                $msg = get_string('companyassign_soap_error', 'local_migrategrades', (string)($resp['mensaje'] ?? 'Error'));
                $rowsok[] = array($username, $msg);

                \local_migrategrades\company_logger::log_row(array(
                    'timecreated' => time(),
                    'actorid' => (int)$USER->id,
                    'username' => $username,
                    'userid' => (int)$user->id,
                    'status' => 'skipped',
                    'message' => $msg,
                    'soap_error' => (string)($resp['error'] ?? ''),
                    'soap_mensaje' => (string)($resp['mensaje'] ?? ''),
                ));
                continue;
            }

            $empresa = $resp['empresa'] ?? null;
            if (!$empresa || empty($empresa['rut']) || empty($empresa['dv'])) {
                $skipped++;
                $msg = get_string('companyassign_no_company_in_soap', 'local_migrategrades');
                $rowsok[] = array($username, $msg);

                \local_migrategrades\company_logger::log_row(array(
                    'timecreated' => time(),
                    'actorid' => (int)$USER->id,
                    'username' => $username,
                    'userid' => (int)$user->id,
                    'status' => 'skipped',
                    'message' => $msg,
                    'soap_error' => (string)($resp['error'] ?? ''),
                    'soap_mensaje' => (string)($resp['mensaje'] ?? ''),
                ));
                continue;
            }

            $empresarut = trim((string)$empresa['rut']) . '-' . strtoupper(trim((string)$empresa['dv']));
            $razonsocial = (string)($empresa['razonSocial'] ?? '');
            $contrato = (string)($empresa['contrato'] ?? '');

            $company = $DB->get_record('company', array('rut' => $empresarut), 'id,rut,razonsocial,contrato', IGNORE_MISSING);
            if (!$company) {
                $skipped++;
                $msg = get_string('companyassign_company_missing', 'local_migrategrades', $empresarut);
                $rowsok[] = array($username, $msg);

                \local_migrategrades\company_logger::log_row(array(
                    'timecreated' => time(),
                    'actorid' => (int)$USER->id,
                    'username' => $username,
                    'userid' => (int)$user->id,
                    'empresarut' => $empresarut,
                    'status' => 'skipped',
                    'message' => $msg,
                    'soap_error' => (string)($resp['error'] ?? ''),
                    'soap_mensaje' => (string)($resp['mensaje'] ?? ''),
                ));
                continue;
            }

            // Assign company: if already exists, update the existing assignment to point to this company.
            $status = 'exists';
            $msg = '';

            $existingtarget = $DB->get_record('company_users', array('userid' => (int)$user->id, 'companyid' => (int)$company->id), 'id,companyid,userid,departmentid,managertype', IGNORE_MISSING);
            if ($existingtarget) {
                $status = 'exists';
                $msg = get_string('companyassign_linked', 'local_migrategrades', $empresarut);
            } else {
                $existingdept0 = $DB->get_record('company_users', array('userid' => (int)$user->id, 'departmentid' => 0), 'id,companyid,userid,departmentid,managertype', IGNORE_MISSING);
                if (!$existingdept0) {
                    $existingdept0 = $DB->get_record('company_users', array('userid' => (int)$user->id), 'id,companyid,userid,departmentid,managertype', IGNORE_MISSING);
                }

                if ($existingdept0) {
                    $upd = new stdClass();
                    $upd->id = (int)$existingdept0->id;
                    $upd->companyid = (int)$company->id;
                    $upd->departmentid = 0;
                    $upd->managertype = 0;
                    $DB->update_record('company_users', $upd);
                    $status = 'updated';
                    $msg = get_string('companyassign_linked', 'local_migrategrades', $empresarut);
                } else {
                    $rec = new stdClass();
                    $rec->userid = (int)$user->id;
                    $rec->companyid = (int)$company->id;
                    $rec->departmentid = 0;
                    $rec->managertype = 0;
                    $DB->insert_record('company_users', $rec);
                    $status = 'inserted';
                    $msg = get_string('companyassign_linked', 'local_migrategrades', $empresarut);
                }
            }

            // Save custom profile fields.
            $fields = array(
                'empresarut' => $empresarut,
                'empresarazonsocial' => $razonsocial !== '' ? $razonsocial : ($company->razonsocial ?? ''),
                'empresacontrato' => $contrato !== '' ? $contrato : ($company->contrato ?? ''),
            );
            profile_save_custom_fields((int)$user->id, $fields);

            $linked++;
            $rowsok[] = array($username, $msg);

            \local_migrategrades\company_logger::log_row(array(
                'timecreated' => time(),
                'actorid' => (int)$USER->id,
                'username' => $username,
                'userid' => (int)$user->id,
                'empresarut' => $empresarut,
                'companyid' => (int)$company->id,
                'status' => $status,
                'message' => $msg,
                'soap_error' => (string)($resp['error'] ?? ''),
                'soap_mensaje' => (string)($resp['mensaje'] ?? ''),
            ));
        } catch (Throwable $e) {
            $errors++;
            $rowserr[] = array($linenum, $username, $e->getMessage());

            \local_migrategrades\company_logger::log_row(array(
                'timecreated' => time(),
                'actorid' => (int)$USER->id,
                'username' => $username,
                'status' => 'error',
                'message' => $e->getMessage(),
            ));
        }
    }

    $cir->close();
    $cir->cleanup(true);

    echo $OUTPUT->box_start('generalbox');
    echo html_writer::tag('h3', get_string('results', 'local_migrategrades'));
    echo html_writer::tag('p',
        get_string('processed', 'local_migrategrades') . ': ' . $processed . '<br>' .
        get_string('companyassign_linked_count', 'local_migrategrades') . ': ' . $linked . '<br>' .
        get_string('skipped', 'local_migrategrades') . ': ' . $skipped . '<br>' .
        get_string('errors', 'local_migrategrades') . ': ' . $errors
    );

    if (!empty($rowsok)) {
        $t = new html_table();
        $t->head = array('username', 'resultado');
        $t->data = $rowsok;
        echo html_writer::table($t);
    }

    if (!empty($rowserr)) {
        $t2 = new html_table();
        $t2->head = array('fila', 'username', 'error');
        $t2->data = $rowserr;
        echo html_writer::table($t2);
    }

    echo $OUTPUT->box_end();
    echo $OUTPUT->continue_button($url);
    echo $OUTPUT->footer();
    die;
}

$mform->display();
echo $OUTPUT->footer();
