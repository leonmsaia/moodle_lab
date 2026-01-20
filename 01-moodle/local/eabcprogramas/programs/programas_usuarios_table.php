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
class programas_usuarios_table extends table_sql
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
    function col_codigo($values)
    {
        return $values->codigo;
    }
    function col_description($values)
    {
        return $values->description;
    }
    function col_empresa($values)
    {
        return $values->empresa;
    }
    function col_rut_empresa($values)
    {
        return $values->rut_empresa;
    }
    function col_usuario($values)
    {
        return $values->usuario;
    }
    function col_rut_usuario($values)
    {
        return $values->rut_usuario;
    }
    /**
     *
     * @param object $values Contains object with all the values of record.
     * @return $string
     */
    function col_status($values)
    {
        if ($values->status == utils::vigente())
            return 'Si';
        else
            return 'No';
    }

    function col_end_firstcourse($values)
    {
        return $this->get_formato($values->end_firstcourse);
    }

    function col_fecha_otorgamiento($values)
    {
        return $this->get_formato($values->fecha_otorgamiento);
    }

    function col_fecha_vencimiento($values)
    {
        return $this->get_formato($values->fecha_vencimiento);
    }
    /**
     *  to allow processing of
     * columns which do not have a *_cols function.
     * @return string return processed value. Return NULL if no change has
     *     been made.
     */
    function other_cols($colname, $value)
    {
        global $CFG;
        $certificado = new moodle_url('/local/eabcprogramas/pdfcertificado.php', ['id' => $value->id]);
        $diploma = new moodle_url('/local/eabcprogramas/pdfdiploma.php', ['id' => $value->id]);
        $td = '';
        if ($colname == 'action') {
            if ($value->status == utils::vigente()) {
                $imgdiploma = $CFG->wwwroot . '/local/eabcprogramas/pix/diploma.png';
                $imgcertificado = $CFG->wwwroot . '/local/eabcprogramas/pix/certificado.png';
                $td = '
                <a href="' . $certificado . '" target="_blank" class="">
                <img style="height:20px; width:20px" class="img-responsive" src="' . $imgcertificado . '" alt="Certificado" title="Certificado">
                </a>
                <a href="' . $diploma . '" target="_blank" class="">
                <img style="height:40px; width:40px" class="img-responsive" src="' . $imgdiploma . '" alt="Diploma" title="Diploma">
                </a>';

                if (!$this->is_downloading())
                    return $td;
            }
        }
        return '';
    }

    function get_formato($time)
    {
        if (!$time) {
            return '-';
        }
        $date = new DateTime("@$time");
        return $date->format('Y-m-d');
    }
    function col_codigo_programa($values)
    {
        return $values->codigo_programa;
    }
    function col_codigo_diploma($values)
    {
        return $values->codigo_diploma;
    }
    function col_codigo_certificado($values)
    {
        return $values->codigo_certificado;
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
