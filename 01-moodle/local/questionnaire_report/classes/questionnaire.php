<?php

class questionnaire{

    public static function getData($fdesde,$fhasta, $type){
        
        // @codingStandardsIgnoreLine
        /** @var \moodle_database $DB */
        global $DB, $CFG;
    
        require_once($CFG->dirroot . "/user/profile/lib.php");

        $DB->delete_records('questionnaire_report',array());

        $DB->execute('SET @a:=0');
        
        if($type == 'response_text') {
            
            $sql = "SELECT @a:=@a+1 id,
                        qr.id as qrid,
                        mu.id as 'userid',
                        qq.id as idpregunta,
                        c.id as cursoid,
                        c.fullname as 'fullname',
                        c.shortname as 'shortname',
                        q.name as 'name',
                        qq.content as 'pregunta',
                        tabl.response as respuesta,
                        tabl.response_id as respuesta_id,                        
                        mu.username as 'rut',
                        concat(mu.firstname,' ', mu.lastname) as 'nombres',
                        from_unixtime(qr.submitted, '%d-%m-%Y') as 'fecha',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 4) as 'participantecargo',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 5) as 'participantefechanacimiento',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 1) as 'empresarut',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 2) as 'empresarazonsocial',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 3) as 'empresacontrato',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 8) as 'participantesexo'
                    from {questionnaire_response} qr
                    join {questionnaire} q on qr.questionnaireid = q.id
                    join {course} c on c.id = q.course
                    join {questionnaire_response_text} tabl on tabl.response_id = qr.id
                    join {questionnaire_question} qq on tabl.question_id = qq.id
                    join {user} mu on mu.id = qr.userid                         
                    where qr.submitted >= ".$fdesde." AND qr.submitted <= ".$fhasta;
        }

        if ($type == 'resp_single'){
            $sql = "SELECT @a:=@a+1 id,
                        qr.id as qrid, 
                        mu.id as 'userid',
                        qq.id as idpregunta,
                        c.id as cursoid,
                        c.fullname as 'fullname',
                        c.shortname as 'shortname',
                        q.name as 'name',
                        qq.content as 'pregunta',
                        mqqc.content as respuesta,
                        mu.username as 'rut',
                        concat(mu.firstname,' ', mu.lastname) as 'nombres',
                        from_unixtime(qr.submitted, '%d-%m-%Y') as 'fecha',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 4) as 'participantecargo',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 5) as 'participantefechanacimiento',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 1) as 'empresarut',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 2) as 'empresarazonsocial',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 3) as 'empresacontrato',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 8) as 'participantesexo'
                    from {questionnaire_response} qr
                    join {questionnaire} q on qr.questionnaireid = q.id
                    join {course} c on c.id = q.course
                    join {questionnaire_resp_single} tabl on tabl.response_id = qr.id
                    join {questionnaire_question} qq on tabl.question_id = qq.id
                    join {user} mu on mu.id = qr.userid
                    left join {questionnaire_quest_choice} mqqc on tabl.choice_id = mqqc.id

                    where qr.submitted >= ".$fdesde." AND qr.submitted <= ".$fhasta;
        }

        if ($type == 'response_bool'){
            $sql = "SELECT @a:=@a+1 id,
                        qr.id as qrid,
                        mu.id as 'userid',
                        qq.id as idpregunta,
                        c.id as cursoid,
                        c.fullname as 'fullname',
                        c.shortname as 'shortname',
                        q.name as 'name',
                        qq.content as 'pregunta',
                        if(tabl.choice_id = 'y', 'si', if(tabl.choice_id = 'n', 'no', '-')) as respuesta,
                        mu.username as 'rut',
                        concat(mu.firstname,' ', mu.lastname) as 'nombres',
                        from_unixtime(qr.submitted, '%d-%m-%Y') as 'fecha',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 4) as 'participantecargo',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 5) as 'participantefechanacimiento',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 1) as 'empresarut',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 2) as 'empresarazonsocial',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 3) as 'empresacontrato',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 8) as 'participantesexo'
                    from {questionnaire_response} qr
                    join {questionnaire} q on qr.questionnaireid = q.id
                    join {course} c on c.id = q.course
                    join {questionnaire_response_bool} tabl on tabl.response_id = qr.id
                    join {questionnaire_question} qq on tabl.question_id = qq.id
                    join {user} mu on mu.id = qr.userid
                    where qr.submitted >= ".$fdesde." AND qr.submitted <= ".$fhasta;
        }

        if ($type == 'resp_multiple'){                        
            $sql = "SELECT @a:=@a+1 id,
                        qr.id as qrid,
                        mu.id as 'userid',
                        qq.id as idpregunta,
                        c.id as cursoid,
                        c.fullname as 'fullname',
                        c.shortname as 'shortname',
                        q.name as 'name',
                        qq.content as 'pregunta',
                        mqqc.content as respuesta,
                        mu.username as 'rut',
                        concat(mu.firstname,' ', mu.lastname) as 'nombres',
                        from_unixtime(qr.submitted, '%d-%m-%Y') as 'fecha',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 4) as 'participantecargo',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 5) as 'participantefechanacimiento',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 1) as 'empresarut',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 2) as 'empresarazonsocial',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 3) as 'empresacontrato',
                        (select uid.data from {user_info_data} uid where mu.id = uid.userid and uid.fieldid = 8) as 'participantesexo'
                    from {questionnaire_response} qr
                    join {questionnaire} q on qr.questionnaireid = q.id
                    join {course} c on c.id = q.course
                    join {questionnaire_resp_multiple} tabl on tabl.response_id = qr.id
                    join {questionnaire_question} qq on tabl.question_id = qq.id
                    join {user} mu on mu.id = qr.userid
                    left join {questionnaire_quest_choice} mqqc on tabl.choice_id = mqqc.id
                    where qr.submitted >= ".$fdesde." AND qr.submitted <= ".$fhasta;
        }

        $respuestas = $DB->get_records_sql($sql);

        $datos_totales = [];  
        foreach($respuestas as $respuesta){
            $datos = new \stdClass();
            $datos->course_id        = $respuesta->cursoid;
            $datos->course_fullname  = $respuesta->fullname;
            $datos->course_shortname = $respuesta->shortname;
            $datos->questionnaire_id = $respuesta->qrid;
            $datos->questionnaire_name =$respuesta->name;
            $datos->pregunta         = strip_tags($respuesta->pregunta); 
            $datos->pregunta_id      = strip_tags($respuesta->idpregunta); 
            $datos->respuesta        = $respuesta->respuesta;
            $datos->respuesta_id     = $respuesta->qrid;                   
            $datos->user             = $respuesta->nombres;
            $datos->username         = $respuesta->rut;
            $datos->userid           = $respuesta->userid;
            $datos->enviado          = $respuesta->fecha;
            $datos->participantecargo = $respuesta->participantecargo;
            $datos->participantefechanacimiento = $respuesta->participantefechanacimiento;
            $datos->empresarut          = $respuesta->empresarut;
            $datos->empresarazonsocial  = $respuesta->empresarazonsocial;
            $datos->empresacontrato     = $respuesta->empresacontrato;
            $datos->participantesexo    = $respuesta->participantesexo;

            $DB->insert_record('questionnaire_report', $datos);
            array_push($datos_totales,$datos);                        
        }
                
        return $datos_totales;        
    }
} 
