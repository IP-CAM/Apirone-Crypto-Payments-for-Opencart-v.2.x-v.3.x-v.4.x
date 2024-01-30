<?php

namespace ApironeApi;

require_once (__DIR__ . '/Error.php');

use ApironeApi\Error as Error;

class Request
{
    const API_URL = 'https://apirone.com/api';

    public static function execute($method, $path, $options = array(), $json = false)
    {
        if ($method && $path) {
            $curl_options = array(
                CURLOPT_URL => self::API_URL . $path,
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
                    $curl_options[CURLOPT_URL] .= '?' . self::prepare($options, $json);
                    break;

                case 'post':
                    $curl_options[CURLOPT_POST] = true;
                    $curl_options[CURLOPT_POSTFIELDS] = self::prepare($options, $json);
                    break;

                case 'patch':
                    $curl_options[CURLOPT_POST] = true;
                    $curl_options[CURLOPT_POSTFIELDS] = self::prepare($options, $json);
                    $curl_options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
                    break;
                    
                default:
                    $curl_options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
            }

            $curl_handle = curl_init();
            curl_setopt_array($curl_handle, $curl_options);

            static::logRequest($method, $path, $options, $curl_options);

            $result       = curl_exec($curl_handle);
            $httpHeaderSize = curl_getinfo($curl_handle, CURLINFO_HEADER_SIZE);
            $httpHeaders    = static::parseResponseHeaders(substr((string)$result, 0, $httpHeaderSize));
            $httpBody       = substr((string)$result, $httpHeaderSize);
            $responseInfo   = curl_getinfo($curl_handle);
            $curlError      = curl_error($curl_handle);
            $curlErrno      = curl_errno($curl_handle);
            
            curl_close($curl_handle);
            if ($result === false) {
                static::logCurlError($curlErrno, $curlError);
                return false;
            }

            if ($responseInfo['http_code'] >= 400) {
                $error = new \ApironeApi\Error($responseInfo['http_code'], $httpBody, json_encode($responseInfo));
                static::logResponseError($error);
                return false;
            }

            static::logResponse($responseInfo['http_code'], $path, $httpBody, $httpHeaders);
            return json_decode($httpBody);
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
    protected static function logCurlError($errno, $error)
    {
        switch ($errno) {
            case CURLE_COULDNT_CONNECT:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_OPERATION_TIMEOUTED:
                $message = 'Could not connect to Apirone API. Please check your internet connection and try again.';
                break;
            case CURLE_SSL_CACERT:
            case CURLE_SSL_PEER_CERTIFICATE:
                $message = 'Could not verify SSL certificate.';
                break;
            default:
                $message = 'Unexpected error communicating.';
        }
        $context = ['details' => "Network error [errno $errno]: $error"];
        LoggerWrapper::error($message, $context);
    }

    protected static function logResponseError($error)
    {
        $httpBody = $error->body;

        $message = json_decode($httpBody);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $message = $httpBody;
        }
        if (is_object($message) && property_exists($message, 'message')) {
            $message = $message->message;
        }
        $context['code'] = $error->code;
        $context['info'] = json_decode($error->info);
        LoggerWrapper::error($message, $context);
    }

    protected static function logRequest($method, $path, $options, $curl_options)
    {
        if (LoggerWrapper::$debugMode) {
            $message = 'Send request: ' . $method . ' ' . $path;
            $context = [];
            $context['body'] = static::maskTransferKey($options);
            $context['headers'] = $curl_options[CURLOPT_HTTPHEADER];

            LoggerWrapper::debug($message, $context);
        }
    }

    protected static function logResponse($code, $path, $httpBody, $httpHeaders)
    {
        if (LoggerWrapper::$debugMode) {
            $message = 'Response with code ' . $code . ' from ' . $path . ' received.';
            $context = array();

            if (!empty($httpBody)) {
                $body = json_decode($httpBody, true);
                if (JSON_ERROR_NONE !== json_last_error()) {
                    $body = $httpBody;
                }
                $context['body'] = static::maskTransferKey($body);
            }
            if (!empty($httpHeaders)) {
                $headers = json_decode(json_encode($httpHeaders));
                if (JSON_ERROR_NONE !== json_last_error()) {
                    $headers = $httpHeaders;
                }

                $context['headers'] = $headers;
            }

            LoggerWrapper::debug($message, $context);
        }
    }

    /**
     * Parse response headers to array
     *
     * @param mixed $rawHeaders
     * @return array
     */
    protected static function parseResponseHeaders($rawHeaders)
    {
        $headers = array();
        $key = '';

        foreach (explode("\n", $rawHeaders) as $headerRow) {
            if (trim($headerRow) === '') {
                break;
            }
            $headerArray = explode(':', $headerRow, 2);

            if (isset($headerArray[1])) {
                if (!isset($headers[$headerArray[0]])) {
                    $headers[trim($headerArray[0])] = trim($headerArray[1]);
                } elseif (is_array($headers[$headerArray[0]])) {
                    $headers[trim($headerArray[0])] = array_merge($headers[trim($headerArray[0])], array(trim($headerArray[1])));
                } else {
                    $headers[trim($headerArray[0])] = array_merge(array($headers[trim($headerArray[0])]), array(trim($headerArray[1])));
                }

                $key = $headerArray[0];
            } else {
                if (substr($headerArray[0], 0, 1) === "\t") {
                    $headers[$key] .= "\r\n\t" . trim($headerArray[0]);
                } elseif (!$key) {
                    $headers[0] = trim($headerArray[0]);
                }
            }
        }

        return $headers;
    }

    protected static function maskTransferKey($data)
    {
        if (is_array($data) && array_key_exists('transfer-key', $data)) {
            $data['transfer-key'] = 'xxxxx';
        }

        if (is_object($data) && property_exists($data, 'transfer-key')) {
            $data->{'transfer-key'} = 'xxxxx';
        }

        return $data;
    }
}
