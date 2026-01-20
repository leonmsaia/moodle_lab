<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class for requests.
 *
 * @package   tool_eabcetlbridge
 * @category  classes
 * @copyright 2024 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_eabcetlbridge;

use curl;
use core\files\curl_security_helper as security_helper;
use core\exception\moodle_exception;
use core\url as moodle_url;
use Exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/filelib.php');

/**
 * Class for requests.
 */
class request {

    /** @var int GET requests */
    const GET = 0;
    /** @var int POST requests */
    const POST = 1;
    /** @var int PUT requests */
    const PUT = 2;

    /** @var int Default method request */
    protected $method;
    /** @var bool true if authentication bearer token is required */
    protected $authrequired;
    /** @var bool true if JSON */
    protected $jsonrequest;
    /** @var bool true if JSON response */
    protected $jsonresponse;
    /** @var string URL */
    protected $url;
    /** @var security_helper */
    protected $securityhelper;
    /** @var array */
    protected $settings = array();

    /** @var mixed Payload */
    protected $payload;

    /** @var mixed Transformed response */
    protected $response = '';
    /** @var bool|string|null Raw response without transformations */
    protected $rawresponse = '';

    /** @var string Token key */
    protected $tokenkey = '';

    /**
     * Constructor
     *
     * @param int $method
     * @param string $url
     * @param bool $authrequired true if authentication bearer token is required
     * @param bool $jsonrequest true if JSON
     * @param bool $jsonresponse true if JSON response
     * @throws moodle_exception on error
     */
    public function __construct($method, $authrequired = false, $jsonrequest = true,
            $jsonresponse = true) {

        $this->method = $method;
        $this->url = get_config('tool_eabcetlbridge', 'externalmoodleurl');
        $this->tokenkey = get_config('tool_eabcetlbridge', 'externalmoodletoken');
        $this->jsonrequest = $jsonrequest;
        $this->jsonresponse = $jsonresponse;
        $this->authrequired = $authrequired;
    }

    /**
     * Standard options used for all curl requests.
     *
     * @return array
     */
    protected function get_curl_options() {
        return array(
            'RETURNTRANSFER' => true,
            'CONNECTTIMEOUT' => 10,
            // Follow redirects with the same type of request when sent 301, or 302 redirects.
            'CURLOPT_POSTREDIR' => 3,
        );
    }

    /**
     * Transform payload before sending request
     *
     * @param mixed $payload
     * @return mixed
     */
    protected function transform_payload($payload) {
        if ($this->jsonrequest) {
            return json_encode($payload);
        }
        return $payload;
    }

    /**
     * Transform response
     *
     * @param bool|string|null $response
     * @return mixed
     */
    protected function transform_response($response) {
        if ($this->jsonresponse && is_string($response)) {
            return json_decode($response);
        }
        return $response;
    }

    /**
     * After validations
     *
     * @param mixed $payload
     * @return void
     */
    protected function after_validations($payload) {
        $this->securityhelper = new security_helper();
        if ($this->securityhelper instanceof \core\files\curl_security_helper_base) {
            $this->settings['securityhelper'] = $this->securityhelper;
        }
        if ($this->securityhelper->url_is_blocked($this->url)) {
            throw new moodle_exception('curlsecurityurlblocked', 'admin');
        }
    }

    /**
     * Post validations
     *
     * @param mixed $response Raw response
     * @param curl $curl
     * @return void
     * @throws moodle_exception on error
     */
    protected function post_validations($response, $curl) : void {
        // Validate CURL connection.
        $info = $curl->get_info();
        $code = $info['http_code'] ?? 0;
        if ($curlerrno = $curl->get_errno()) {
            // CURL connection error.
            throw new Exception('Error CURL: '.$curlerrno);
        } else if ($code != 200) {
            // Unexpected error from server.
            throw new Exception('Error CURL: no 200 ' . $code);
        }
    }

    /**
     * Request
     *
     * @param array $payload
     * @return mixed
     * @throws moodle_exception when curl validation, or {@see self::post_validations} fails
     */
    public function request($payload) {

        // Validate response.
        $this->after_validations($payload);

        $curl = new curl($this->settings);

        if ($this->jsonrequest) {
            $curl->setHeader(array('Content-type: application/json'));
        }

        $options = $this->get_curl_options();

        if ($this->authrequired) {
            $token = $this->tokenkey;
            $payload['wstoken'] = $token;
        }
        $this->payload = $this->transform_payload($payload);

        $response = null;
        if ($this->method == self::GET) {
            $response = $curl->get($this->url, $this->payload, $options);
        } else if ($this->method == self::POST) {
            $response = $curl->post($this->url, $this->payload, $options);
        } else if ($this->method == self::PUT) {
            $response = $curl->put($this->url, $this->payload, $options);
        }

        // Validate response.
        $this->post_validations($response, $curl);

        $this->rawresponse = $response;
        $this->response = $this->transform_response($response);

        return $this->response;

    }

    /**
     * Get response
     *
     * @param bool $rawresponse true to Return raw response instead of transformed
     * @return mixed
     */
    final public function get_response($rawresponse = false) {
        if ($rawresponse) {
            return $this->rawresponse;
        }
        return $this->response;
    }
}
