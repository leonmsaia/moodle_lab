<?php 

use local_eabcpanelcursos\utils;

class cursos_session  {

    public function get_sessions($fecha_desde,$fecha_hasta, $userID){
        
        global $DB;         

        $all_cursos         =  enrol_get_all_users_courses($userID);
        $actividades        = [];
        $totales            = [];
        $totalCursos        = 0;        
        $cursosPendientes   = 0;
        $cursosAtrasados    = 0;
        $cursosATiempo      = 0;
        $cursosFueraTiempo  = 0;
        $cursosEnProceso    = 0;
        $cursosNoIniciados  = 0;
        
        foreach($all_cursos as $curso){
            $all_groups = groups_get_course_data($curso->id);
            foreach ($all_groups->groups as $group) {
                
                
                $params['groupid'] = $group->id;  
                $params['userid'] = $userID;  

                $sqlGroup = 'SELECT * FROM {groups_members} WHERE groupid = :groupid and userid = :userid';
                $recordGroup = $DB->get_record_sql($sqlGroup, $params);
                
                if ($recordGroup) {

                    if((!$fecha_desde) && (!$fecha_hasta)) {
                        $fecha_desde = 1704081600;
                        $fecha_hasta = 1830312000;
                    }
                    
                    $sql = 'SELECT * FROM {eabcattendance_sessions} WHERE groupid = :groupid AND sessdate BETWEEN '.$fecha_desde.' AND '.$fecha_hasta .' order by sessdate desc limit 1';
                    $record = $DB->get_record_sql($sql, $params);
                    if ($record){
                        
                        $record_flags = $DB->get_record('focalizacion',array('sesionid'=>$record->id));

                        $sendmail  = 0;
                        $callphone = 0;
                        if ($record_flags){                        
                            $sendmail = ($record_flags->email == 1) ? 100 : 0;
                            $callphone= ($record_flags->callemp == 1) ? 100 : 0;
                        }

                        $sql_fisrt = 'SELECT * FROM {eabcattendance_sessions} WHERE groupid = :groupid order by sessdate ASC limit 1';
                        $record_fisrt = $DB->get_record_sql($sql_fisrt, $params);
                                            
                        $fecha_final = $record->sessdate + $record->duration;
                        $fecha_tope = $fecha_final + HORAS_PLAZO;
                        $weekends = utils::isWeekend($fecha_tope);
                        $fecha_tope = $fecha_tope + $weekends;
                        $fecha_inicio = $record_fisrt->sessdate;

                        if (($fecha_inicio < time() )){
                            $totalCursos++;
                        }
                        
                        $cursoAll = [];

                        $cursoAll['flags']['sendmail']  = $sendmail;
                        $cursoAll['flags']['callphone'] = $callphone;

                        $sql2 = 'SELECT * FROM {format_eabctiles_closegroup} WHERE groupid = :groupid and status = 1';
                        $record2 = $DB->get_record_sql($sql2, $params);                    
                        $fecha_cierre = ($record2) ? $record2->timecreated : 0;                      
                        
                        $diffFechas = $fecha_cierre - $fecha_final;

                        if (( $fecha_cierre !=0) && ($diffFechas <= HORAS_PLAZO) ){
                            $cursoAll['a_tiempo']['estatus']                = 'cerrado en tiempo';
                            $cursoAll['a_tiempo']['curso']                  = $curso->fullname;
                            $cursoAll['a_tiempo']['idcurso']                = $curso->id;                    
                            $cursoAll['a_tiempo']['fecha_final_curso']      = date('d-m-Y H:i', $fecha_final);
                            $cursoAll['a_tiempo']['fecha_mas_horas_tope']   = date('d-m-Y H:i', $fecha_tope);
                            $cursoAll['a_tiempo']['fecha_de_cierre']        = date('d-m-Y H:i', $fecha_cierre);
                            $cursosATiempo++;
                        }else if ( $fecha_cierre !=0 && $diffFechas > HORAS_PLAZO ){
                            $cursoAll['fuera_tiempo']['estatus']                = 'cerrado fuera de tiempo';
                            $cursoAll['fuera_tiempo']['curso']                  = $curso->fullname;
                            $cursoAll['fuera_tiempo']['idcurso']                = $curso->id;                    
                            $cursoAll['fuera_tiempo']['fecha_final_curso']      = date('d-m-Y H:i', $fecha_final);
                            $cursoAll['fuera_tiempo']['fecha_mas_horas_tope']   = date('d-m-Y H:i', $fecha_tope);
                            $cursoAll['fuera_tiempo']['fecha_de_cierre']        = date('d-m-Y H:i', $fecha_cierre);
                            $cursosFueraTiempo++;
                        }else if ( $fecha_cierre ==0){
                            if ((time() > $fecha_final) && (time() <= $fecha_tope) ){
                                $cursoAll['pendiente']['estatus']               = 'pendiente';
                                $cursoAll['pendiente']['curso']                 = $curso->fullname;
                                $cursoAll['pendiente']['idcurso']               = $curso->id;                    
                                $cursoAll['pendiente']['fecha_final_curso']     = date('d-m-Y H:i', $fecha_final);
                                $cursoAll['pendiente']['fecha_mas_horas_tope']  = date('d-m-Y H:i', $fecha_tope);
                                $cursoAll['pendiente']['fecha_de_cierre']       = date('d-m-Y H:i', $fecha_cierre);
                                $cursosPendientes++;
                            }else if ($fecha_tope <= time() ){
                                $cursoAll['atrasado']['estatus']                = 'atrasado';
                                $cursoAll['atrasado']['curso']                  = $curso->fullname;
                                $cursoAll['atrasado']['idcurso']                = $curso->id;                    
                                $cursoAll['atrasado']['fecha_final_curso']      = date('d-m-Y H:i', $fecha_final);
                                $cursoAll['atrasado']['fecha_mas_horas_tope']   = date('d-m-Y H:i', $fecha_tope);
                                $cursoAll['atrasado']['fecha_de_cierre']        = date('d-m-Y H:i', $fecha_cierre);
                                $cursosAtrasados++;
                            }else if($fecha_tope > time() && ($fecha_inicio < time() )) { 
                                $cursoAll['en_proceso']['estatus']              = 'en_proceso';
                                $cursoAll['en_proceso']['curso']                = $curso->fullname;
                                $cursoAll['en_proceso']['idcurso']              = $curso->id;                    
                                $cursoAll['en_proceso']['fecha_final_curso']    = date('d-m-Y H:i', $fecha_final);
                                $cursoAll['en_proceso']['fecha_mas_horas_tope'] = date('d-m-Y H:i', $fecha_tope);
                                $cursoAll['en_proceso']['fecha_de_cierre']      = date('d-m-Y H:i', $fecha_cierre);
                                $cursosEnProceso++;
                            }else{
                                $cursoAll['no_iniciado']['estatus']             = 'no_iniciado';
                                $cursoAll['no_iniciado']['curso']               = $curso->fullname;
                                $cursoAll['no_iniciado']['idcurso']             = $curso->id;                    
                                $cursoAll['no_iniciado']['fecha_final_curso']   = date('d-m-Y H:i', $fecha_final);
                                $cursoAll['no_iniciado']['fecha_mas_horas_tope']= date('d-m-Y H:i', $fecha_tope);
                                $cursoAll['no_iniciado']['fecha_de_cierre']     = date('d-m-Y H:i', $fecha_cierre);
                                $cursosNoIniciados++;
                            }                                                 
                        }else {
                            $cursoAll['otro']['estatus'] = 'otro estatus';
                        }                  

                        array_push($actividades,$cursoAll);
                    }
                }
                
            }
        }
        
        $suspendidos = $this->get_suspends($fecha_desde, $fecha_hasta, $userID);

        foreach ($suspendidos as $suspendido) {          
            if ( isset($suspendido['estatus'])){                
                $cursosSuspendidosPrevios = $suspendido['estatus']['suspendido_previo'];
                $cursosSuspendidosDurante = $suspendido['estatus']['suspendido_durante']; 
                $totalCursos += $cursosSuspendidosPrevios + $cursosSuspendidosDurante;
                if ($totalCursos!=0){
                    $totales['porcentajes']['suspendido_previo']   = round(($cursosSuspendidosPrevios   * 100) / $totalCursos);
                    $totales['porcentajes']['suspendido_durante']  = round(($cursosSuspendidosDurante   * 100) / $totalCursos);
                }                
                $totales['estatus']['suspendido_previo']    = $cursosSuspendidosPrevios;
                $totales['estatus']['suspendido_durante']   = $cursosSuspendidosDurante;                
            }  
            if ( isset($suspendido['suspendido_previo']) || isset($suspendido['suspendido_durante'])  ){ 
                array_push($actividades,$suspendido);
            }            
        }
                       
        $totales['estatus']['a_tiempo']     = $cursosATiempo;
        $totales['estatus']['fuera_tiempo'] = $cursosFueraTiempo;
        $totales['estatus']['atrasado']     = $cursosAtrasados;
        $totales['estatus']['pendiente']    = $cursosPendientes;
        $totales['estatus']['en_proceso']   = $cursosEnProceso;
        //$totales['estatus']['no_iniciado']   = $cursosNoIniciados;
        $totales['estatus']['totalCerrados']  = $cursosATiempo + $cursosFueraTiempo;
        $totales['estatus']['totalCursos']  = $totalCursos;

        if ($totalCursos!=0){
            $totales['porcentajes']['a_tiempo']     = round(($cursosATiempo     * 100) / $totalCursos);
            $totales['porcentajes']['fuera_tiempo'] = round(($cursosFueraTiempo * 100) / $totalCursos);
            $totales['porcentajes']['atrasado']     = round(($cursosAtrasados   * 100) / $totalCursos);
            $totales['porcentajes']['pendiente']    = round(($cursosPendientes  * 100) / $totalCursos);
            $totales['porcentajes']['en_proceso']   = round(($cursosEnProceso   * 100) / $totalCursos);
            $totales['porcentajes']['totalCerrados']   = $totales['porcentajes']['a_tiempo'] + $totales['porcentajes']['fuera_tiempo'];
            //$totales['porcentajes']['no_iniciado']  = round(($cursosNoIniciados * 100) / $totalCursos);
        }
        
        array_push($actividades,$totales);                
        return $actividades;
    }


