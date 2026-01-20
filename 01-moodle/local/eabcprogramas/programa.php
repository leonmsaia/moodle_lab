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
require_once('programa_form.php');

$PAGE->set_context(context_system::instance());
$url = new moodle_url('/local/eabcprogramas/programa.php');
$PAGE->set_url($url);

$PAGE->set_title(get_string('program', 'local_eabcprogramas'));
// $PAGE->set_heading(get_string('new_program', 'local_eabcprogramas'));

$url_programas = new moodle_url('/local/eabcprogramas/programs.php');
$PAGE->navbar->add(get_string('programs', 'local_eabcprogramas'), $url_programas);
//$PAGE->navbar->add(get_string('new_program', 'local_eabcprogramas'), $url);

require_login();

echo $OUTPUT->header();

if (has_capability('local/eabcprogramas:view', context_system::instance())) {
    $mform = new programa_form();

    if ($mform->is_cancelled()) {
        redirect($url_programas);
    } else if ($fromform = $mform->get_data()) {
        $mensaje = '';
        if ($fromform->action == 'crear') {
            $program = new stdClass();

            $program->description = $fromform->description;
            $program->grupo = $fromform->grupo;
            $program->codigo = $fromform->codigo;
            $program->version = $fromform->version;
            $program->caducidad = $fromform->caducidad;
            $program->horas = $fromform->horas;
            $program->responsable = $fromform->responsable;
            $program->timemodified = time();
            $program->fromdate = 0;
            $program->todate = 0;
            $program->status = utils::enpreparacion();
            $program->cursos = '';
            $program->timecreated = time();
            $program->objetivos = $fromform->objetivos['text'];

            $DB->insert_record('local_eabcprogramas', $program);
            $mensaje = "Programa guardado con éxito";
        }
        if ($fromform->action == 'cursos') {
            $program = $DB->get_record('local_eabcprogramas', ['id' => $fromform->id]);
            if (isset($fromform->cursos)) {
                $idcursos = utils::get_filters($fromform->cursos);
                $program->cursos = $idcursos;
            }
            $DB->update_record('local_eabcprogramas', $program);
            $mensaje = "Cursos guardados con éxito";
        }
        if ($fromform->action == 'activar') {
            $program = $DB->get_record('local_eabcprogramas', ['id' => $fromform->id]);
            if ((int) $program->status == utils::enpreparacion()) {
                $program->fromdate = time(); //lanzamiento
            }
            if ((int) $program->status == utils::inactivo()) {
                if ($program->fromdate == 0) {
                    $program->fromdate = time();
                }

                $program->todate = 0; //finalización
            }
            $program->status = utils::activo();

            $DB->update_record('local_eabcprogramas', $program);
            $mensaje = "Programa activado con éxito";
        }
        if ($fromform->action == 'clonar') {
            $program_old = $DB->get_record('local_eabcprogramas', ['id' => $fromform->id]);

            $program = new stdClass();
            $program->description = $fromform->description;
            $program->grupo = $fromform->grupo;
            $program->codigo = utils::struuid(false);
            $program->version = $fromform->version;
            $program->caducidad = $fromform->caducidad;
            $program->horas = $fromform->horas;
            $program->responsable = $fromform->responsable;
            $program->fromdate = 0;
            $program->todate = 0;
            $program->status = utils::enpreparacion();
            $program->cursos = $program_old->cursos;
            $program->timemodified = time();
            $program->timecreated = time();
            $program->objetivos = $fromform->objetivos['text'];
            $DB->insert_record('local_eabcprogramas', $program);
            $mensaje = "Programa duplicado con éxito";
        }
        if ($fromform->action == 'inactivar') {
            $program = $DB->get_record('local_eabcprogramas', ['id' => $fromform->id]);
            $program->status = utils::inactivo();
            $program->todate = time();
            $DB->update_record('local_eabcprogramas', $program);
            $mensaje = "Programa inactivado con éxito";
        }
        if ($fromform->action == 'editar') {
            $program = $DB->get_record('local_eabcprogramas', ['id' => $fromform->id]);

            $program->description = $fromform->description;
            $program->version = $fromform->version;
            $program->caducidad = $fromform->caducidad;
            $program->horas = $fromform->horas;
            $program->responsable = $fromform->responsable;
            $program->timemodified = time();
            $program->objetivos = $fromform->objetivos['text'];
            $DB->update_record('local_eabcprogramas', $program);
            $mensaje = "Programa modificado con éxito";
        }

        // $event = \core\event\program_created::create(array(
        //     'objectid' => $program->id,
        //     'other' => array(
        //         'shortname' => $program->name,
        //         'fullname' => $program->group
        //     )
        // ));

        // $event->trigger();
        echo $OUTPUT->notification($mensaje, 'notifysuccess');
        echo $OUTPUT->continue_button($url_programas);
    } else {
        $mform->display();
    }
}

echo $OUTPUT->footer();
