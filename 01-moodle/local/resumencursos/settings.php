<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {


    $settings = new admin_settingpage('local_resumencursos', 'Configuración Cursos y diplomas');

    // Create 
    $ADMIN->add('localplugins', $settings);

        $settings->add(new admin_setting_configcheckbox(
                'local_resumencursos/active_diploma', 
                'Activar opción de descargar diploma', 
                'Activar opción de descargar diploma', 
                1)
        );

        $settings->add(new admin_setting_configcheckbox(
                'local_resumencursos/active_dpf_summary', 
                'Activar opción de descargar pdf con el resumen de cursos', 
                'Activar opción de descargar pdf con el resumen de cursos', 
                1)
        );

        $settings->add(new admin_setting_configcheckbox(
                'local_resumencursos/allow_download_with_grade',
                get_string('allow_download_with_grade', 'local_resumencursos'),
                get_string('allow_download_with_grade_desc', 'local_resumencursos'),
                0
            )
        );

        $settings->add(new admin_setting_configcheckbox(
                'local_resumencursos/use_external_completion',
                get_string('use_external_completion', 'local_resumencursos'),
                get_string('use_external_completion_desc', 'local_resumencursos'),
                0
            )
        );

}
