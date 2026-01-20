<?php

namespace local_pubsub;

defined('MOODLE_INTERNAL') || die();

global $CFG, $DB, $OUTPUT, $PAGE, $COURSE;

//require_once(dirname(__FILE__).'/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_once ($CFG->dirroot . '/local/pubsub/vendor/autoload.php');

use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\ServiceBus\Models\ReceiveMessageOptions;
use Exception;

class sistema_get {

    public function __construct() {

        return 0;
    }

    static public function get_message() {

        $conectionstring = get_config('local_pubsub', 'conectionstring');
        $subscription = get_config('local_pubsub', 'subscription');
        $topic = get_config('local_pubsub', 'topic');

//verifico que los campos no sean nulos
        if (strlen($conectionstring) < 2) {
            print_error(get_string('conectionstring-empty', 'local_pubsub'));
        }

        if (strlen($subscription) < 2) {
            print_error(get_string('subscription-empty', 'local_pubsub'));
        }

        if (strlen($topic) < 2) {
            print_error(get_string('topic-empty', 'local_pubsub'));
        }

        $connectionString = $conectionstring;
        $serviceBusRestProxy = ServicesBuilder::getInstance()->createServiceBusService($connectionString);

        try {
            // Set receive mode to PeekLock (default is ReceiveAndDelete)
            $options = new ReceiveMessageOptions();
            $options->setPeekLock();

            // Get message.
            $message = $serviceBusRestProxy->receiveSubscriptionMessage($topic, $subscription, $options);

            if ($message == null)
                return false;


            $body_msg = json_decode($message->getBody());

            //Busco con un get los datos del curso en el backend			
            $cURLConnection = curl_init();
            curl_setopt($cURLConnection, CURLOPT_URL, get_config('local_pubsub', 'endpointcursos') . $body_msg->ID);
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
                'Authorization: ' . get_config('local_pubsub', 'tokenapi'),
                'Ocp-Apim-Subscription-Key: ' . get_config('local_pubsub', 'subscriptionkey')
            ));

            $respuesta = curl_exec($cURLConnection);

            curl_close($cURLConnection);
            $serviceBusRestProxy->deleteMessage($message);

            $respuesta = json_decode($respuesta, true);

            $ret = new \stdClass();
            $ret->mensaje = $respuesta;
            $ret->publicador = $body_msg->Publicador;
            $ret->guid = $body_msg->ID;
            $ret->accion = $body_msg->Action;

            return $ret;

