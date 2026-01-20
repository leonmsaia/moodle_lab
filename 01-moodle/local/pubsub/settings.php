<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {


    $settings = new admin_settingpage('local_pubsub', 'Configuración sistema publish subscribe');

    // Create 
    $ADMIN->add('localplugins', $settings);

    // Add a setting field to the settings for this page
    $settings->add(new admin_setting_configtext(
            'local_pubsub/conectionstring', // This is the reference you will use to your configuration
            'Conection string', // This is the friendly title for the config, which will be displayed
            'Conection string for microsoft asure', // This is helper text for this config field
            'Endpoint=https://qaeus2sbusintcap001.servicebus.windows.net/;SharedAccessKeyName=FRONT;SharedAccessKey=VPmZ3+5JJoL3eDU9tUkSeW9CQ6BzUUc8bGAVupoS1ts=', // This is the default value
            PARAM_TEXT // This is the type of Parameter this config is
    ));

    $settings->add(new admin_setting_configtext(
            'local_pubsub/tokenapi', 'Token API', 'Token API', 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJncnAiOiJNVVRVQUwiLCJ2cnNuIjoiMi4wIiwiYXBwIjoiQXBsaWNhY2nDs24gZGUgY29uc29sYSIsInJvbGUiOiJJTkdFTklFUk8gREUgU09QT1JURSIsInJvbGVJZCI6IjE1IiwiZXhwIjoxNzUxOTc4MjkzLCJpc3MiOiJNdXR1YWwuSUEuVG9rZW5Qb3J0YWwiLCJhdWQiOiJNdXR1YWwuSUEuQVBJIn0.1ABpOhjJKgsMvM8Nxl3L1MqWidiHwsCLJgUx5TyPTGs', PARAM_TEXT
    ));


    $settings->add(new admin_setting_configtext(
            'local_pubsub/subscriptionkey', 'Subscription key', 'Subscription key', 'b83bc53fb3d046008703dddddeb5e0fd', PARAM_TEXT
    ));


    $settings->add(new admin_setting_configtext(
            'local_pubsub/subscription', 'Subscription', 'Subscription', 'FRONT', PARAM_TEXT
    ));


    $settings->add(new admin_setting_configtext(
            'local_pubsub/topic', 'Topic', 'Topic', 'backcambiocatalogocursos', PARAM_TEXT
    ));


    $settings->add(new admin_setting_configtext(
            'local_pubsub/endpointcursos', 'Endpoint Cursos', 'Endpoint Cursos', 'https://qaapimeus2apiadh001.azure-api.net/integracion/cursos/', PARAM_TEXT
    ));


    $settings->add(new admin_setting_configtext(
            'local_pubsub/rutaarchivombz', 'Ruta archivo .mbz', 'Ruta archivo .mbz', '', PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
            'local_pubsub/coursename', 'Course name', 'Course name', 'ProductoCurso', PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
            'local_pubsub/courseshortname', 'Course short name', 'Course short name', 'CodigoCurso', PARAM_TEXT
    ));


    $settings->add(new admin_setting_configtext(
            'local_pubsub/coursecategory', 'Course category', 'Course category', '', PARAM_TEXT
    ));
    
    /*config session*/
    $settings->add(new admin_setting_configtext(
            'local_pubsub/conexionaltasession', 'Conexión string Alta sesiones', 'Conexión string Alta sesiones', 
            'Endpoint=https://qaeus2sbusintcap001.servicebus.windows.net/;SharedAccessKeyName=FRONT;SharedAccessKey=3hk8b8mgwodxveRVLYrIQkR7JXc/oxOZGfvXbQPsplM=', 
            PARAM_TEXT
    ));
    
    $settings->add(new admin_setting_configtext(
            'local_pubsub/topicaltasession', 'Topic alta sesión', 'Topic alta sesión', 
            'backaltasesion', 
            PARAM_TEXT
    ));
    
    
    $settings->add(new admin_setting_configtext(
            'local_pubsub/endpointsession', 'End point de sesión', 'End point de sesión', 
            'https://qaapimeus2apiadh001.azure-api.net/integracion/sesiones/', 
            PARAM_TEXT
    ));
    /*end config session*/

    /*config participantes por sesion*/
    
    $settings->add(new admin_setting_configtext(
            'local_pubsub/conexionupdatesession', 'Conexión string Actulización Sesión', 'Conexión string Actulización Sesión', 
            'Endpoint=https://qaeus2sbusintcap001.servicebus.windows.net/;SharedAccessKeyName=FRONT;SharedAccessKey=aOCbo7Qo+HcX3bCOtf3xwpuAsJsRpb8/8W3Nan9DeAI=', 
            PARAM_TEXT
    ));
    
    $settings->add(new admin_setting_configtext(
            'local_pubsub/topicupdatesession', 'Topic Actulización Sesión', 'Topic Actulización Sesión', 
            'backactualizacionsesion', 
            PARAM_TEXT
    ));
    
    $settings->add(new admin_setting_configtext(
            'local_pubsub/endpointupdatesession', 'End point de sesión', 'End point de sesión', 
            'https://qaapimeus2apiadh001.azure-api.net/integracion/sesiones/{idSesion}/participantes', 
            PARAM_TEXT
    ));
    
     /*config Cierre De sesion*/
     $settings->add(new admin_setting_configtext(
             'local_pubsub/endpointclosesession', 'End point de cierre de sesión', 'End point de cierre de sesión', 
              'https://qaapimeus2apiadh001.azure-api.net/integracion/sesiones/{idSesion}/inscripciones', 
              PARAM_TEXT
     ));
     /** CONFIG Cierre de Sesion */

     /*config suspensión De sesion*/
     $settings->add(new admin_setting_configtext(
        'local_pubsub/endpointsuspendsession', 'End point de suspensión de sesión', 'End point de suspensión de sesión', 
         'https://qaapimeus2apiadh001.azure-api.net/integracion/sesiones/', 
         PARAM_TEXT
     ));
     /** FIN CONFIG suspensión de Sesion */

     /*config participantes por sesión */
     $settings->add(new admin_setting_configtext(
        'local_pubsub/endpointparticipantessession', 'End point de participantes por sesión', 'End point de participantes por sesión', 
        'https://qaapimeus2apiadh001.azure-api.net/integracion/sesiones/{idSesion}/participantes', 
        PARAM_TEXT
     /* FIN config participantes por sesión */
));
     
     $settings->add(new admin_setting_configtext(
        'local_pubsub/endpointcertificado', 'Endoint para obtener diplomas y certificados', '', 
        'https://qaapimeus2apiadh001.azure-api.net/integracion/documento/', 
        PARAM_TEXT
     /* FIN config certificado */
));
      /*config participantes alta facilitadores*/
     
     $settings->add(new admin_setting_configtext(
            'local_pubsub/conexionhighfacilitators', 'Conexión string Alta Facilitadores', 'Conexión string Alta Facilitadores', 
            'Endpoint=https://qaeus2sbusintcap001.servicebus.windows.net/;SharedAccessKeyName=FRONT;SharedAccessKey=6Kyhg1iN4bMQbDaH/12lupTdjC/ZAQbIG+jGprBCX5g=', 
            PARAM_TEXT
    ));
    
    $settings->add(new admin_setting_configtext(
            'local_pubsub/topichighfacilitators', 'Topic Alta Facilitadores', 'Topic Alta Facilitadores', 
            'backregistrofacilitador', 
            PARAM_TEXT
    ));
    
    $settings->add(new admin_setting_configtext(
            'local_pubsub/endpointgetfacilitators', 'End point obtener Facilitadores', 'End point obtener Facilitadores', 
            'https://qaapimeus2apiadh001.azure-api.net/integracion/facilitadores/', 
            PARAM_TEXT
    ));
    
     /*config participantes alta facilitadores*/
    
    
    /*Configuración registrar calificación en huellero */
    $settings->add(new admin_setting_configtext(
            'local_pubsub/ratehuellero', 
            'Registrar calificación en huellero', 
            'Calificación que se guardara como nota máxima de sesión', 
            '100%', 
            PARAM_TEXT
    ));
    /*Configuración registrar calificación en huellero */

    /* Endpoint para reportar los bloqueos de agenda al back */
    $settings->add(new admin_setting_configtext(
        'local_pubsub/bloqueoagenda', 'End point para bloquear la agenda de un facilitador para un período en especifico', 'End point bloqueo Agenda Facilitador', 
        'https://qaapimeus2apiadh001.azure-api.net/integracion/facilitadores/bloqueoagenda', 
        PARAM_TEXT
    ));
    
    //    estatus de sesiones apdobado o suspendido
//    aprobado
    $settings->add(new admin_setting_configtext(
            'local_pubsub/approvedstatus', 
            'Estatus aprobado para ws de sesiones', 
            'Estatus aprobado para ws de sesiones', 
            '100000001', PARAM_TEXT
    ));
//    suspendido
    $settings->add(new admin_setting_configtext(
            'local_pubsub/suspendedstatus', 
            'Estatus suspendido para ws de sesiones', 
            'Estatus suspendido para ws de sesiones', 
            '100000004', PARAM_TEXT
    ));
//    estatus de sesiones apdobado o suspendido

        //Actualiza registro participantes e-learning
        $settings->add(new admin_setting_configtext(
                'local_pubsub/endpointupdatepartielearning', 'End point Actualiza registro participantes e-learning', 'End point Actualiza registro participantes e-learning', 
                'https://qaapimeus2apiadh001.azure-api.net/integracion/elearning/actualizacionparticipantes', 
                PARAM_TEXT
        ));

        //Cierre capacitación e-learning
        $settings->add(new admin_setting_configtext(
                'local_pubsub/endpointcierreparticipantes', 'End point Cierre capacitación e-learning', 'End point Cierre capacitación e-learning', 
                'https://qaapimeus2apiadh001.azure-api.net/integracion/elearning/cierreparticipantes', 
                PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext('local_pubsub/days','Días', 'Dias para aprobar el curso', 30, PARAM_INT));

        $settings->add(new admin_setting_configtext(
            'local_pubsub/batchsize',
            get_string('batchsize', 'local_pubsub'),
            get_string('batchsize_desc', 'local_pubsub'),
            '500',
            PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'local_pubsub/datestart',
            get_string('datestart', 'local_pubsub'),
            get_string('datestart_desc', 'local_pubsub'),
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
            'local_pubsub/dateend',
            get_string('dateend', 'local_pubsub'),
            get_string('dateend_desc', 'local_pubsub'),
            '',
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configcheckbox(
                'local_pubsub/use_batch_processing_for_elearning',
                get_string('use_batch_processing_for_elearning', 'local_pubsub'),
                get_string('use_batch_processing_for_elearning_desc', 'local_pubsub'),
                1
            )
        );

        $settings->add(new admin_setting_configcheckbox(
                'local_pubsub/filter_pendientes_by_timecompleted',
                get_string('filter_pendientes_by_timecompleted', 'local_pubsub'),
                get_string('filter_pendientes_by_timecompleted_desc', 'local_pubsub'),
                1 // Por defecto, estará activado para mantener el comportamiento actual.
            )
        );


        $choices = \local_pubsub\back\inscripcion_elearning_batch::get_processing_modes();
        $default = \local_pubsub\back\inscripcion_elearning_batch::PROCESSING_MODE_SYNC;
        $settings->add(new admin_setting_configselect(
                'local_pubsub/processing_mode',
                get_string('processing_mode', 'local_pubsub'),
                get_string('processing_mode_desc', 'local_pubsub'),
                $default,
                $choices
            )
        );

        $settings->add(new admin_setting_configtext(
                'local_pubsub/parallel_batch_size',
                get_string('parallel_batch_size', 'local_pubsub'),
                get_string('parallel_batch_size_desc', 'local_pubsub'),
                '20',
                PARAM_INT
            )
        );


        // TIPO DE MODALIDAD PRESENCIAL
        $settings->add(new admin_setting_configtext(
                'local_pubsub/tipomodalidadpresencial', 
                'Curso Tipo Modadidad Presencial', 
                'Curso Tipo Modadidad Presencial', 
                '100000000', PARAM_TEXT
        ));

        // TIPO DE MODALIDAD SEMI-PRESENCIAL
        $settings->add(new admin_setting_configtext(
                'local_pubsub/tipomodalidadsemipresencial', 
                'Curso Tipo Modadidad Semi-Presencial', 
                'Curso Tipo Modadidad Semi-Presencial', 
                '100000001', PARAM_TEXT
        ));

        // TIPO DE MODALIDAD DISTANCIA
        $settings->add(new admin_setting_configtext(
                'local_pubsub/tipomodalidaddistancia', 
                'Curso Tipo Modadidad Distancia', 
                'Curso Tipo Modadidad Distancia', 
                '100000002', PARAM_TEXT
        ));

        // TIPO DE MODALIDAD DISTANCIA ELEARNING
        $settings->add(new admin_setting_configtext(
                'local_pubsub/modalidaddistanciaelearning', 
                'Curso Modadidad Distancia Elearning', 
                'Curso Modadidad Distancia Elearning', 
                '201320000', PARAM_TEXT
        ));
        
        // TIPO DE MODALIDAD DISTANCIA streaming
        $settings->add(new admin_setting_configtext(
                'local_pubsub/modalidaddistanciastreaming', 
                'Curso Modadidad Distancia streaming', 
                'Curso Modadidad Distancia streaming', 
                '201320001', PARAM_TEXT
        ));

        // TIPO DE MODALIDAD DISTANCIA mobile
        $settings->add(new admin_setting_configtext(
                'local_pubsub/modalidaddistanciamobile', 
                'Curso Modadidad Distancia mobile', 
                'Curso Modadidad Distancia mobile', 
                '201320002', PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
                'local_pubsub/rutaelearningmbz', 'Ruta archivo .mbz Curso Elearning', 'Ruta archivo .mbz Curso Elearning', '', PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
                'local_pubsub/rutastreamingmbz', 'Ruta archivo .mbz Curso Streamning', 'Ruta archivo .mbz Curso Streamning', '', PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtext(
                'local_pubsub/rutamobilembz', 'Ruta archivo .mbz Curso Mobile', 'Ruta archivo .mbz Curso Mobile', '', PARAM_TEXT
        ));

        $settings->add(new admin_setting_configcheckbox(
                'local_pubsub/curso_presencial_active', 
                get_string('curso_presencial_active', 'local_pubsub'), 
                get_string('curso_presencial_active', 'local_pubsub'), 
                1)
        );

        $settings->add(new admin_setting_configcheckbox(
                'local_pubsub/curso_semi_presencial_active', 
                get_string('curso_semi_presencial_active', 'local_pubsub'), 
                get_string('curso_semi_presencial_active', 'local_pubsub'), 
                1)
        );

        $settings->add(new admin_setting_configcheckbox(
                'local_pubsub/curso_distancia_elearning_active', 
                get_string('curso_distancia_elearning_active', 'local_pubsub'), 
                get_string('curso_distancia_elearning_active', 'local_pubsub'), 
                1)
        );

        $settings->add(new admin_setting_configcheckbox(
                'local_pubsub/curso_distancia_streaming_active', 
                get_string('curso_distancia_streaming_active', 'local_pubsub'), 
                get_string('curso_distancia_streaming_active', 'local_pubsub'), 
                1)
        );

        $settings->add(new admin_setting_configcheckbox(
                'local_pubsub/curso_distancia_mobile_active', 
                get_string('curso_distancia_mobile_active', 'local_pubsub'), 
                get_string('curso_distancia_mobile_active', 'local_pubsub'), 
                1)
        );

        $settings->add(new admin_setting_configtext(
                'local_pubsub/toemailreport', // This is the reference you will use to your configuration
                'Correo de destino para envió de reporte', // This is the friendly title for the config, which will be displayed
                'Correo de destino para envió de reporte', // This is helper text for this config field
                '', // This is the default value
                PARAM_TEXT // This is the type of Parameter this config is
        ));



}
