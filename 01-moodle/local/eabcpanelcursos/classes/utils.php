<?php

namespace local_eabcpanelcursos;

class utils {
    /* 
     *   Funcion para calcular si una fecha es fin de semana (Sabado o Domingo)
     *   Devuelve la cantidad de segundos correspondientes a adicionar
     *   Para sabado:  172800
     *   Para Domingo:  86400
     */
    public static function isWeekend($date) {
        $weekDay = date('w', $date);
        if ($weekDay == 0){
            $seconds = 86400;
        }else if ($weekDay == 6){
            $seconds = 172800;
        }else{
            $seconds = 0;
        }            
        return $seconds;    
    }
}
