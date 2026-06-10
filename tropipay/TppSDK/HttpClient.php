<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class HttpClient 
{
    /**
     * @description Convert ["Key: Value"] headers to ["Key" => "Value"] associative array
     * @param       array $headers_raw
     * @return      array
     */
    private function parse_headers_array(array $headers_raw) {
        $parsed = array();
        foreach ($headers_raw as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $parsed[trim($parts[0])] = trim($parts[1]);
            } else {
                $parsed[$header] = '';
            }
        }
        return $parsed;
    }

    /**
     * @description Make HTTP-GET call using WordPress HTTP API
     * @param       $url
     * @param       array $params
     * @param       array|null $headers
     * @return      array containing 'error' (string/false) and 'data' (array/null)
     */
    public function get($url, array $params, array $headers = null) {
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }

        $args = array(
            'timeout'     => 30,
            'redirection' => 10,
            'httpversion' => '1.0',
            'headers'     => !empty($headers) ? $this->parse_headers_array($headers) : array()
        );

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return array(
                'error' => $response->get_error_message(),
                'data'  => null
            );
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        return array(
            'error' => false,
            'data'  => $decoded
        );
    }

    /**
     * @description Make HTTP-POST call using WordPress HTTP API
     * @param       $url
     * @param       array $params
     * @param       array|null $headers
     * @return      array containing 'error' (string/false) and 'data' (array/null)
     */
    public function tpost($url, array $params, array $headers = null) {
        if (empty($headers)) {
            $headers_parsed = array("Content-Type" => "application/json");
        } else {
            $headers_parsed = $this->parse_headers_array($headers);
        }

        // If content-type is json, encode the body
        $has_json_content = false;
        foreach ($headers_parsed as $key => $val) {
            if (strtolower($key) === 'content-type' && strpos(strtolower($val), 'json') !== false) {
                $has_json_content = true;
                break;
            }
        }

        $body = $has_json_content ? json_encode($params) : $params;

        $args = array(
            'method'      => 'POST',
            'timeout'     => 30,
            'redirection' => 10,
            'httpversion' => '1.0',
            'headers'     => $headers_parsed,
            'body'        => $body
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return array(
                'error' => $response->get_error_message(),
                'data'  => null
            );
        }

        $body_response = wp_remote_retrieve_body($response);
        $decoded = json_decode($body_response, true);

        return array(
            'error' => false,
            'data'  => $decoded
        );
    }

    /**
     * @description Make HTTP-PUT call using WordPress HTTP API
     * @param       $url
     * @param       array $params
     * @return      string|false Response body or false on error
     */
    public function tHTTPPut($url, array $params) {
        $args = array(
            'method'  => 'PUT',
            'timeout' => 30,
            'body'    => $params
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        return wp_remote_retrieve_body($response);
    }

    /**
     * @description Make HTTP-DELETE call using WordPress HTTP API
     * @param       $url
     * @param       array $params
     * @return      string|false Response body or false on error
     */
    public function tHTTPDelete($url, array $params) {
        $args = array(
            'method'  => 'DELETE',
            'timeout' => 30,
            'body'    => $params
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        return wp_remote_retrieve_body($response);
    }
}
