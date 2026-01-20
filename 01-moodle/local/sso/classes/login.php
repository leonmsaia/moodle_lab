<?php

namespace local_sso;

global $CFG;
include_once $CFG->libdir . '/filelib.php';

class login
{
    private $token;
    public function __construct($token = false)
    {
        
        $this->token = $token;
        
    }

    public function request_validate_sso()
    {
        $headers = array();
        $headers[] = 'Cookie: ' . str_replace(' ', '+', $this->token);

        $curl = new \curl();
        $curl->setHeader($headers);

        $curl->setopt(array(
            'CURLOPT_SSL_VERIFYPEER' => false, // Desactiva la verificación del certificado
            'CURLOPT_SSL_VERIFYHOST' => 0      // Desactiva la verificación del nombre del host
        ));

        $response =  $curl->post(get_config('local_sso', 'url_validate'), '');

        $curldata = null;
        if ($curl->get_errno() === 0) {
            $curldata = json_decode($response);
        }

        return (object) [
            'response' => $curldata,
            // 'http_code' => $http_code     
        ];
    }


    public function request_login_sso($username, $password)
    {

        $curl = new \curl();
        $curl->setHeader(array('Content-type: application/json'));

        $curl->setopt(array(
            'CURLOPT_SSL_VERIFYPEER' => false, // Desactiva la verificación del certificado
            'CURLOPT_SSL_VERIFYHOST' => 0      // Desactiva la verificación del nombre del host
        ));

        $command = json_encode([
            "username" => "uid=$username,cn=users,O=TRABAJADOR",
            "password" => $password
        ]);

        try {
            $response =  $curl->post(get_config('local_sso', 'url_login'), $command);

            $curldata = null;
            if ($curl->get_errno() === 0) {
                $curldata = json_decode($response, true);
            }

            return  [
                'response' => $curldata,
                // 'http_code' => $http_code     
            ];
        } catch (\Throwable $th) {
            return  [];
        }
        
    }

    public function request_login_external($username, $password)
    {

        $curl = new \curl();
        $curl->setHeader(array('Content-type: application/json'));
        // $curl->setopt(array(
        //     'CURLOPT_VERBOSE' => true,
        //     'CURLOPT_SSL_VERIFYPEER' => false, // Desactiva la verificación del certificado
        //     'CURLOPT_SSL_VERIFYHOST' => 0      // Desactiva la verificación del nombre del host
        // ));

        $command = [
            "wstoken" => get_config('local_sso', 'wstoken'),
            "wsfunction" => 'local_sso_validate_login',
            "moodlewsrestformat" => 'json',
            "username" => $username,
            "password" => $password
        ];

        try {

            
            $response = $curl->get(get_config('local_sso', 'url_moodle') . '/webservice/rest/server.php?' , $command);
            // $response = $curl->get('http://moodle45/webservice/rest/server.php?wstoken=3bac5a87d99156e6d4faaea22a415cc7&wsfunction=local_sso_validate_login&moodlewsrestformat=json&username=admin&password=Salgado852046.');


            // error_log('==================response==========');

            // error_log(print_r($response, true));
            // error_log(print_r(get_config('local_sso', 'url_moodle'), true));
            // error_log('==================response==========');
            // echo '<br>============================<br>';
            // echo '<br>============================<br>';
            // echo print_r(get_config('local_sso', 'url_moodle') . '/webservice/rest/server.php?' . http_build_query($command), true);
            // echo '<br>============================<br>';
            // echo print_r($response, true);
            // echo '<br>============================<br>';
            $curldata = null;
            if ($curl->get_errno() === 0) {
                $curldata = json_decode($response, true);
            }

            

            // $curldata = (object) [
            //     'success' => true,
            //     // 'http_code' => $http_code     
            // ];

            return  $curldata;
        } catch (\Throwable $th) {
            return  [];
        }
        
    }
    
