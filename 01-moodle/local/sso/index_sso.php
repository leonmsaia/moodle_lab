<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/sso/index_sso.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/sso/index_sso.php'));
$PAGE->set_title('Login');
$PAGE->set_heading('Login');


// $PAGE->set_pagelayout('embedded');

$mform = new \local_sso\form\login_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));
} else if ($data = $mform->get_data()) {
    $username = $data->username;
    $password = $data->password;

    $solo_numero = explode('-', $username)[0];

    $short_username = strtok($solo_numero, '-');
    $login_object = new \local_sso\login();

    $get_datas = $DB->get_records_sql("SELECT * FROM {user} WHERE username like '$short_username%'");

    $user = end($get_datas);

    if (!empty($user)) {

        $validate_login = $login_object->request_login_sso($short_username, $password);

        if (!empty($validate_login)) {

            if (!empty($validate_login['response'])) {

                if (array_key_exists('error', $validate_login['response'])) {
                    echo $OUTPUT->header();
                    echo $OUTPUT->notification('Credenciales inválidas');
                    $mform->display();
                    echo $OUTPUT->footer();
                    exit;
                } else {
                    $user_obj = get_complete_user_data('username', $user->username);
                    $login_user = complete_user_login($user_obj);

                    if ($login_user) {
                        redirect(new \moodle_url('/'));
                    } else {
                        echo $OUTPUT->header();
                        echo $OUTPUT->notification('error al iniciar sesión');
                        $mform->display();
                        echo $OUTPUT->footer();
                        exit;
                    }
                }
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
