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

defined('MOODLE_INTERNAL') || die();

include_once("$CFG->libdir/formslib.php");
class programa_form extends moodleform
{
    public function definition()
    {
        $action = optional_param('action', '', PARAM_TEXT);
        $id = optional_param('id', 0, PARAM_INT);
        global $USER, $DB;
        $mform = $this->_form;
        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_NOTAGS);

        if ($action == 'crear') {
            $mform->addElement('header', 'program', get_string('program', 'local_eabcprogramas'));

            $mform->addElement('text', 'description', get_string('description', 'local_eabcprogramas'));
            $mform->setType('description', PARAM_NOTAGS);
            $mform->addRule('description', get_string('required'), 'required');

            $mform->addElement('hidden', 'codigo', '');
            $mform->setType('codigo', PARAM_NOTAGS);
            $mform->setDefault('codigo', utils::struuid(false));

            $mform->addElement('hidden', 'grupo', '');
            $mform->setType('grupo', PARAM_NOTAGS);
            $mform->setDefault('grupo', utils::struuid(false));

            $mform->addElement('text', 'caducidad', get_string('meses', 'local_eabcprogramas'));
            $mform->setType('caducidad', PARAM_NOTAGS);
            $mform->addHelpButton('caducidad', 'caducidad', 'local_eabcprogramas');
            $mform->addRule('caducidad', get_string('numeric', 'local_eabcprogramas'), 'numeric');
            $mform->setDefault('caducidad', 36);

            $mform->addElement('text', 'horas', get_string('horas', 'local_eabcprogramas'));
            $mform->setType('horas', PARAM_NOTAGS);
            $mform->addHelpButton('horas', 'horas', 'local_eabcprogramas');
            $mform->addRule('horas', get_string('numeric', 'local_eabcprogramas'), 'numeric');

            $mform->addElement('text', 'responsable', get_string('responsable', 'local_eabcprogramas'));
            $mform->setType('responsable', PARAM_NOTAGS);
            $mform->addHelpButton('responsable', 'responsable', 'local_eabcprogramas');

            $mform->addElement('text', 'version', get_string('version', 'local_eabcprogramas'), ['placeholder' => 'Ej: 2020']);
            $mform->setType('version', PARAM_NOTAGS);
            $mform->addRule('version', get_string('required'), 'required', 'numeric');
            $mform->addHelpButton('version', 'version', 'local_eabcprogramas');

            $mform->addElement('editor', 'objetivos', get_string('objetivos', 'local_eabcprogramas'));
            $mform->setType('objetivos', PARAM_RAW);

            $this->add_action_buttons(true, 'Crear');
        }

