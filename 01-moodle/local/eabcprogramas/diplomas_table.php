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

class diplomas_table extends table_sql
{
    /**
     * Constructor
     * @param int $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     */
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
        $columns = array('preview', 'status', 'fromdate', 'todate', 'otorgados', 'action');
        $this->define_columns($columns);
        $headers = array('Imágen', 'Condición', 'Desde', 'Hasta', 'Programas Otorgados', 'Acción');
        $this->define_headers($headers);
    }

    /**
     * 
     *
     * @param object $values Contains object with all the values of record.
     * @return $string
     */
    function col_fromdate($values)
    {
        $date = new DateTime("@$values->fromdate");
        return $date->format('Y-m-d');
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

    function col_status($values)
    {
        if (!$values->status) {
            return 'Inactivo';
        }
        return 'Activo';
    }

    function other_cols($colname, $value)
    {
        global $DB;
        $url = new moodle_url('/local/eabcprogramas/diploma.php');
        $td = '';
        if ($colname == 'action') {
            $programa_otorgados = $DB->get_records('local_eabcprogramas_usuarios', ['diplomaid' => $value->id]);
            if ($value->status) {
                $url_crear = new moodle_url('/local/eabcprogramas/diploma.php', ['action' => 'crear']);
                $td = '
                    <a href="' . $url . '?id=' . $value->id . '&action=inactivar" title="Inactivar">
                        <i class="fa fa-ban"></i> 
                    </a>';
                if (count($programa_otorgados) == 0) {
                    $td .= '
                    <a href="' . $url_crear . '" title="Reemplazar"> 
                        <i class="fa fa-exchange"></i>
                    </a>
                    <a href="' . $url . '?id=' . $value->id . '&action=eliminar" title="Eliminar">
                        <i class="fa fa-trash"></i> 
                    </a>';
                }
                return $td;
            } else {
                if (count($programa_otorgados) == 0) {
                    $td .= '
                    <a href="' . $url . '?id=' . $value->id . '&action=eliminar" title="Eliminar">
                        <i class="fa fa-trash"></i> 
                    </a>';
                }
                return $td;
            }

            return $td;
        }
        if ($colname == 'otorgados') {
            $programa_otorgados = $DB->get_records('local_eabcprogramas_usuarios', ['diplomaid' => $value->id]);
            return count($programa_otorgados);
        }
        if ($colname == 'preview') {
            if ($value->urlfile) {
                $td = '
                <a href="' . $value->urlfile . '" class="zoom" target="_blank">
                    <img src="' . $value->urlfile . '" class="img-thumbnail" alt="diploma">
                </a>';
                return $td;
            }
            return '';
        }
        return '';
    }
}
