<?php 
require_once($CFG->libdir . '/ddllib.php');

class feedback_facilitador  {    

    /**
     * Funcion para obtener los facilitadores registrados en la tabla facilitador_back
     */
    public static function get_facilitadores($facilitador = null) {
        global $DB;
    
        $sql = "SELECT * FROM mdl_facilitador_back";
    
        if (!is_null($facilitador) && $facilitador != 0) {
            $sql .= " WHERE id = ?";
            $params = array($facilitador);
        } else {
            $sql .= " ORDER BY nombre ASC LIMIT 1";  // Agrega un límite para obtener solo el primer registro
            $params = array();
        }
    
        $facilitadores = $DB->get_records_sql($sql, $params);
        return $facilitadores;
    }
    

    /**
     * Funcion que devuelve un array de objetos de facilitadores con cursos asociados
     */
    public static function get_facilitadores_cursos($facilitadorId){
        $facilitadores = enrol_get_users_courses($facilitadorId,true); 
        return $facilitadores;
    }

    /** 
     * Funcion que calcula los resultados de un feeback de un facilitados de acuerdo a los valores 
     * de calificación obtenidos en la encuesta de satisfación. Dichos valores toman de acuerdo 
     * al campo 'Label' de la tabla feedback_item y se agrupan de acuerdo a los calculos de puntaje a realizar      
     */

    public static function get_resultados_feedback($facilitadorId,$fecha_desde,$fecha_hasta){
        
        $resultados = '(5,6,7)';
        $horarios   = '(6)';
        $recomienda = '(7)';
        $resultados_curso = '(1,2,3)';

        $total_feedback     = 0;
        $total_horarios     = 0;
        $total_recomienda   = 0;
        $total_curso        = 0;

        $cursos = self::get_facilitadores_cursos($facilitadorId);
        
        foreach($cursos as $curso){            
            $feedback           = self::get_resultado_feedback_curso_facilitador($curso->id, $resultados,$fecha_desde,$fecha_hasta);
            $feedback_horarios  = self::get_resultado_feedback_curso_facilitador($curso->id, $horarios,$fecha_desde,$fecha_hasta);
            $feedback_recomienda= self::get_resultado_feedback_curso_facilitador($curso->id, $recomienda ,$fecha_desde,$fecha_hasta);
            $feedback_curso     = self::get_resultado_feedback_curso_facilitador($curso->id, $resultados_curso, $fecha_desde,$fecha_hasta);
            $total_feedback     += $feedback;     
            $total_horarios     += $feedback_horarios;
            $total_recomienda   += $feedback_recomienda;
            $total_curso        += $feedback_curso;
        }

        if (count($cursos)!=0){
            $total_feedback     =  round(($total_feedback/count($cursos) * 100) / 15) ; 
            $total_horarios     =  round($total_horarios/count($cursos));
            $total_recomienda   =  round($total_recomienda/count($cursos));
            $total_curso        =  round(($total_curso/count($cursos) * 100) / 15) ; 
        }

        $response = [
                    'feedback'      => $total_feedback, 
                    'horario'       => $total_horarios, 
                    'recomienda'    => $total_recomienda,
                    'feedback_curso'=> $total_curso,
                ];
        return $response;    
    }

    /*
     *   Devuelve el puntaje obtenido de las preguntas correspondientes a un Facilitador por curso
     *   Se toman como parametros de busqueda los Label asociados a las mismas
     *   Ejemplo: 5,6 y 7 Son los correspondientes al feedback del facilitador
     *   1,2, y 3 Son los correspondientes al feedback del Curso
    */  
    public static function get_resultado_feedback_curso_facilitador($cursoId, $criterio, $fecha_desde, $fecha_hasta){
        global $DB;
         
        $andwhere =  '';
        if (($fecha_desde!='') && ($fecha_hasta!='')) {
            $andwhere = ' AND timemodified BETWEEN '.$fecha_desde.' AND '.$fecha_hasta;
        }
        $params['course'] = $cursoId;  
        $sql = 'SELECT * FROM {feedback} WHERE course = :course '.$andwhere;
        $feedback = $DB->get_record_sql($sql, $params);
        //$feedback = $DB->get_record('feedback',array('course'=>$cursoId));
        
        $total_item = 0;
        if (isset($feedback->id)){
            $params['feedback'] = $feedback->id;
            $sql = 'SELECT * FROM {feedback_item} where feedback = :feedback AND label in '. $criterio;
            $feedback_items  = $DB->get_records_sql($sql,$params);                
            foreach($feedback_items as $feedback_item){                    
                $feedback_values = $DB->get_records('feedback_value',array('item' => $feedback_item->id));                                        
                foreach ($feedback_values as $feedback_value) {                    
                    if (($criterio == '(5,6,7)') || ($criterio == '(1,2,3)')){
                        $total_item = $total_item + $feedback_value->value;
                    }else if ($criterio == '(6)'){
                        if ($feedback_value->value == 1){
                            $total_item = $total_item + 100;
                        }else if ($feedback_value->value == 2 || $feedback_value->value == 3 ){
                            $total_item = $total_item + 50;
                        }else{
                            $total_item = $total_item + 0;
                        }
                    }else{
                        if ($feedback_value->value == 1){
                            $total_item = $total_item + 100;
                        }else{
                            $total_item = $total_item + 0;
                        }
                    }                  
                }      
            }                
        }

        return $total_item;

    }

