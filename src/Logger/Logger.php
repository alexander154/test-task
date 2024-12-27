<?php

namespace TestTaskMailing\Logger;

use Exception;

class Logger
{
    const LEVEL_INFO = "INFO";
    const LEVEL_ERROR = "ERROR";
    const LEVEL_WARNING = "WARNING";
    const LEVEL_DEBUG = "DEBUG";

    public static function write($data, string $level = self::LEVEL_INFO): void
    {
        if (!in_array($level, [self::LEVEL_DEBUG, self::LEVEL_ERROR, self::LEVEL_INFO, self::LEVEL_WARNING])) {
            $level = self::LEVEL_INFO;
        }

        if ($data instanceof Exception) {
            $string = $level . ":::" . $data->getMessage() . " trace is " . $data->getTraceAsString() . PHP_EOL;
        } else {
            $string = $level . ":::" . $data . PHP_EOL;
        }

        $string = date("d.m.Y H:i:s") . ":::" . $string;

        file_put_contents(__DIR__ . "/../../log/log_custom.txt", $string, FILE_APPEND | LOCK_EX);
    }
}