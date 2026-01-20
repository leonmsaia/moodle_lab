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
require_once('certificado_form.php');

$PAGE->set_context(context_system::instance());
$url = new moodle_url('/local/eabcprogramas/certificado.php');
$PAGE->set_url($url);

$PAGE->set_title(get_string('certificado', 'local_eabcprogramas'));

$url_certificados = new moodle_url('/local/eabcprogramas/certificados.php');
$PAGE->navbar->add(get_string('programs', 'local_eabcprogramas'), $url_certificados);

require_login();

echo $OUTPUT->header();

if (is_siteadmin()) {
    $mform = new certificado_form();

    if ($fromform = $mform->get_data()) {

        $mensaje = '';
        if ($fromform->action == 'crear') {
            $certificado = new stdClass();

            $certificado->img = $fromform->img;
            $certificado->status = 1;
            $certificado->fromdate = time();
            $certificado->todate = 0;
            $certificado->timecreated = time();
            $certificado->timemodified = time();

            $filename = $mform->get_new_filename('img');

            // $tempdir = 'certificados/' . $fromform->img;
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
                // $certificado->urltemp = $file;

                $fileurl = \moodle_url::make_pluginfile_url(
                    $storedfile->get_contextid(),
                    $storedfile->get_component(),
                    $storedfile->get_filearea(),
                    $fromform->img,
                    $storedfile->get_filepath(),
                    $storedfile->get_filename()
                );
                $urlfile = $fileurl->out();
                $certificado->urlfile = $urlfile;

                $anterior = $DB->get_record('local_certificados', ['status' => 1]);
                if ($anterior) {
                    $anterior->status = utils::inactivo();
                    $anterior->todate = time();
                    $anterior->timemodified = time();
                    $DB->update_record('local_certificados', $anterior);
                }

                $DB->insert_record('local_certificados', $certificado);

                $mensaje = 'Modelo de certificado guardado con éxito';
                echo $OUTPUT->notification($mensaje, 'notifysuccess');
            } else {
                $mensaje = 'No se pudo guardar el modelo de certificado';
                echo $OUTPUT->notification($mensaje, 'notifyerror');
            }
        }
        if ($fromform->action == 'inactivar') {
            $certificado = $DB->get_record('local_certificados', ['id' => $fromform->id]);
            $certificado->status = utils::inactivo();
            $certificado->todate = time();
            $certificado->timemodified = time();
            $DB->update_record('local_certificados', $certificado);
            $mensaje = "Modelo de certificado inactivado";
        }
        if ($fromform->action == 'eliminar') {
            $DB->delete_records('local_certificados', ['id' => $fromform->id]);
            $mensaje = "Modelo de certificado eliminado!";
            echo $OUTPUT->notification($mensaje, 'notifysuccess');
        }
        echo $OUTPUT->continue_button($url_certificados);
    } else {
        $mform->display();
    }
}

echo $OUTPUT->footer();