    /**
     * Esta funcion realiza el calculo y setea los registros en panel_feedback_facilitadores
     * Con el fin de agilizar la muestra de resultados desde un data table
     * En cada llamado a esta funcion se realiza un Truncate de la tabla y se recalculan los valores
     * Recibe los parametros de busqueda $fecha_desde y $fecha_hasta como filtros
     */

    public static function set_table_panel_facilitadores($fecha_desde,$fecha_hasta, $facilitador, $rangos){
        global $DB;

        define('HORAS_PLAZO', 86400);
        $facilitadores = self::get_facilitadores($facilitador);
        try{
            $cursos = new cursos_session();
            $DB->delete_records('panel_feedback_facilitadores',array());
            $datos_totales = [];

            foreach($facilitadores as $facilitador){
                $cumplimiento_plazo     = 0;
                $cumplimiento_cierre    = 0;
                $total_cumplimiento     = 0;
                $cant_cursos            = 0;   
                $cant_cursos_suspendidos  = 0;         
                $feebackFacilitador     = self::get_resultados_feedback($facilitador->id_user_moodle,$fecha_desde,$fecha_hasta);    
                $estado_cursos          = $cursos->get_sessions($fecha_desde,$fecha_hasta,$facilitador->id_user_moodle);

                $contFlags          = 0;
                $subtotalSendMail   = 0;
                $subtotalCallPhone  = 0;
                foreach($estado_cursos as $estado){

                    if (isset($estado['estatus'])){
                        $cant_cursos  = $estado['estatus']['totalCursos'];
                        $cant_cursos_suspendidos  = $estado['estatus']['suspendido_previo'] + $estado['estatus']['suspendido_durante'];
                    }

                    if (isset($estado['porcentajes'])){
                        $cumplimiento_plazo  = $estado['porcentajes']['a_tiempo'];
                        $cumplimiento_cierre = $estado['porcentajes']['a_tiempo'] + $estado['porcentajes']['fuera_tiempo']; 
                    }            

                    if (isset($estado['flags'])){
                        $contFlags++;
                        $subtotalSendMail   += $estado['flags']['sendmail'];                    
                        $subtotalCallPhone  += $estado['flags']['callphone'];    
                    }
                }

                $totalSendMail  = ($contFlags!=0) ? round($subtotalSendMail/$contFlags) : 0;
                $totalCallPhone = ($contFlags!=0) ? round($subtotalCallPhone/$contFlags) : 0;
                    
                $total_cumplimiento = ($cumplimiento_plazo + $cumplimiento_cierre + $feebackFacilitador['feedback'] + $feebackFacilitador['horario']) / 4;
                
                $data = '';
                if ($rangos == 0){
                    $data = self::set_data($facilitador, $cumplimiento_plazo, $cumplimiento_cierre, $feebackFacilitador, $total_cumplimiento, $cant_cursos, $cant_cursos_suspendidos, $totalSendMail, $totalCallPhone);
                }elseif ($rangos == 1){
                    if ($total_cumplimiento == 0){
                        $data = self::set_data($facilitador, $cumplimiento_plazo, $cumplimiento_cierre, $feebackFacilitador, $total_cumplimiento, $cant_cursos, $cant_cursos_suspendidos, $totalSendMail, $totalCallPhone);
                    }
                }elseif ($rangos == 2){
                    if ($total_cumplimiento > 0 && $total_cumplimiento <=25){
                        $data = self::set_data($facilitador, $cumplimiento_plazo, $cumplimiento_cierre, $feebackFacilitador, $total_cumplimiento, $cant_cursos, $cant_cursos_suspendidos, $totalSendMail, $totalCallPhone);
                    }
                }elseif ($rangos == 3){
                    if ($total_cumplimiento > 25 && $total_cumplimiento <=50){
                        $data = self::set_data($facilitador, $cumplimiento_plazo, $cumplimiento_cierre, $feebackFacilitador, $total_cumplimiento, $cant_cursos, $cant_cursos_suspendidos, $totalSendMail, $totalCallPhone);
                    }
                }elseif ($rangos == 4){
                    if ($total_cumplimiento > 50 && $total_cumplimiento <=75){
                        $data = self::set_data($facilitador, $cumplimiento_plazo, $cumplimiento_cierre, $feebackFacilitador, $total_cumplimiento, $cant_cursos, $cant_cursos_suspendidos, $totalSendMail, $totalCallPhone);
                    }
                }elseif ($rangos == 5){
                    if ($total_cumplimiento > 75 && $total_cumplimiento <=100){
                        $data = self::set_data($facilitador, $cumplimiento_plazo, $cumplimiento_cierre, $feebackFacilitador, $total_cumplimiento, $cant_cursos, $cant_cursos_suspendidos, $totalSendMail, $totalCallPhone);
                    }
                }
                
                if (!empty($data)){
                    $DB->insert_record('panel_feedback_facilitadores', $data);
                }
                
                array_push($datos_totales,$data);
                
            }
            return $datos_totales;
        }catch (moodle_exception $e) {
            throw new moodle_exception("errormsg", "feedback_facilitador", '', $e->getMessage(), $e->debuginfo);
        }
        
    }

    
    public static function set_data($facilitador, $cumplimiento_plazo, $cumplimiento_cierre, $feebackFacilitador, $total_cumplimiento, $cant_cursos, $cant_cursos_suspendidos, $totalSendMail, $totalCallPhone){
        $data = new \stdClass();
        $data->facilitador_id       = $facilitador->id_user_moodle;
        $data->nombre               = $facilitador->nombre.' '.$facilitador->apellidopaterno;
        $data->cumplimiento_plazo   = $cumplimiento_plazo ;
        $data->cumplimiento_cierre  = $cumplimiento_cierre;
        $data->encuesta_facilitador	= $feebackFacilitador['feedback'];
        $data->cumplimiento_horario = $feebackFacilitador['horario'];
        $data->total_cumplimiento   = $total_cumplimiento;
        $data->cantidad_cursos      = $cant_cursos;
        $data->cantidad_cursos_suspendidos = $cant_cursos_suspendidos;
        $data->recomienda_curso     = $feebackFacilitador['recomienda'];
        $data->envio_correo         = $totalSendMail;
        $data->llamo_empresa        = $totalCallPhone;
        $data->encuesta_curso       = $feebackFacilitador['feedback_curso']; 

        return $data;
    }