    public static function sso_encrypt($data) {
        $key = get_config('local_sso', 'sso_secret');
        $iv = openssl_random_pseudo_bytes(16);
        $ciphertext = openssl_encrypt(json_encode($data), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        // Encode with base64, then replace + and / to avoid spaces and URL issues
        $encoded = base64_encode($iv . $ciphertext);
        // Replace + with -, / with _, and remove = padding
        $encoded = strtr(rtrim($encoded, '='), '+/', '-_');
        return $encoded;
    }

    public static function sso_decrypt($encoded) {
        $key = get_config('local_sso', 'sso_secret');
        // Restore padding if needed
        $remainder = strlen($encoded) % 4;
        if ($remainder) {
            $encoded .= str_repeat('=', 4 - $remainder);
        }
        // Reverse the character replacements
        $encoded = strtr($encoded, '-_', '+/');
        $data = base64_decode($encoded);
        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return json_decode($decrypted, true);
    }

    public function get_user_external_moodle($username)
    {

        $curl = new \curl([
            'blockedhosts' => [],
            'allowexternalurls' => true,
            'debug' => false,
            'ignoresecurity' => true
        ]);
        $curl->setHeader(array('Content-type: application/json'));

        $command = [
            "wstoken" => get_config('local_sso', 'wstoken'),
            "wsfunction" => 'local_sso_validate_get_user_username',
            "moodlewsrestformat" => 'json',
            "username" => $username,
            "strict" => true,
        ];

        try {

            $response = $curl->get(get_config('local_sso', 'url_moodle') . '/webservice/rest/server.php?', $command);

            $curldata = null;
            if ($curl->get_errno() === 0) {
                $curldata = json_decode($response, true);
            }

            return $curldata;
        } catch (\Throwable $th) {
            return [];
        }
    }

    public function is_in_course_user_site($userid)
    {
        global $CFG, $SESSION;

        require_once($CFG->dirroot . '/mod/scorm/locallib.php');
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        
        $is_active = false;
        // $curso_user = enrol_get_users_courses($userid, true, '*');
        global $DB;

        // Obtener los user_enrolments junto con la información de enrol (instancia)
        $sql = "SELECT ue.*, e.courseid as courseid
              FROM {user_enrolments} ue
              JOIN {enrol} e ON ue.enrolid = e.id
             WHERE ue.userid = :userid";
        $params = ['userid' => $userid];
        $curso_user = $DB->get_records_sql($sql, $params);

        
        // error_log("curso_user " . print_r($curso_user, true));

        // Validar si alguno de los cursos tiene timecreated menor a 30 días desde ahora
        $now = time();
        $treintadias = 30 * DAYSECS;
        $days30 = 30 * 24 * 60 * 60;

        $lastAccess = $DB->get_record_sql("SELECT 
            MAX(ula.timeaccess) AS lastaccess
        FROM {user_lastaccess} ula
        WHERE ula.userid = " . $userid);

        // if($lastAccess) {
        //     if( empty($lastAccess->lastaccess)) {
        //         //si nunca entro a ningun curso no esta activo
        //         $is_active = false;
        //     }

        //     if($now - $lastAccess->lastaccess  > $days30) {
        //         //si el ultimo acceso es mayor a 30 dias no esta activo
        //         $is_active = false;
        //     }
        // } 
        
        if(empty($curso_user)) {
            // Si no hay cursos, se considera que no está activo
            $is_active = false;
        } else {
            

            foreach ($curso_user as $curso) {
                $quizzes = $DB->get_records('quiz', array('course' => $curso->courseid));

                    foreach ($quizzes as $quiz) {
                        $attempts = quiz_get_user_attempts($quiz->id, $userid, 'all', true);
                        
                        if (!empty($attempts)) {
                            foreach ($attempts as $attempt) {
                                $fechainicial = $attempt->timestart ?? 1592841740;
                                $referencedate = $attempt->timestart ?? 1592841740;

        
                                $now = time();
                                $finaldateTime = $attempt->timestart +  $days30;

                                //convierto la fecha para buscar el dia, mes y año
                                $finaldateStr = userdate($finaldateTime, '%Y-%m-%d');
                                //despues de encontrear la fecha la fecha le agrego 23:59:59 
                                //para que sea el final del dia y lo convierto a timestamp
                                $finaldate = strtotime($finaldateStr . 'T23:59:59z');
                               

                    //                  echo '<br>==================is_active==========<br>';
                    // echo print_r(intval($referencedate), true);
                    // echo '<br>==================is_active2==========<br>';
                    // echo print_r(intval($finaldateTime), true);
                    // echo '<br>==================is_active3==========<br>';
                    // echo print_r((intval($now) >= intval($finaldateTime)), true);
                    // echo '<br>==================is_active4==========<br>';
                    // echo print_r($now, true);
                    // echo '<br>==================is_active==========<br>';

                                // echo '<br>==================is_active1111==========<br>';
                                // echo print_r((intval($now) >= intval($referencedate)) && (intval($now) <= intval($finaldate)), true);
                                // echo '<br>==================is_active1111==========<br>';

                                if ((intval($now) >= intval($finaldateTime))) {
                                    $is_active = false;
                                } else {
                                    $is_active = true;
                                    break 2;
                                }
                                
                                // Verifica si han pasado más de 30 días desde el inicio del intento
                                // if (($now - $fechainicial) > $days30) {
                                //     // Han pasado más de 30 días
                                //     // continue;
                                // } else {
                                //     $is_active = true;
                                //     break 2;
                                // }
                            }
                            
                        }
                    }
                // if(\local_mutual\front\utils::is_course_elearning($curso->courseid) == true){
                //     // Verificar intentos en quizzes, si es elearning valido scrom y quiz
                //     // Verificar intentos en SCORMs
                //     // $scorms = $DB->get_records('scorm', array('course' => $curso->courseid));

                //     // foreach ($scorms as $scorm) {
                //     //     $tracks = scorm_get_tracks($scorm->id, $userid, 0);
                //     //     if (!empty($tracks)) {
                //     //         $is_active = true;
                //     //         break 2; // Sale de ambos foreach
                //     //     }
                //     // }

                //     $quizzes = $DB->get_records('quiz', array('course' => $curso->courseid));
                //     foreach ($quizzes as $quiz) {
                //         $attempts = quiz_get_user_attempts($quiz->id, $userid, 'all', true);
                //         foreach ($attempts as $attempt) {
                //             $fechainicial = $attempt->timestart;
                //             if ($now - $fechainicial > $days30) {
                //                 // continue;
                //             } else {
                //                 $is_active = true;
                //                 break 2;
                //             }
                //         }
                //     }

                    
                // } else {

                    
                //     //si el curso es presencial o streaming valido solo quiz
                //     $quizzes = $DB->get_records('quiz', array('course' => $curso->courseid));

                //     foreach ($quizzes as $quiz) {
                //         $attempts = quiz_get_user_attempts($quiz->id, $userid, 'all', true);
                        
                //         if (!empty($attempts)) {
                //             foreach ($attempts as $attempt) {
                //                 $fechainicial = $attempt->timestart;
                //                 if ($now - $days30 > $fechainicial ) {
                //                     // continue;
                //                 } else {
                //                     $is_active = true;
                //                     break 2;
                //                 }
                //             }
                            
                //         }
                //     }
                // }
            }   

            //valido si tiene cursos activos, se considera activo si eta en menos de 30 dias
            // foreach ($curso_user as $curso) {
            //     $course_time = ($curso->timestart) > 0 ? $curso->timestart : ($curso->timecreated);
            //     if ($course_time > 0 && ($now - $course_time) <= $days30) {
            //         $is_active = true;
            //         break;
            //     }
            // }

        }
        
        $SESSION->migrado_in_course = $is_active;
        return $is_active;
    }

    public static function average_quizes_scorm($course, $userid){
        global $USER, $DB, $CFG;
        $inquizactige = false;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
        require_once($CFG->libdir . '/datalib.php');
        $quizes = $DB->get_records('quiz', array('course' => $course->id));
        $numattempts = 1;
        $numquizes = 0;
        $geenralattemps = 0;
        foreach($quizes as $quiz) {
            $gradesum = 0;
            if (!$cm = get_coursemodule_from_instance("quiz", $quiz->id, $course->id)) {
                return 0;
            }
            // Get this user's attempts.
            $attempts = quiz_get_user_attempts($quiz->id, $userid, 'all', true);
            if (!empty($attempts)) {
                $inquizactige = true;
                break;
            }
        }
        //evitar division entre cero
        return $inquizactige;
    }

    public static function average_scorm($course, $userid){
        global $USER, $DB, $CFG;
        $inscormactige = false;
        require_once($CFG->dirroot.'/mod/scorm/locallib.php');
        $scroms = $DB->get_records('scorm', array('course' => $course->id));
        $numattempts = 1;
        $numquizes = 0;
        $geenralattemps = 0;
        
        foreach($scroms as $quiz) {
            $gradesum = 0;
            if (!$cm = get_coursemodule_from_instance("scorm", $quiz->id, $course->id)) {
                return 0;
            }
            // Get this user's attempts.
            $attempts = scorm_get_tracks($quiz->id, $userid, $numattempts);
            if (!empty($attempts)) {
                $inquizactige = true;
                break;
            }
        }
        //evitar division entre cero
        return $inquizactige;
    }

    function check_facilitador_summary(){
        global $USER, $SESSION;
    
        $SESSION->facilitador = false;
        $enrol_courses = enrol_get_all_users_courses($USER->id);
    
        foreach($enrol_courses as $enrol_course){
            $context = \context_course::instance($enrol_course->id);
                if (has_capability('local/eabccalendar:view', $context, $USER->id)) { 
                    $SESSION->facilitador = true;
                    break;
            }
        }

        $context = \context_system::instance();
        $roles = get_user_roles($context, $USER->id);

        // Para saber si un rol específico está incluido, por ejemplo el rol con shortname 'teacher':
        $SESSION->rol_incluido = false;
        foreach ($roles as $role) {
            if ($role->shortname === 'contac_callcenter' || $role->shortname === 'holdinguser' || $role->shortname === 'supervisorelas') { // Cambia 'teacher' por el shortname que desees verificar
                $SESSION->rol_incluido = true;
                break;
            }
        }
        
        return $SESSION->facilitador;
    }

    function create_user($data, $exact = null)
    {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');

        
        $username = strtolower(trim($data['username']));

        $user = new \stdClass();
        $user->username = $username;
        $user->password = trim($data['password']);
        $user->firstname = $data['firstname'];
        $user->lastname = $data['lastname'];
        $user->email = $data['email'];
        $user->auth = 'manual';
        $user->confirmed = 1;
        $user->mnethostid = 1;
        $user->deleted = 0;
        $user->timezone = '99';
        
        if($exact) {
            $get_user = $DB->get_record_sql("SELECT * FROM {user} WHERE username = '$username'");
        } else {
            $get_user = $DB->get_record_sql("SELECT * FROM {user} WHERE username like '$username%'");
        }


        if (empty($get_user)) {

            $newuserid = user_create_user($user, false, false);

            if ($newuserid) {
                $user = $DB->get_record('user', ['id' => $newuserid]);

                // Hashea y actualiza la contraseña correctamente
                update_internal_user_password($user, trim($data['password']));


                $user_arr = (array) $createuser;
                $user_arr['apellidomaterno'] = '';
                // \local_pubsub\metodos_comunes::saveApellidoMaterno($createuser, $newuserid);
            }

            $user_obj = get_complete_user_data('id', $newuserid);
            return $user_obj;
        } else {
            $user_obj = get_complete_user_data('id', $get_user->id);
            return $user_obj;
        }
    }

    function mutual_buscar_usuario($username)
    {
        global $CFG;
        $headers = array(
            "Content-type: text/xml",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            // "Content-length: " . strlen($soap_request),
        );;
        // $headers[] = 'Content-type: text/xml';

        $curl = new \curl();
        $curl->setHeader($headers);

        $curl->setopt(array(
            'CURLOPT_SSL_VERIFYPEER' => false, // Desactiva la verificación del certificado
            'CURLOPT_SSL_VERIFYHOST' => 0      // Desactiva la verificación del nombre del host
        ));

        $xmk_body = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cl="http://cl.mutual.srv/">
   <soapenv:Header/>
   <soapenv:Body>
      <cl:obtiene>
         <!--Optional:-->
         <buscaTrabajadorRequest>
            <!--Optional:-->
            <instancia></instancia>
            <rutTrabajador>'.$username.'</rutTrabajador>
         </buscaTrabajadorRequest>
      </cl:obtiene>
   </soapenv:Body>
</soapenv:Envelope>';


        // Enviar el cuerpo XML como string en la petición POST
        $response = $curl->post(get_config('local_sso', 'api_buscar_trabajador'), $xmk_body);

        $curldata = null;
        if ($curl->get_errno() === 0) {
            $clean_xml = str_ireplace(['S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/"', 'S:Envelope', ' xmlnsns0="http//cl.mutual.ws/"', 's:Body', '<>', '</>', 'ns0:', ':'], '', $response);
            $clean_xml = simplexml_load_string($clean_xml);
            $curldata = $clean_xml;
        }

        return (object) [
            'response' => $clean_xml,
            // 'http_code' => $http_code     
        ];
    }

    public function create_user_external_moodle($username, $password, $firstname, $lastname, $email, $raw_password = false, $is_admin = false)
    {
        $curl = new \curl([
            'blockedhosts' => [],
            'allowexternalurls' => true,
            'debug' => false,
            'ignoresecurity' => true
        ]);
        $curl->setHeader(array('Content-type: application/json'));

        $command = [
            "wstoken" => get_config('local_sso', 'wstoken'),
            "wsfunction" => 'local_sso_create_user',
            "moodlewsrestformat" => 'json',
            "username" => $username,
            "password" => $password,
            "firstname" => $firstname,
            "lastname" => $lastname,
            "email" => $email,
        ];

        if($raw_password) {
            $command["raw_password"] = true;
        }
        
        if($is_admin) {
            $command["is_admin"] = true;
        }

        try {

            $response = $curl->get(get_config('local_sso', 'url_moodle') . '/webservice/rest/server.php?', $command);

            $curldata = null;
            if ($curl->get_errno() === 0) {
                $curldata = json_decode($response, true);
            }

            return $curldata;
        } catch (\Throwable $th) {
            return [];
        }
    }

    public static function process_data_row_migrado($line, $columns){
        $data = self::array_user_process_migrado();
        $username = ['', ''];
        foreach ($line as $key => $value) {
            $key = $columns[$key];
            if ($value !== '') {
                switch ($key) {
                    case 'username':
                        $data['username'] = $value;
                        break;
                    case 'firstname':
                        $data['firstname'] = $value;
                        break;
                    case 'password':
                        $data['password'] = $value;
                        break;
                    case 'lastname':
                        $data['lastname'] = $value;
                        break;
                    case 'email':
                        $data['email'] = $value;
                        break;
                    case 'profile_field_empresarazonsocial':
                        $data['profile_field_empresarazonsocial'] = $value;
                        break;
                    case 'profile_field_empresarut':
                        $data['profile_field_empresarut'] = $value;
                        break;
                    case 'profile_field_empresacontrato':
                        $data['profile_field_empresacontrato'] = $value;
                        break;
                    case 'raw_password':
                        $data['raw_password'] = $value;
                        break;
                    default:
                       break;
                }
            } 
        }
        return array(
            'data' => $data, 
        );
    }

    public static function array_user_process_migrado(){
        return array(
            'username' => '',
            'firstname' => '',
            'password' => '',
            'lastname' => '',
            'email' => '',
            'profile_field_empresarazonsocial' => '',
            'profile_field_empresarut' => '',
            'profile_field_empresacontrato' => '',
        );
    }

    public static function start_external_migration(){
        global $USER, $DB, $CFG;
        $curl = new \curl();
        $headers = array();
        $headers[] = 'Content-type: application/json';
        $headers[] = 'Authorization: Bearer ' . get_config('local_sso', 'migracion_bearer');
        $curl->setHeader($headers);

        $url = $CFG->wwwroot . '/local/migration/api/start_external_migration.php';

        $command = json_encode([
            "userid" => $USER->id,
            "destination" => get_config('local_sso', 'url_moodle')
        ]);

        $response =  $curl->post($url, $command);


        $curldata = null;
        if ($curl->get_errno() === 0) {
            $curldata = json_decode($response);
        }

        return (object) [
            'response' => $curldata,
            // 'http_code' => $http_code     
        ];
    }

    public static function preferenceAndRecoverPassword($username, $password){
        global $USER, $DB, $CFG;
        $curl = new \curl();
        $headers = array();
        $command = [
            "wstoken" => get_config('local_sso', 'wstoken'),
            "wsfunction" => 'local_sso_update_password_user',
            "moodlewsrestformat" => 'json',
            "username" => $username,
            "password" => $password
        ];

        try {
            $response = $curl->get(get_config('local_sso', 'url_moodle') . '/webservice/rest/server.php?' , $command);

            $curldata = null;
            if ($curl->get_errno() === 0) {
                $curldata = json_decode($response);
            }

            return (object) [
                'response' => $curldata,
                // 'http_code' => $http_code     
            ];
        } catch (\Exception $th) {
            return [];
        }
    }
}
