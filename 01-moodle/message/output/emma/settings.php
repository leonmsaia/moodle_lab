<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {   
    $url = new moodle_url('/message/output/emma/view.php');
    $link = html_writer::link($url, 'Reportes EMMA');
    $settings->add(new admin_setting_heading('view', '', $link));

    $settings->add(new admin_setting_configtext('message_emma/emma_wsdl'        ,get_string('emma_wsdl','message_emma')         ,get_string('emma_wsdl','message_emma')         ,'https://www.emma.cl/WSUI/ws_emma.wsdl'                    , PARAM_TEXT));    
    $settings->add(new admin_setting_configtext('message_emma/emma_webservices' ,get_string('emma_webservices','message_emma')  ,get_string('emma_webservices','message_emma')  ,'https://www.emma.cl/cgi-bin/webservice/WSUI/ws_emma.cgi'  , PARAM_TEXT));
    $settings->add(new admin_setting_configtext('message_emma/idempresa'        ,get_string('idempresa','message_emma')         ,get_string('idempresa','message_emma')         ,649                                                        , PARAM_INT));
    $settings->add(new admin_setting_configtext('message_emma/clave'            ,get_string('clave','message_emma')             ,get_string('clave','message_emma')             ,'2979.ws456hn67xS'                                         , PARAM_TEXT));
    $settings->add(new admin_setting_configtext('message_emma/idcampana'        ,get_string('idcampana','message_emma')         ,get_string('idcampana','message_emma')         ,181279                                                     , PARAM_INT ));
    $settings->add(new admin_setting_configtext('message_emma/idcategoria'      ,get_string('idcategoria','message_emma')       ,get_string('idcategoria','message_emma')       ,165734                                                     , PARAM_INT ));    
}
