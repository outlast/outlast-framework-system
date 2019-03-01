<?php
    /**
     * This library contains useful methods for sending requests to APIs and such.
     * @author Aron Budinszky <aron@outlast.hu>
     * @version 3.0
     * @package Library
     **/

    class zajlib_request extends zajLibExtension {

        /**
         * Sends a request to a specified url via curl. This can be more reliable the file_get_contents but is not supported on all systems.
         * @param string $url The url of the desired destination. You can specify parameters as a query string.
         * @param string|array|bool $params This is optional if parameters are specified via query string in the $url. It can be an array or a query string.
         * @param string $method The method in which to send the request (GET/POST/PUT/DELETE/etc.).
         * @param array|bool $additional_options An associative array of additional curl options. {@link http://www.php.net/manual/en/function.curl-setopt.php} Example: array(CURLOPT_URL => 'http://www.example.com/')
         * @return string Returns a string with the content received.
         **/
        public function curl($url, $params = false, $method = "GET", $additional_options = false) {
            // Check for curl support
            if (!function_exists('curl_init')) {
                return $this->ofw->error("Curl support not installed.");
            }
            // Check to see if url needs to be parsed
            if ($params == false) {
                // parse the url
                $params = parse_url($url);
                if ($params === false) {
                    return $this->ofw->warning("Malformed url ($url). Cannot parse.");
                }
            }
            // Now init and send request
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            if (!is_array($additional_options) || !array_key_exists(CURLOPT_RETURNTRANSFER, $additional_options)) {
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            }
            if (!is_array($additional_options) || !array_key_exists(CURLOPT_SSL_VERIFYPEER, $additional_options)) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            }

            if ($method == 'POST' || $method == 'PUT') {
                if ($method == 'POST') {
                    curl_setopt($curl, CURLOPT_POST, true);
                }

                if ($method == 'PUT') {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
                }
                if ($params && (is_array($params) || is_object($params))) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params, null, '&'));
                }
                if ($params && is_string($params)) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                }
            } else {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            }

            // Set any other options?
            if (is_array($additional_options)) {
                foreach ($additional_options as $key => $value) {
                    curl_setopt($curl, $key, $value);
                }
            }
            // Send and close
            $ret = curl_exec($curl);

            // Check to see if an error occurred
            if ($ret === false) {
                $this->ofw->warning("Curl error (".curl_errno($curl)."): ".curl_error($curl));
            }
            // Close and return
            curl_close($curl);

            return $ret;
        }

        /**
         * Sends a POST request to a specified url, by using the query string as post data. You can also send the POST data in the second parameter. Supports HTTPS.
         * @param string $url The url of the desired destination. Example: post("https://www.example.com/example.php?asdf=1&qwerty=2");
         * @param string|boolean $content The content of the document to be sent.
         * @param boolean $returnheaders If set to true, the headers will be returned as well. By default it is false, so only document content is returned.
         * @param array|boolean $customheaders An array of keys and values with custom headers to be sent along with the content.
         * @param integer|boolean $port The port number. If not set, it will default to 80 or 443 depending on the request type.
         * @return string Returns a string with the content received.
         */
        public function post($url, $content = false, $returnheaders = false, $customheaders = false, $port = false) {
            // Set the content based on url query string
            if ($content === false) {
                // parse the url
                $urldata = parse_url($url);
                if ($urldata === false) {
                    return $this->ofw->warning("Malformed url ($url). Cannot parse.");
                }
                // set as content
                $content = $urldata['query'];
            }
            // Default header, merge my custom into it
            $headers = ['Content-type' => 'application/x-www-form-urlencoded'];
            if (is_array($customheaders)) {
                $headers = array_merge($headers, $customheaders);
            }

            // Now send the POST request and return the result
            return $this->get($url, $content, $returnheaders, $headers, $port, 'POST');
        }

        /**
         * Sends a request via GET or POST method to the specified url via fsockopen. Supports HTTPS.
         * @param string $url The url of the desired destination.
         * @param string $content The content of the document to be sent.
         * @param boolean $returnheaders If set to true, the headers will be returned as well. By default it is false, so only document content is returned.
         * @param array|boolean $customheaders An array of keys and values with custom headers to be sent along with the content.
         * @param string $method Specifies the method by which the content is sent. Can be GET (the default) or POST.
         * @param integer|boolean $port The port number. If not set, it will default to 80 or 443 depending on the request type.
         * @return string Returns a string with the content received.
         * @todo Optimize so that calling post() doesnt run parse_url twice.
         */
        public function get(
            $url,
            $content = "",
            $returnheaders = false,
            $customheaders = false,
            $port = false,
            $method = 'GET'
        ) {
            // add backwards compatiblity for method parameter order (if port is used as method)
            if (!is_numeric($port) && $port !== false) {
                $method = $port;
                $port = false;
            }
            // parse the url
            $urldata = parse_url($url);
            if ($urldata === false) {
                return $this->ofw->warning("Malformed url ($url). Cannot parse.");
            }
            // if port not set, determine automatically based on protocol
            if ($port === false) {
                if ($urldata['scheme'] == "https") {
                    $port = 443;
                } else {
                    $port = 80;
                }
            }
            // now set prefix for https
            if ($urldata['scheme'] == "https") {
                $prefix = "ssl://";
            } else {
                $prefix = "";
            }
            // get method
            if ($method == 'POST') {
                $method = 'POST';
                $path = $urldata['path'];
            } else {
                $method = 'GET';
                $path = $url;
            }
            // assemble my headers (if none given)
            if (empty($customheaders)) {
                $customheaders = [];
            }
            if (!is_array($customheaders)) {
                return $this->ofw->error("Invalid format for custom headers! Must be a key/value array.");
            }
            if (empty($customheaders['Content-type']) && empty($customheaders['content-type']) && empty($customheaders['Content-Type'])) {
                $customheaders['Content-type'] = "text/html";
            }
            // open remote host
            $fp = fsockopen($prefix.$urldata['host'], $port);
            if ($fp === false) {
                return false;
            }
            // send GET or POST request
            fputs($fp, "$method $path HTTP/1.1\r\n");
            fputs($fp, "Host: ".$urldata['host']."\r\n");
            // Send custom headers
            foreach ($customheaders as $key => $value) {
                fputs($fp, "$key: $value\r\n");
            }
            // send the content
            fputs($fp, "Content-length: ".strlen($content)."\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $content."\r\n\r\n");
            // get response
            $buf = '';
            while (!feof($fp)) {
                $buf .= fgets($fp, 102);
            }
            // close connection
            fclose($fp);

            // now split into header and content
            $bufdata = explode("\r\n\r\n", $buf);
            //$headers = $bufdata[0];
            $content = $bufdata[1];

            // now return what was requested
            if ($returnheaders) {
                return $buf;
            } else {
                return $content;
            }
        }

        /**
         * Get HTTP response code for a url.
         * @param string $url The url to fetch.
         * @return integer Returns the HTTP response code.
         */
        public function response_code($url) {
            $headers = get_headers($url);

            return substr($headers[0], 9, 3);
        }

        /**
         * Returns the name of a status code based on the code.
         * @param integer $status_code The status code.
         * @return string The name.
         */
        public function http_status_name($status_code) {
            // Decide which one
            switch ($status_code) {
                case 100:
                    $text = 'Continue';
                    break;
                case 101:
                    $text = 'Switching Protocols';
                    break;
                case 200:
                    $text = 'OK';
                    break;
                case 201:
                    $text = 'Created';
                    break;
                case 202:
                    $text = 'Accepted';
                    break;
                case 203:
                    $text = 'Non-Authoritative Information';
                    break;
                case 204:
                    $text = 'No Content';
                    break;
                case 205:
                    $text = 'Reset Content';
                    break;
                case 206:
                    $text = 'Partial Content';
                    break;
                case 300:
                    $text = 'Multiple Choices';
                    break;
                case 301:
                    $text = 'Moved Permanently';
                    break;
                case 302:
                    $text = 'Moved Temporarily';
                    break;
                case 303:
                    $text = 'See Other';
                    break;
                case 304:
                    $text = 'Not Modified';
                    break;
                case 305:
                    $text = 'Use Proxy';
                    break;
                case 400:
                    $text = 'Bad Request';
                    break;
                case 401:
                    $text = 'Unauthorized';
                    break;
                case 402:
                    $text = 'Payment Required';
                    break;
                case 403:
                    $text = 'Forbidden';
                    break;
                case 404:
                    $text = 'Not Found';
                    break;
                case 405:
                    $text = 'Method Not Allowed';
                    break;
                case 406:
                    $text = 'Not Acceptable';
                    break;
                case 407:
                    $text = 'Proxy Authentication Required';
                    break;
                case 408:
                    $text = 'Request Time-out';
                    break;
                case 409:
                    $text = 'Conflict';
                    break;
                case 410:
                    $text = 'Gone';
                    break;
                case 411:
                    $text = 'Length Required';
                    break;
                case 412:
                    $text = 'Precondition Failed';
                    break;
                case 413:
                    $text = 'Request Entity Too Large';
                    break;
                case 414:
                    $text = 'Request-URI Too Large';
                    break;
                case 415:
                    $text = 'Unsupported Media Type';
                    break;
                case 500:
                    $text = 'Internal Server Error';
                    break;
                case 501:
                    $text = 'Not Implemented';
                    break;
                case 502:
                    $text = 'Bad Gateway';
                    break;
                case 503:
                    $text = 'Service Unavailable';
                    break;
                case 504:
                    $text = 'Gateway Time-out';
                    break;
                case 505:
                    $text = 'HTTP Version not supported';
                    break;
                default:
                    return $this->ofw->warning("Unkown HTTP status code requested ".$status_code);
                    break;
            }

            return $text;
        }

        /**
         * Use http_status_name instead.
         * @deprecated
         */
        public function get_http_status_name($status_code) {
            return $this->http_status_name($status_code);
        }

        /**
         * Is the current request an ajax request? Requires a Javascript library to work properly cross-browser (jquery, moo, etc.)
         * @see For cross-domain ajax detection see http://stackoverflow.com/questions/8163703/cross-domain-ajax-doesnt-send-x-requested-with-header
         * @return boolean Return true if ajax request, false otherwise.
         */
        public function is_ajax() {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Get the client's (remote) IP address.
         * Because of forwarding or clusters, this may actually be different from REMOTE_ADDR.
         * @return string The ip address; can be empty, but generally should not be.
         */
        public function client_ip() {
            return $this->ofw->error->client_ip();
        }

        /**
         * Get the client's (remote) host name address.
         * @return string The host name of the client; can be empty.
         */
        public function client_host() {
            return $_SERVER['REMOTE_HOST'];
        }

        /**
         * Get the client's (remote) user agent string.
         * @return string The host name of the client; can be empty.
         */
        public function client_agent() {
            $possible_agent_sources = [
                'HTTP_USER_AGENT',
                // Header can occur on devices using Opera Mini.
                'HTTP_X_OPERAMINI_PHONE_UA',
                // Vodafone specific header: http://www.seoprinciple.com/mobile-web-community-still-angry-at-vodafone/24/
                'HTTP_X_DEVICE_USER_AGENT',
                'HTTP_X_ORIGINAL_USER_AGENT',
                'HTTP_X_SKYFIRE_PHONE',
                'HTTP_X_BOLT_PHONE_UA',
                'HTTP_DEVICE_STOCK_UA',
                'HTTP_X_UCBROWSER_DEVICE_UA',
            ];
            foreach($possible_agent_sources as $agent_source) {
                if(!empty($_SERVER[$agent_source])) {
                    return $_SERVER[$agent_source];
                }
            }
        }

        /**
         * Get the client's referer string. Do not rely on this as some browsers may not pass this data.
         * @return string The referer of the client; can be empty.
         */
        public function client_referer() {
            if (!empty($_SERVER['HTTP_REFERER'])) {
                return $_SERVER['HTTP_REFERER'];
            } else {
                return '';
            }
        }

    }
