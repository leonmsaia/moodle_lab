<?php
namespace local_showallactivities;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class utils {
    
    public static function get_where($fromform) {
        $where = "";
        if (!empty($fromform->rut)) {
            $where .= ' AND u.username LIKE "%' . $fromform->rut . '%"';
        }
        if (!empty($fromform->nombre)) {
            $where .= ' AND CONCAT(u.firstname, " ", u.lastname) LIKE "%' . $fromform->nombre . '%"';
        }
        if (!empty($fromform->empresa)) {
            $where .= ' AND sb.nombreadherente LIKE "%' . $fromform->empresa . '%"';
        }
        if (!empty($fromform->rutempresa)) {
            $where .= ' AND sb.rutadherente LIKE "%' . $fromform->rutempresa . '%"';
        }
        if (!empty($fromform->nroempresa)) {
            $where .= ' AND sb.numeroadherente LIKE "%' . $fromform->nroempresa . '%"';
        }
        if (!empty($fromform->curso)) {
            $where .= ' AND c.fullname LIKE "%' . $fromform->curso . '%"';
        }
        if (!empty($fromform->estadoabierto) || !empty($fromform->estadocerrado)) {
            $estadostr = "";
            if (!empty($fromform->estadoabierto)) {
                $estadostr .= '"100000001",';
            } if (!empty($fromform->estadocerrado)) {
                $estadostr .= '"100000003",';
            }
            $estadostr .= '"..."';
            $where .= ' AND sb.estado IN (' . $estadostr . ')';
        }
        
        if (!empty($fromform->dateto) && ($fromform->dateto != 0)  &&
        !empty($fromform->datefrom) && ($fromform->datefrom != 0)) {
            $today = $fromform->dateto['day'] . '-' .$fromform->dateto['month'] . '-' . $fromform->dateto['year'];
            $todayfrom = $fromform->datefrom['day'] . '-' .$fromform->datefrom['month'] . '-' . $fromform->datefrom['year'];
            $where .= ' AND s.sessdate >= "'.strtotime($today).'" AND s.sessdate <= "'.strtotime($todayfrom).'" '; 
        }

        if (!empty($fromform->evaluacion)) {
            $where .= ' AND aes.description LIKE "%' . $fromform->evaluacion . '%"';
        }
        if (!empty($fromform->modalidadopresencial) || !empty($fromform->modalidadsemipresencial) || !empty($fromform->modalidaddistancia)) {
            $modaliidadstr = "";
            if (!empty($fromform->modalidadopresencial)) {
                $modaliidadstr .= '"100000000",';
            } if (!empty($fromform->modalidadsemipresencial)) {
                $modaliidadstr .= '"100000001",';
            } 
            $modaliidadstr .= '"..."';
            $where .= ' AND cb.tipomodalidad IN (' . $modaliidadstr . ')';
        }
        if(!empty($fromform->modalidadelearning) || !empty($fromform->modalidadstreaming) || !empty($fromform->modalidadmobile)){
            $modaliidad_distancia_str = "";
            if (!empty($fromform->modalidadelearning)) {
                $modaliidad_distancia_str .= '"201320000",';
            } if (!empty($fromform->modalidadstreaming)) {
                $modaliidad_distancia_str .= '"201320001",';
            } if (!empty($fromform->modalidadmobile)) {
                $modaliidad_distancia_str .= '"201320002",';
            } 
            $modaliidad_distancia_str .= '"..."';
            $where .= ' AND cb.modalidaddistancia IN (' . $modaliidad_distancia_str . ')';
        }
        return $where;
    }
}