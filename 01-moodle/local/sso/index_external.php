<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/sso/index_external.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/sso/index_external.php'));
$PAGE->set_title('Login');
$PAGE->set_heading('Login');


// $PAGE->set_pagelayout('embedded');

$mform = new \local_sso\form\login_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));
} else if ($data = $mform->get_data()) {
    $username = $data->username;
    $password = $data->password;

    // $solo_numero = explode('-', $username)[0];

    // $short_username = strtok($solo_numero, '-');
    $login_object = new \local_sso\login();
    

    // $get_datas = $DB->get_records_sql("SELECT * FROM {user} WHERE username like '$short_username%'");

    // $user = end($get_datas);

    if (!empty($username) && !empty($password)) {

        $validate_login = $login_object->request_login_external($username, $password);

        

        // error_log('==================validate_login==========');
        // error_log(print_r($validate_login, true));
        // error_log('==================validate_login==========');


        if (!empty($validate_login)) {


            if (isset($validate_login["success"])) {

                    if ($validate_login["success"] == true) {
                        $payload = $login_object->sso_encrypt([
                            'username' => 'admin',
                            'password' => 'Salgado852046.',
                            'timestamp' => time()
                        ]);

                        redirect(get_config('local_sso', 'url_moodle') . '/local/sso/login_external.php?payload=' . urlencode($payload));
                        // redirect('http://localhost:1032' . '/local/sso/login_external.php?username=' . $username . '&password=' . $password);
                    } else {
                        echo $OUTPUT->header();
                        echo $OUTPUT->notification('error al iniciar sesión');
                        $mform->display();
                        echo $OUTPUT->footer();
                        exit;
                    }
            } else {
                echo $OUTPUT->header();
                echo $OUTPUT->notification('Credenciales inválidas');
                $mform->display();
                echo $OUTPUT->footer();
                exit;
            }
        } else {
            echo $OUTPUT->header();
            echo $OUTPUT->notification('Credenciales inválidas');
            $mform->display();
            echo $OUTPUT->footer();
            exit;
        }
        
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->notification('Credenciales inválidas');
        $mform->display();
        echo $OUTPUT->footer();
        exit;
    }
} else {
    echo $OUTPUT->header();
    $mform->display();
    echo $OUTPUT->footer();
}
