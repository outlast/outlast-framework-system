<?php
    /**
     * This library contains useful methods for dealing with URLs.
     * @author Aron Budinszky <aron@outlast.hu>
     * @version 3.0
     * @package Library
     **/

    class zajlib_url extends zajLibExtension {

        /**
         * Returns true or false depending on whether the passed string is a valid http URL.
         * @param string $url The url to be parsed
         * @param boolean $allow_spaces Allow spaces in query string. This will allow spaces in query string, but also in the url. True url-encoded strings should not require this since spaces are %20.
         * @return bool True if a valid url. False otherwise.
         **/
        function valid($url, $allow_spaces = true) {
            if ($allow_spaces) {
                return (boolean)preg_match('/^((https?|ftp):)?\/\/[^\s\/$.?#].[\S ]*$/i', $url);
            } else {
                return (boolean)preg_match('/^((https?|ftp):)?\/\/[^\s\/$.?#].[^\s]*$/i', $url);
            }
        }

        /**
         * Returns the domain without any subdomains for the given url. For example, for foo.bar.www.youtube.com it will return youtube.com.
         * @param string $url The url to parse.
         * @return string The domain portion of the url.
         **/
        function get_domain($url) {
            // Get my hostname
            $hostname = parse_url($url, PHP_URL_HOST);
            // Get my domain match
            $hdata = explode('.', $hostname);
            $hc = count($hdata);

            // Return my proper match
            return $hdata[$hc - 2].'.'.$hdata[$hc - 1];
        }

        /**
         * Returns the subdomain, but excludes www. This is useful because users usually think www.news.domain.com is the same as news.domain.com and domain.com is the same as www.domain.com.
         * @param string $url The url to parse.
         * @return string The subdomain portion of the url.
         **/
        function get_subdomain($url) {
            // Get my hostname
            $hostname = parse_url($url, PHP_URL_HOST);
            // Get my subdomain match
            preg_match('/^(www.)*(.*)(\..*){2}/', $hostname, $matches);

            // Return my proper match
            return $matches[2];
        }

        /**
         * Get the request path for a url.
         * @param string $url Any url.
         * @return string Return the request path which will not include the baseurl or query string.
         */
        function get_requestpath($url) {
            $url = parse_url($url, PHP_URL_PATH);
            $url = str_ireplace($this->ofw->basefolder, '', $url);
            $url = preg_replace('~/+~', '/', $url);

            return trim($url, '/').'/';
        }

        /**
         * Generates a friendly url based on an input string.
         * @param string $title Any string such as a name or title.
         * @return string The string converted to a url-friendly format (no accents, trimmed, no spaces)
         **/
        function friendly($title) {
            // convert accents and trim
            $title = mb_strtolower(trim($this->ofw->lang->convert_eng($title)));
            // remove any remaining non-alpha numeric
            $title = preg_replace("/[^a-z0-9 ]/", "", $title);
            // remove spaces
            $title = str_ireplace(' ', '-', $title);

            // return trimmed
            return $title;
        }

        /**
         * Get ready for query string by adding a ? or & to the url.
         * @param string $url Any url.
         * @return string Will return a url that is definitely ready to append a query string.
         */
        function querymode($url) {
            // Does it have a ? already?
            if (strstr($url, '?')) {
                return $url.'&';
            } else {
                return $url.'?';
            }
        }

    }