    public function get_suspends($fecha_desde, $fecha_hasta, $userID){
        global $USER, $DB;

        $all_cursos =  enrol_get_all_users_courses($userID);
        
        $totalSuspendidoPrevio=0;
        $totalSuspendidoDurante=0;
        $suspendidos = [];

        foreach($all_cursos as $curso){
            $cursoAll = [];
            $params['courseid'] = $curso->id;
            $sql = 'SELECT * FROM {format_eabctiles_suspendgrou} WHERE courseid = :courseid limit 1';
            $suspendido = $DB->get_record_sql($sql, $params); 
            if ($suspendido){
                $curso = $DB->get_record('course', array('id' => $curso->id ));                
                $andwhere =  '';
                if (($fecha_desde!='') && ($fecha_hasta!='')) {
                    $andwhere = ' AND sessdate BETWEEN '.$fecha_desde.' AND '.$fecha_hasta;
                }

                $params['groupid'] = $suspendido->groupid;
                $sql = 'SELECT * FROM {eabcattendance_sessions} WHERE  groupid = :groupid '.$andwhere.' order by sessdate ASC limit 1';
                $record_fisrt = $DB->get_record_sql($sql, $params);

                if($record_fisrt){
                    $fecha_inicio = $record_fisrt->sessdate; 
                    
                    if ($fecha_inicio > ($suspendido->timecreated )){
                        $cursoAll['suspendido_previo']['estatus']           = 'suspendido_previo';
                        $cursoAll['suspendido_previo']['curso']             = $curso->fullname;
                        $cursoAll['suspendido_previo']['idcurso']           = $curso->id;                    
                        $cursoAll['suspendido_previo']['fecha_inicio']      = date('d-m-Y H:i', $fecha_inicio);    
                        $cursoAll['suspendido_previo']['fecha_suspencion']  = date('d-m-Y H:i', $suspendido->timecreated);    
                        $totalSuspendidoPrevio++;
                    }else{
                        $cursoAll['suspendido_durante']['estatus']          = 'suspendido_durante';
                        $cursoAll['suspendido_durante']['curso']            = $curso->fullname;
                        $cursoAll['suspendido_durante']['idcurso']          = $curso->id;                    
                        $cursoAll['suspendido_durante']['fecha_inicio']     = date('d-m-Y H:i', $fecha_inicio);
                        $cursoAll['suspendido_durante']['fecha_suspencion'] = date('d-m-Y H:i', $suspendido->timecreated);    
                        $totalSuspendidoDurante++;
                    }  
                    array_push($suspendidos,$cursoAll);
                }                
            }            
        }

        $totales['estatus']['suspendido_previo']     = $totalSuspendidoPrevio;
        $totales['estatus']['suspendido_durante']    = $totalSuspendidoDurante;
        array_push($suspendidos,$totales);
        return $suspendidos;
    }
}
