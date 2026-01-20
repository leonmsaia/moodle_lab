<?php

require_once('../../config.php');
require_once('lib.php');


$xml = getxml(file_get_contents('php://input'));

$decrypt = decrypt($xml, $CFG->local_cron_key_decrypt);

$xmlParse = format_xml_decrypt($decrypt);

$validate_request = validate_request($xmlParse);

if (!empty($validate_request)) {
    print_r($validate_request);
    exit;
}

if (gettype($xmlParse) == "object") {
    $xmlstr = user_create_enrol_user_xml($xmlParse);
    print_r($xmlstr);
} else {
    $xmlstr = get_string('null_response', 'local_cron');
    $response = new SimpleXMLElement($xmlstr);
    print_r($response->asXML());
}



