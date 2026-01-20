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

use local_eabcprogramas\utils;

/**
 *
 * @package    local_eabcprogramas
 * @copyright  2020 e-abclearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class programs_table extends table_sql
{
    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
    }
    /**
     *
     * @param object $values Contains object with all the values of record.
     * @return $string
     */
    function col_status($values)
    {
        if ($values->status == utils::activo())
            return 'Activo';
        if ($values->status == utils::inactivo())
            return 'Inactivo';
        if ($values->status == utils::enpreparacion())
            return 'En Preparación';
    }
    /**
     * 
     *
     * @param object $values Contains object with all the values of record.
     * @return $string
     */
    function col_fromdate($values)
    {
        if (!$values->fromdate) {
            return '-';
        }
        $date = new DateTime("@$values->fromdate");
        return $date->format('Y-m-d');
    }
    /**
     * 
     *
     * @param object $values Contains object with all the values of record.
     * @return $string
     */
    function col_objetivos($values)
    {
        if (strpos($values->objetivos, '<ol>') || strpos($values->objetivos, '<ul>')) {
            $objs = strip_tags($values->objetivos, '<li>');
            $objs = str_replace("</li>", "", $objs);
            $objs_elems = explode("<li>", $objs);
            unset($objs_elems[0]);
            $objs_str = utils::get_filters($objs_elems);
            
            return $objs_str;
        }
        return strip_tags($values->objetivos);
    }
    /**
     * 
     *
     * @param object $values Contains object with all the values of record.
     * @return $string
     */
    function col_todate($values)
    {
        if (!$values->todate) {
            return '-';
        }
        $date = new DateTime("@$values->todate");
        return $date->format('Y-m-d');
    }

    /**
     *  to allow processing of
     * columns which do not have a *_cols function.
     * @return string return processed value. Return NULL if no change has
     *     been made.
     */
    function other_cols($colname, $value)
    {
        global $DB;
        $url = new moodle_url('/local/eabcprogramas/programa.php');
        $td = '
            <a href="' . $url . '?id=' . $value->id . '&action=vercursos" title="Listar cursos">
                <i class="fa fa-list"></i>
            </a>
            <a href="' . $url . '?id=' . $value->id . '&action=clonar" title="Generar Duplicado">
                <i class="fa fa-clone"></i>
            </a>';
        if ($colname == 'action') {
            if ($value->status == utils::activo()) { //y si hay datos de trabajadores en la tabla certificados-programas
                $td .= '                
                <a href="' . $url . '?id=' . $value->id . '&action=inactivar" title="Inactivar">
                    <i class="fa fa-ban"></i>
                </a>
                ';
                $programa_otorgados = $DB->get_records('local_eabcprogramas_usuarios', ['programid' => $value->id]);
                if (!count($programa_otorgados)) {
                    $td .= '
                    <a href="' . $url . '?id=' . $value->id . '&action=cursos" title="Agregar o Eliminar Cursos">
                        <i class="fa fa-plus"></i>
                    </a>
                    <a href="' . $url . '?id=' . $value->id . '&action=editar" title="Modificar">
                        <i class="fa fa-edit"></i>
                    </a>';
                }
            }

            if ($value->status == utils::inactivo()) {
                $td .= '
                <a href="' . $url . '?id=' . $value->id . '&action=activar" title="Activar">
                    <i class="fa fa-send"></i>
                </a>';
            }

            if ($value->status == utils::enpreparacion()) {
                $td .= '
                <a href="' . $url . '?id=' . $value->id . '&action=cursos" title="Agregar o Eliminar Cursos">
                    <i class="fa fa-plus"></i>
                </a>
                <a href="' . $url . '?id=' . $value->id . '&action=editar" title="Modificar">
                    <i class="fa fa-edit"></i>
                </a>
                <a href="' . $url . '?id=' . $value->id . '&action=activar" title="Activar">
                    <i class="fa fa-send"></i>
                </a> 
                ';
            }
            if (!$this->is_downloading())
                return $td;
            return '';
        }

        if ($colname == 'otorgados') {
            $programa_otorgados = $DB->get_records('local_eabcprogramas_usuarios', ['programid' => $value->id]);
            return count($programa_otorgados);
        }
    }

    public function download_buttons()
    {
        global $OUTPUT;

        if ($this->is_downloadable() && !$this->is_downloading()) {
            return $this->download_dataformat_selector_table(
                get_string('downloadas', 'table'),
                $this->baseurl->out_omit_querystring(),
                'download',
                $this->baseurl->params()
            );
        } else {
            return '';
        }
    }

    public function download_dataformat_selector_table($label, $base, $name = 'dataformat', $params = array())
    {
        global $OUTPUT;
        $formats = core_plugin_manager::instance()->get_plugins_of_type('dataformat');
        $options = array();

        foreach ($formats as $format) {
            if ($format->is_enabled()) {
                if (($format->name == 'excel')) {
                    $options[] = array(
                        'value' => $format->name,
                        'label' => get_string('dataformat', $format->component),
                    );
                }
            }
        }
        $hiddenparams = array();
        foreach ($params as $key => $value) {
            $hiddenparams[] = array(
                'name' => $key,
                'value' => $value,
            );
        }
        $data = array(
            'label' => $label,
            'base' => $base,
            'name' => $name,
            'params' => $hiddenparams,
            'options' => $options,
            'sesskey' => sesskey(),
            'submit' => get_string('download'),
        );
        return $OUTPUT->render_from_template('core/dataformat_selector', $data);
    }
}