            // echo "MessageID: ".$message->getMessageId()."<br />";
        } catch (ServiceException $e) {
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // https://docs.microsoft.com/rest/api/storageservices/Common-REST-API-Error-Codes
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code . ": " . $error_message . "<br />";
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    static public function get_session() {

        $conectionstring = get_config('local_pubsub', 'conexionaltasession');
        $subscription = get_config('local_pubsub', 'subscription');
        $topic = get_config('local_pubsub', 'topicaltasession');

        if (empty($conectionstring)) {
            //TODO add string (JSALGADO)
            print_error('error empty conexionaltasession');
        }

        if (empty($subscription)) {
            //TODO add string (JSALGADO)
            print_error(get_string('subscription-empty', 'local_pubsub'));
        }

        if (empty($topic)) {
            //TODO add string (JSALGADO)
            print_error('error empty topicaltasession');
        }

        $serviceBusRestProxy = ServicesBuilder::getInstance()->createServiceBusService($conectionstring);

        try {
            // Set receive mode to PeekLock (default is ReceiveAndDelete)
            $options = new ReceiveMessageOptions();
            $options->setPeekLock();

            // Get message.
            $message = $serviceBusRestProxy->receiveSubscriptionMessage($topic, $subscription, $options);
//            echo "<br>--------------------<br>";
//            echo print_r($message, true);
//            echo "<br>--------------------<br>";
            if ($message == null) {
                return false;
            }
            $body_msg = json_decode($message->getBody());

//            //Busco con un get los datos del curso en el backend			
            $cURLConnection = curl_init();
            curl_setopt($cURLConnection, CURLOPT_URL, get_config('local_pubsub', 'endpointsession') . $body_msg->Id);
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
//
            curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
                'Authorization: ' . get_config('local_pubsub', 'tokenapi'),
                'Ocp-Apim-Subscription-Key: ' . get_config('local_pubsub', 'subscriptionkey')
            ));
            $respuesta = curl_exec($cURLConnection);

            curl_close($cURLConnection);
            $serviceBusRestProxy->deleteMessage($message);

            $respuesta = json_decode($respuesta, true);
            $ret = new \stdClass();
            $ret->mensaje = $respuesta;
            $ret->publicador = $body_msg->Publicador;
            $ret->guid = $body_msg->Id;
            $ret->accion = $body_msg->Action;
            $ret->idcurso = $body_msg->IdCurso;
            $ret->idevento = $body_msg->IdEvento;

            return $ret;
        } catch (ServiceException $e) {
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // https://docs.microsoft.com/rest/api/storageservices/Common-REST-API-Error-Codes
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code . ": " . $error_message . "<br />";
            $event = \local_pubsub\event\get_sessions::create(
                            array(
                                'context' => \context_system::instance(),
                                'other' => array(
                                    'error' => $error_message
                                ),
                            )
            );
            $event->trigger();
        } catch (Exception $e) {
            echo $e->getMessage();
            $event = \local_pubsub\event\get_sessions::create(
                            array(
                                'context' => \context_system::instance(),
                                'other' => array(
                                    'error' => $e->getMessage()
                                ),
                            )
            );
            $event->trigger();
        }
    }

    static public function update_session() {

        $conectionstring = get_config('local_pubsub', 'conexionupdatesession');
        $subscription = get_config('local_pubsub', 'subscription');
        $topic = get_config('local_pubsub', 'topicupdatesession');

        if (empty($conectionstring)) {
            print_error('error empty conexionupdatesession');
        }

        if (empty($subscription)) {
            print_error(get_string('subscription-empty', 'local_pubsub'));
        }

        if (empty($topic)) {
            print_error('error empty topicupdatesession');
        }

        $serviceBusRestProxy = ServicesBuilder::getInstance()->createServiceBusService($conectionstring);

        try {
            $options = new ReceiveMessageOptions();
            $options->setPeekLock();
            // Get message.
            $message = $serviceBusRestProxy->receiveSubscriptionMessage($topic, $subscription, $options);
            if ($message == null) {
                return false;
            }
            $body_msg = json_decode($message->getBody());
            $cURLConnection = curl_init();
            curl_setopt($cURLConnection, CURLOPT_URL, get_config('local_pubsub', 'endpointsession') . $body_msg->Id);
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
                'Authorization: ' . get_config('local_pubsub', 'tokenapi'),
                'Ocp-Apim-Subscription-Key: ' . get_config('local_pubsub', 'subscriptionkey')
            ));
            $respuesta = curl_exec($cURLConnection);

            curl_close($cURLConnection);
            $serviceBusRestProxy->deleteMessage($message);

            $respuesta = json_decode($respuesta, true);
            $ret = new \stdClass();
            $ret->mensaje = $respuesta;
            $ret->publicador = $body_msg->Publicador;
            $ret->guid = $body_msg->Id;
            $ret->accion = $body_msg->Action;
            $ret->idcurso = $body_msg->IdCurso;
            $ret->idevento = $body_msg->IdEvento;

            return $ret;
        } catch (ServiceException $e) {
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code . ": " . $error_message . "<br />";
            $event = \local_pubsub\event\get_sessions::create(
                            array(
                                'context' => \context_system::instance(),
                                'other' => array(
                                    'error' => $error_message
                                ),
                            )
            );
            $event->trigger();
        } catch (Exception $e) {
            echo $e->getMessage();
            $event = \local_pubsub\event\get_sessions::create(
                            array(
                                'context' => \context_system::instance(),
                                'other' => array(
                                    'error' => $e->getMessage()
                                ),
                            )
            );
            $event->trigger();
        }
    }

    static public function get_session_participants() {


        $serviceBusRestProxy = ServicesBuilder::getInstance()->createServiceBusService($conectionstring);

        try {
            // Set receive mode to PeekLock (default is ReceiveAndDelete)
            $options = new ReceiveMessageOptions();
            $options->setPeekLock();

            // Get message.
            $message = $serviceBusRestProxy->receiveSubscriptionMessage($topic, $subscription, $options);

            if ($message == null) {
                return false;
            }
            $body_msg = json_decode($message->getBody());

//            get_request_endpoint($endpoint, $tokenapi, $subscriptionkey);

//            echo "<br>--------------------<br>";
//            echo print_r($body_msg, true);
//            echo "<br>--------------------<br>";
        } catch (ServiceException $e) {
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // https://docs.microsoft.com/rest/api/storageservices/Common-REST-API-Error-Codes
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code . ": " . $error_message . "<br />";
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    
    static public function get_facilitators($conectionstring, $topic, $subscription, $endpoint, $tokenapi, $subscriptionkey){
        $serviceBusRestProxy = ServicesBuilder::getInstance()->createServiceBusService($conectionstring);
        try {
            // Set receive mode to PeekLock (default is ReceiveAndDelete)
            $options = new ReceiveMessageOptions();
            $options->setPeekLock();

            // Get message.
            $message = $serviceBusRestProxy->receiveSubscriptionMessage($topic, $subscription, $options);
            if ($message == null) {
                return false;
            }
            $body_msg = json_decode($message->getBody());

            $endpointcomplete = $endpoint.$body_msg->IdInterno;
            //Busco con un get los datos del facilitador en back		
            $respuesta = self::get_request_endpoint($endpointcomplete, $tokenapi, $subscriptionkey);
            
            $serviceBusRestProxy->deleteMessage($message);
            $ret = new \stdClass();
            $ret->mensaje = $respuesta;
            $ret->IdInterno = $body_msg->IdInterno;
            $ret->Identificador = $body_msg->Identificador;
            $ret->Modalidad = $body_msg->Modalidad;
            $ret->TipoFacilitador = $body_msg->TipoFacilitador;
            return $ret;
        } catch (ServiceException $e) {
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // https://docs.microsoft.com/rest/api/storageservices/Common-REST-API-Error-Codes
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code . ": " . $error_message . "<br />";
            //TODO guardar evento
            $event->trigger();
        } catch (Exception $e) {
            echo $e->getMessage();
            //TODO guardar evento
        }
    }

    static public function get_request_endpoint($endpoint, $tokenapi, $subscriptionkey) {
        try {
            //Busco con un get los datos del curso en el backend			
            $cURLConnection = curl_init();
            curl_setopt($cURLConnection, CURLOPT_URL, $endpoint);
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
                'Authorization: ' . $tokenapi,
                'Ocp-Apim-Subscription-Key: ' . $subscriptionkey
            ));
            $respuesta = curl_exec($cURLConnection);

            curl_close($cURLConnection);

            $respuesta = json_decode($respuesta, true);
            return $respuesta;
        } catch (ServiceException $e) {
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // https://docs.microsoft.com/rest/api/storageservices/Common-REST-API-Error-Codes
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code . ": " . $error_message . "<br />";
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

}
