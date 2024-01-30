<?php

namespace ApironeApi;

class LoggerWrapper 
{
    static $loggerInstance;

    static $debugMode;

    public static function setLogger($logger, $debug = false)
    {
        if (is_object($logger) && method_exists($logger, 'write')) {
            self::$loggerInstance = $logger;
            self::$debugMode = $debug;
        } 
        else {
            throw new \InvalidArgumentException('Invalid logger');
        }
    }

    public static function debug($message, $context = [])
    {
        self::log('debug', $message, $context);
    }
    public static function error($message, $context = [])
    {
        self::log('error', $message, $context);
    }

    protected static function log($level, $message, $context = array())
    {
        if ($level == 'debug' && !self::$debugMode) {
            return;
        }

        $replace = self::prepareContext($context);
        $message = strip_tags($message);

        if (!empty($replace)) {
            $message = self::prepareMessage($replace, $message);
        }

        self::$loggerInstance->write(strtoupper($level) . ': ' . $message);
    }

    public static function callbackDebug($message, $context = [])
    {
        $debug = sprintf('Callback request from %s: %s', $_SERVER['REMOTE_ADDR'] , $message);

        self::debug($debug, $context);
    }

    public static function callbackError($message, $context = [])
    {
        $error = sprintf('Callback request from %s with error: %s', $_SERVER['REMOTE_ADDR'] , $message);

        self::error($error, $context);
    }

    protected static function prepareMessage($replace, $message)
    {
        return $message . ' ' . print_r(json_encode($replace, JSON_UNESCAPED_LINE_TERMINATORS), true);
    }

    protected static function prepareContext($context)
    {
        if (!$context) {
            return;
        }
        if (is_string($context)) {
            $context = json_decode($context);
        }

        return $context;
    }
}
