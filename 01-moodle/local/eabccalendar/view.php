<?php


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/classes/eabccalendar_selectform.php');

/**
 * @var moodle_page $PAGE
 * @var core_renderer $OUTPUT;
 */
global $PAGE, $OUTPUT, $CFG, $USER ;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/eabccalendar/view.php'));

$PAGE->requires->css('/local/eabccalendar/assets/css/fullcalendar.css');
$PAGE->requires->css('/local/eabccalendar/assets/css/daygrid.css');
$PAGE->requires->css( '/local/eabccalendar/scss/alertify.css');
$PAGE->requires->css( '/local/eabccalendar/assets/css/main.css');

$userid = optional_param('userid', $USER->id, PARAM_INT);
$PAGE->requires->strings_for_js(array('today', 'month', 'week', 'day','list','locale','cancel','confirm','delete','confirmDelete'), 'local_eabccalendar');
$PAGE->requires->js_call_amd("local_eabccalendar/main", "init",array($userid,$courseid = null));
$PAGE->requires->js_call_amd("local_eabccalendar/select", "init",array($userid));


echo $OUTPUT->header();


if ((isset($SESSION->facilitador) && ($SESSION->facilitador))){
    // Botón para ir a la vista de eabc calendar en 3.5
    $login = new \local_sso\login();

    $validate_external_user = $login->get_user_external_moodle($USER->username);

    if ($validate_external_user["success"] == true) {
        $payload = $login->sso_encrypt([
            'id' => $validate_external_user['id'],
            'timestamp' => time()
        ]);

        $urlbtn = get_config('local_sso', 'url_moodle') . '/local/sso/login_external_menu.php?payload=' . $payload.'&redirectto=/local/eabccalendar/view.php';
        $htmlbtn = html_writer::link($urlbtn, get_string('viewhistory', 'local_eabccalendar'), array('class' => 'btn btn-primary mb-3 ', 'target'=>'_blank'));
        echo $OUTPUT->box($htmlbtn, 'text-right d-block');
    }

    $mform = new simplehtml_form();
    echo '<div id="loader" class="loader" style="display:none"><img class="loader-img" src="assets/img/spinner.gif"></div>';    
    echo '<div id="calendar" style="padding-bottom: 50px;"></div>';
    echo "<div class='d-flex justify-content-center'> Leyenda:  
        <div class='d-inline-block text-dark' style='background-color:#FFC33E' >&nbsp;Bloqueado&nbsp;</div><div class='d-inline-block text-white' style='background-color:#0F77D1' >&nbsp;Planificado&nbsp;</div><div class='d-inline-block text-white' style='background-color:#32720A' >&nbsp;Plan/Contactado&nbsp;</div> 
      </div>";
    $mform->display();
    echo '<div style="padding-bottom: 200px;" ></div>';
}else{
    echo  '<div>'.get_string('accessdenied','local_eabccalendar').'</div>';
}

echo $OUTPUT->footer();
