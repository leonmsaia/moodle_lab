<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @package    local_eabcprogramas
 * @copyright  2020 e-abclearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_eabcprogramas\utils;

global $PAGE, $USER;

require(__DIR__ . '/../../config.php');
require_once('diploma_form.php');

$PAGE->set_context(context_system::instance());
$url = new moodle_url('/local/eabcprogramas/diploma.php');
$PAGE->set_url($url);

$PAGE->set_title(get_string('diploma', 'local_eabcprogramas'));

$url_diplomas = new moodle_url('/local/eabcprogramas/diplomas.php');
$PAGE->navbar->add(get_string('programs', 'local_eabcprogramas'), $url_diplomas);

require_login();

echo $OUTPUT->header();

if (is_siteadmin()) {
    $mform = new diploma_form();

    if ($fromform = $mform->get_data()) {

        $mensaje = '';
        if ($fromform->action == 'crear') {
            $diploma = new stdClass();

            $diploma->img = $fromform->img;
            $diploma->status = utils::activo();
            $diploma->fromdate = time();
            $diploma->todate = 0;
            $diploma->timecreated = time();
            $diploma->timemodified = time();

            $filename = $mform->get_new_filename('img');

            // $tempdir = 'diplomas/' . $fromform->img;
            // make_temp_directory($tempdir);
            // $file = $CFG->tempdir . '/' . $tempdir . '/' . $filename;
            // $status = $mform->save_file('img', $file);

            $storedfile = $mform->save_stored_file(
                'img',
                \context_system::instance()->id,
                'local_eabcprogramas',
                'local_eabcprogramas_img',
                $fromform->img,
                '/',
                $filename,
                false,
                $USER->id
            );

            if ($storedfile) {
                // $diploma->urltemp = $file;

                $fileurl = \moodle_url::make_pluginfile_url(
                    $storedfile->get_contextid(),
                    $storedfile->get_component(),
                    $storedfile->get_filearea(),
                    $fromform->img,
                    $storedfile->get_filepath(),
                    $storedfile->get_filename()
                );
                $urlfile = $fileurl->out();

                $diploma->urlfile = $urlfile;
                $anterior = $DB->get_record('local_diplomas', ['status' => 1]);
                if ($anterior) {
                    $anterior->status = utils::inactivo();
                    $anterior->todate = time();
                    $anterior->timemodified = time();
                    $DB->update_record('local_diplomas', $anterior);
                }

                $DB->insert_record('local_diplomas', $diploma);
                $mensaje = 'Modelo de diploma guardado con éxito';
                echo $OUTPUT->notification($mensaje, 'notifysuccess');
            } else {
                $mensaje = 'No se pudo guardar el modelo de diploma';
                echo $OUTPUT->notification($mensaje, 'notifyerror');
            }
        }
        if ($fromform->action == 'inactivar') {
            $diploma = $DB->get_record('local_diplomas', ['id' => $fromform->id]);
            $diploma->status = utils::inactivo();
            $diploma->todate = time();
            $diploma->timemodified = time();
            $DB->update_record('local_diplomas', $diploma);
            $mensaje = "Modelo de diploma inactivado!";
            echo $OUTPUT->notification($mensaje, 'notifysuccess');
        }
        if ($fromform->action == 'eliminar') {
            $diploma = $DB->get_record('local_diplomas', ['id' => $fromform->id]);
            $DB->delete_records('local_diplomas', ['id' => $fromform->id]);
            $mensaje = "Modelo de diploma eliminado!";
            echo $OUTPUT->notification($mensaje, 'notifysuccess');
        }

        // $event = \core\event\diploma_created::create(array(
        //     'objectid' => $diploma->id,
        //     'other' => array(
        //         'inicio' => $diploma->fromdate,
        //         'fin' => $diploma->todate
        //     )
        // ));

        // $event->trigger();
        echo $OUTPUT->continue_button($url_diplomas);
    } else {
        $mform->display();
    }
}

echo $OUTPUT->footer();
