<?php

namespace ApironeApi;

class Log {
    public static function debug($message)
    {
        if (empty(Apirone::$LogFilePath))
            return;

        // Implement write data to file
    }
}