        if ($id > 0) {
            $program = $DB->get_record('local_eabcprogramas', ['id' => $id]);

            $mform->addElement('hidden', 'id', $id);
            $mform->setType('id', PARAM_RAW);

            if ($action == 'cursos') {
                echo '
                <div class="alert alert-info">
                    <h4>Agregar o Eliminar cursos del programa <i>' . $program->description . ' ' . $program->version . '</h4>
                </div><hr>';
                $categoriesid = get_config('local_eabcprogramas', 'categoryid');
                $catlist = explode(',', $categoriesid);
                $array_cat = utils::get_list_valid_category($catlist);

                $cursos = utils::get_courses($array_cat);
                $mform->addElement('autocomplete', 'cursos', get_string('cursos', 'local_eabcprogramas'), $cursos);
                $mform->getElement('cursos')->setMultiple(true);
                $mform->setDefault('cursos', explode(',', $program->cursos));

                $this->add_action_buttons(true, 'Guardar cambios');
            }

            if ($action == 'activar') {
                echo '
                <div class="alert alert-info">
                    <h4>Activar el programa <i>' . $program->description . ' ' . $program->version . '</i> 
                    <br>Cursos: ' . utils::courses_names(explode(',', $program->cursos)) . '</h4>
                </div><hr>';
                $this->add_action_buttons(true, 'Activar este programa');
            }
            if ($action == 'clonar' || $action == 'editar') {

                $alert = 'Generar duplicado del programa';
                if ($action == 'editar') {
                    $alert = 'Modificar programa ';
                }
                echo '
                <div class="alert alert-info">
                    <h4>' . $alert . ' <i>' . $program->description . ' ' . $program->version . '</i></h4>
                </div><hr>';

                $mform->addElement('header', 'program', get_string('program', 'local_eabcprogramas'));

                $mform->addElement('text', 'description', get_string('description', 'local_eabcprogramas'));
                $mform->setType('description', PARAM_NOTAGS);
                $mform->addRule('description', get_string('required'), 'required');
                $mform->setDefault('description', $program->description);

                $mform->addElement('hidden', 'codigo', '');
                $mform->setType('codigo', PARAM_NOTAGS);
                if ($action == 'clonar')
                    $mform->setDefault('codigo', utils::struuid(false));
                else
                    $mform->setDefault('codigo', $program->codigo);

                $mform->addElement('hidden', 'grupo', '');
                $mform->setType('grupo', PARAM_NOTAGS);
                $mform->setDefault('grupo', $program->grupo);

                $mform->addElement('text', 'caducidad', get_string('meses', 'local_eabcprogramas'));
                $mform->setType('caducidad', PARAM_NOTAGS);
                $mform->addHelpButton('caducidad', 'caducidad', 'local_eabcprogramas');
                $mform->addRule('caducidad', get_string('numeric', 'local_eabcprogramas'), 'numeric');
                $mform->setDefault('caducidad', $program->caducidad);

                $mform->addElement('text', 'horas', get_string('horas', 'local_eabcprogramas'));
                $mform->setType('horas', PARAM_NOTAGS);
                $mform->addHelpButton('horas', 'horas', 'local_eabcprogramas');
                $mform->addRule('horas', get_string('numeric', 'local_eabcprogramas'), 'numeric');
                $mform->setDefault('horas', $program->horas);

                $mform->addElement('text', 'responsable', get_string('responsable', 'local_eabcprogramas'));
                $mform->setType('responsable', PARAM_NOTAGS);
                $mform->addHelpButton('responsable', 'responsable', 'local_eabcprogramas');
                $mform->setDefault('responsable', $program->responsable);

                $mform->addElement('text', 'version', get_string('version', 'local_eabcprogramas'));
                $mform->setType('version', PARAM_NOTAGS);
                $mform->addRule('version', get_string('required'), 'required', 'numeric');
                $mform->addHelpButton('version', 'version', 'local_eabcprogramas');
                if ($action == 'editar')
                    $mform->setDefault('version', $program->version);

                $objetivos = [];
                $objetivos['text'] = $program->objetivos;
                $objetivos['format'] = 1;
                $mform->addElement('editor', 'objetivos', get_string('objetivos', 'local_eabcprogramas'));
                $mform->setType('objetivos', PARAM_RAW);
                $mform->setDefault('objetivos', $objetivos);

                if ($action == 'clonar')
                    $this->add_action_buttons(true, 'Generar Duplicado');
                else
                    $this->add_action_buttons(true, 'Modificar');
            }
            if ($action == 'inactivar') {
                echo '
                <div class="alert alert-info">
                    <h4>Inactivar el programa <i>' . $program->description . ' ' . $program->version . '</i></h4>
                </div><hr>';
                $this->add_action_buttons(true, 'Inactivar este programa');
            }

            if ($action == 'vercursos') {
                global $OUTPUT, $CFG;
                echo '
                <div class="alert alert-info">
                    <h4>Lista de cursos del programa <i>' . $program->description . ' ' . $program->version . '</i></h4>
                </div><hr>';
                $url_regresar = new moodle_url('/local/eabcprogramas/programs.php');
                if (!$program->cursos) {
                    echo '<a href="' . $url_regresar . '" class="btn btn-primary">Regresar</a><br>';
                    echo '<h4>El programa <i>' . $program->description . '</i>, no tiene cursos asociados</h4>';

                } else {
                    $cursosid = explode(',', $program->cursos);
                    $courses = utils::get_courses_by_ids($cursosid);
                    $url_pdf = $CFG->wwwroot . '/local/eabcprogramas/pdfcursos.php?cursos=' . $program->cursos . '&programa=' . $program->id;
                    $table = $OUTPUT->render_from_template("local_eabcprogramas/lista_cursos", array('courses' => $courses, 'pdf' => $url_pdf, 'back' => $url_regresar));
                    echo $table;
                    echo '<hr>';
                    // echo '<h4>Objetivos</h4>';
                    echo $program->objetivos;
                }
            }
        }
    }

    /**
     * @param array $data
     * @param array $files
     * @return array
     * @throws dml_exception
     */
    function validation($data, $files)
    {
        global $DB, $CFG;

        $errors = parent::validation($data, $files);

        if (isset($data['version'])) {
            if (!is_numeric($data['version'])) {
                $errors['version'] = get_string('version_help', 'local_eabcprogramas');
            } else {
                if (preg_match("/\d{4}/", $data['version']) === 0) {
                    $errors["version"] = "Debe ser un número de 4 dígitos";
                }
                if ($data['version'] <= 2016 || $data['version'] > date('Y', time())) {
                    $errors["version"] = "Debe ser mayor que 2016 y menor que el año en curso";
                }
            }
        }
        // if (utils::validate_dates($data['fromdate'], $data['todate'], $data['todate_old'], $data['fromdate_old']) == 'todatebeforebeforefromdate') {
        //     $errors['todate'] = get_string('error', 'local_eabcprogramas');
        // }
        // if (utils::validate_dates($data['fromdate'], $data['todate'], $data['todate_old'], $data['fromdate_old']) == 'beforetoday') {
        //     $errors['todate'] = get_string('beforetoday', 'local_eabcprogramas');
        // }
        // if (utils::validate_dates($data['fromdate'], $data['todate'], $data['todate_old'], $data['fromdate_old']) == 'solapan') {
        //     $errors['todate'] = get_string('solapan', 'local_eabcprogramas');
        // }

        return $errors;
    }
}
