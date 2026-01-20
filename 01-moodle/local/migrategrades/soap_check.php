<?php

require_once('../../config.php');

global $PAGE, $OUTPUT, $CFG;

require_once($CFG->libdir . '/adminlib.php');

require_login();
$context = context_system::instance();
require_capability('local/migrategrades:upload', $context);

$url = new moodle_url('/local/migrategrades/soap_check.php');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('soapchecktitle', 'local_migrategrades'));
$PAGE->set_heading(get_string('pluginname', 'local_migrategrades'));

$mform = new \local_migrategrades\soapcheck_form($url, null);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('soapchecktitle', 'local_migrategrades'));

echo $OUTPUT->single_button(new moodle_url('/local/migrategrades/index.php'), get_string('backtoupload', 'local_migrategrades'));

echo $OUTPUT->box_start('generalbox');
$mform->display();
echo $OUTPUT->box_end();

if ($mform->is_cancelled()) {
    echo $OUTPUT->footer();
    die;
}

if ($data = $mform->get_data()) {
    $username = trim((string)$data->username);

    echo $OUTPUT->box_start('generalbox');
    try {
        $client = new \local_migrategrades\soap_personas_client();
        $resp = $client->fetch_persona_by_identificador($username);

        $rows = array(
            array('error', (string)($resp['error'] ?? '')),
            array('mensaje', (string)($resp['mensaje'] ?? '')),
        );

        $empresa = $resp['empresa'] ?? null;
        if (is_array($empresa)) {
            $rows[] = array('empresa.rut', (string)($empresa['rut'] ?? ''));
            $rows[] = array('empresa.dv', (string)($empresa['dv'] ?? ''));
            $rows[] = array('empresa.razonSocial', (string)($empresa['razonSocial'] ?? ''));
            $rows[] = array('empresa.contrato', (string)($empresa['contrato'] ?? ''));
            $rows[] = array('empresa.activo', (string)($empresa['activo'] ?? ''));
        }

        $t = new html_table();
        $t->head = array('campo', 'valor');
        $t->data = $rows;
        echo html_writer::table($t);

        if (!empty($resp['rawxml'])) {
            echo html_writer::tag('h3', get_string('soapcheck_rawxml', 'local_migrategrades'));
            echo html_writer::tag('pre', s((string)$resp['rawxml']), array('style' => 'white-space: pre-wrap;'));
        }
    } catch (Throwable $e) {
        echo $OUTPUT->notification($e->getMessage(), 'notifyproblem');
    }
    echo $OUTPUT->box_end();
}

echo $OUTPUT->footer();
