<?php

namespace ApironeApi;

require_once (__DIR__ . '/Error.php');

use ApironeApi\Error;

class Request
{
    const API_URL = 'https://apirone.com/api';
    const ERROR = false;

    public static function execute($method, $url, $params = array(), $json = false)
    {
        $error = new \ApironeApi\Error();

        if ($method && $url) {
            $curl_options = array(
                CURLOPT_URL => self::API_URL . $url,
                CURLOPT_HEADER => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_INFILESIZE => Null,
                CURLOPT_HTTPHEADER => array(),
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 10,
            );

            $curl_options[CURLOPT_HTTPHEADER][] = 'Accept-Charset: utf-8';
            $curl_options[CURLOPT_HTTPHEADER][] = 'Accept: application/json';
            $curl_options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';


            switch (strtolower(trim($method))) {
                case 'get':
                    $curl_options[CURLOPT_HTTPGET] = true;
                    $curl_options[CURLOPT_URL] .= '?' . self::prepare($params, $json);
                    break;

                case 'post':
                    $curl_options[CURLOPT_POST] = true;
                    $curl_options[CURLOPT_POSTFIELDS] = self::prepare($params, $json);
                    break;

                case 'patch':
                    $curl_options[CURLOPT_POST] = true;
                    $curl_options[CURLOPT_POSTFIELDS] = self::prepare($params, $json);
                    $curl_options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
                    break;
                    
                default:
                    $curl_options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
            }

            $ch = curl_init();
            curl_setopt_array($ch, $curl_options);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);

            if (curl_errno($ch)) {
                $curl_code = curl_errno($ch);

                $error->add($curl_code, 'CURL ERROR: ' .  curl_strerror($curl_code), json_encode($info));
            }
            curl_close($ch);

            $body = '';
            $parts = explode("\r\n\r\n", $result, 3);

            if (isset($parts[0]) && isset($parts[1])) {
                $body = (($parts[0] == 'HTTP/1.1 100 Continue') && isset($parts[2])) ? $parts[2] : $parts[1];
            }
            if ($info['http_code'] >= 400) {
                $error->add($info['http_code'], $body, json_encode($info));
            }
            if ($error->hasError()){
                return $error;
            }

            return $body;
        }
    }

    public static function prepare($params, $json)
    {
        if (is_string($params)) {
            return $params;
        }
        if ($json) {
            return json_encode($params);
        }
        else {
            return http_build_query($params);
        }
    }

    public static function isResponseError($response)
    {
        return  ($response instanceof \ApironeApi\Error) ? true : false;
    }
}