    /**
     * Funcion que calcula el total de registros en la tabla panel_feedback_facilitadores
     * en forma de array de objetos para ser renderizados en el template de totales
     */

    public static function get_totales(){
        global $DB;

        $facilitadores = $DB->get_records('panel_feedback_facilitadores',array());

        $total_cumplimiento = 0;
        $cantidad_cursos    = 0;
        $recomienda_curso   = 0;
        $envio_correo       = 0;
        $llamo_empresa      = 0;
        $encuesta_curso     = 0;

        foreach($facilitadores as $facilitador){
            $total_cumplimiento += $facilitador->total_cumplimiento;
            $cantidad_cursos    += $facilitador->cantidad_cursos;
            $recomienda_curso   += $facilitador->recomienda_curso;
            $envio_correo       += $facilitador->envio_correo;
            $llamo_empresa      += $facilitador->llamo_empresa;
            $encuesta_curso     += $facilitador->encuesta_curso;
        }

        $count_facilitadores = count($facilitadores);
        if ($count_facilitadores !=0){
            $total_cumplimiento = round($total_cumplimiento/$count_facilitadores);
            $recomienda_curso   = round($recomienda_curso/$count_facilitadores);
            $envio_correo       = round($envio_correo/ $count_facilitadores);
            $llamo_empresa      = round($llamo_empresa / $count_facilitadores);
            $encuesta_curso     = round($encuesta_curso / $count_facilitadores);
        }

        $totales = array(
            'total_cumplimiento'    => $total_cumplimiento,
            'cantidad_cursos'       => $cantidad_cursos,
            'recomienda_curso'      => $recomienda_curso,
            'envio_correo'          => $envio_correo,
            'llamo_empresa'         => $llamo_empresa,
            'encuesta_curso'        => $encuesta_curso
        );

        return $totales;
    }
    
    /**
     * Funcion que retorna el Grafico de barras con los datos de los facilitadores
     */

    public static function show_grafiph($facilitadores){
        $chart = new \core\chart_bar(); 
        $chart->set_stacked(true);
    
        $nombres        = [];
        $cumplimientos  = [];
        $recomienda     = [];
        $correo         = [];
        $llamo          = [];
        $encuesta       = [];
        foreach($facilitadores as $facilitador){            
            if ( isset($facilitador->total_cumplimiento) && $facilitador->total_cumplimiento!=0){
                array_push($nombres, $facilitador->nombre);
                array_push($cumplimientos, $facilitador->total_cumplimiento);
                array_push($recomienda, $facilitador->recomienda_curso);
                array_push($correo, $facilitador->envio_correo);
                array_push($llamo, $facilitador->llamo_empresa);
                array_push($encuesta, $facilitador->encuesta_curso);
            }
        }
    
        $cumplimientos  = new \core\chart_series('Total cumplimiento', $cumplimientos);
        $recomienda     = new \core\chart_series('Recomienda curso', $recomienda);
        $correo         = new \core\chart_series('Correo', $correo);
        $llamo          = new \core\chart_series('Llamo', $llamo);
        $encuesta       = new \core\chart_series('Encuesta curso', $encuesta);
    
        $chart->add_series($cumplimientos);
        $chart->add_series($recomienda);
        $chart->add_series($correo);
        $chart->add_series($llamo);
        $chart->add_series($encuesta);
    
        $chart->set_labels($nombres);
        $chart->set_horizontal(true);

        return $chart;
    }

}
