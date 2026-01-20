<?php

require_once('../../config.php');

global $PAGE, $OUTPUT, $CFG, $USER;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');

require_login();
$context = context_system::instance();
require_capability('local/migrategrades:upload', $context);

$url = new moodle_url('/local/migrategrades/index.php');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'local_migrategrades'));
$PAGE->set_heading(get_string('pluginname', 'local_migrategrades'));

$mform = new \local_migrategrades\uploadcsv_form($url, null);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadtitle', 'local_migrategrades'));

if ($mform->is_cancelled()) {
    echo $OUTPUT->continue_button(new moodle_url('/admin/settings.php', array('section' => 'local_migrategrades')));
    echo $OUTPUT->footer();
    die;
}

if ($formdata = $mform->get_data()) {
    $iid = csv_import_reader::get_new_iid('migrategrades');
    $cir = new csv_import_reader($iid, 'migrategrades');

    $content = $mform->get_file_content('userfile');
    $readcount = $cir->load_csv_content(
        $content,
        $formdata->encoding,
        $formdata->delimiter_name,
        '\\local_migrategrades\\csv_utils::validate_columns'
    );
    unset($content);

    if ($readcount === false) {
        echo $OUTPUT->notification($cir->get_error(), 'notifyproblem');
        echo $OUTPUT->footer();
        die;
    }
    if ($readcount == 0) {
        echo $OUTPUT->notification('Archivo vacío', 'notifyproblem');
        echo $OUTPUT->footer();
        die;
    }

    $cir->init();
    $columns = $cir->get_columns();

    $processed = 0;
    $updated = 0;
    $skipped = 0;
    $errors = 0;

    $rowsok = array();
    $rowserr = array();

    $olddb = new \local_migrategrades\old_moodle_db();
    $migrator = new \local_migrategrades\migrator($olddb);

    $linenum = 1; // header line.
    while ($line = $cir->next()) {
        $linenum++;
        $processed++;

        $mapped = \local_migrategrades\csv_utils::map_row($line, $columns);

        try {
            $res = $migrator->migrate_one($mapped['username'], $mapped['shortname'], (int)$USER->id);
            if ($res['status'] === 'updated') {
                $updated++;
                $rowsok[] = array($mapped['username'], $mapped['shortname'], $res['message'], $res['old'] ?? '', $res['new'] ?? '');
            } else if ($res['status'] === 'skipped') {
                $skipped++;
                $rowsok[] = array($mapped['username'], $mapped['shortname'], $res['message'], $res['old'] ?? '', $res['new'] ?? '');
            } else {
                $errors++;
                $rowserr[] = array($linenum, $mapped['username'], $mapped['shortname'], $res['message'] ?? 'Error');
            }

            \local_migrategrades\logger::log_row(array(
                'timecreated' => time(),
                'actorid' => (int)$USER->id,
                'username' => $mapped['username'],
                'shortname' => $mapped['shortname'],
                'newuserid' => $res['newuserid'] ?? null,
                'newcourseid' => $res['newcourseid'] ?? null,
                'status' => $res['status'] ?? 'error',
                'winner' => $res['winner'] ?? null,
                'oldgrade' => $res['old'] ?? null,
                'newgrade' => $res['new'] ?? null,
                'applied_timeenrolled' => $res['applied_timeenrolled'] ?? null,
                'applied_timecompleted' => $res['applied_timecompleted'] ?? null,
                'message' => $res['message'] ?? null,
            ));
        } catch (Exception $ex) {
            $errors++;
            $rowserr[] = array($linenum, $mapped['username'], $mapped['shortname'], $ex->getMessage());

            \local_migrategrades\logger::log_row(array(
                'timecreated' => time(),
                'actorid' => (int)$USER->id,
                'username' => $mapped['username'],
                'shortname' => $mapped['shortname'],
                'status' => 'error',
                'winner' => 'none',
                'message' => $ex->getMessage(),
            ));
        }
    }

    $cir->close();
    $cir->cleanup(true);
    $olddb->close();

    echo $OUTPUT->box_start('generalbox');
    echo html_writer::tag('h3', get_string('results', 'local_migrategrades'));
    echo html_writer::tag('p',
        get_string('processed', 'local_migrategrades') . ': ' . $processed . '<br>' .
        get_string('updated', 'local_migrategrades') . ': ' . $updated . '<br>' .
        get_string('skipped', 'local_migrategrades') . ': ' . $skipped . '<br>' .
        get_string('errors', 'local_migrategrades') . ': ' . $errors
    );

    if (!empty($rowsok)) {
        $t = new html_table();
        $t->head = array('username', 'shortname', 'resultado', 'nota viejo', 'nota nuevo');
        $t->data = $rowsok;
        echo html_writer::table($t);
    }

    if (!empty($rowserr)) {
        $t2 = new html_table();
        $t2->head = array('fila', 'username', 'shortname', 'error');
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
