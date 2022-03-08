<?php

namespace FiiSoft\Jackdaw\Handler\Logger;

interface ErrorLogger
{
    /**
     * Log errors caught during processing of stream.
     *
     * @param \Throwable $error caught error
     * @param mixed $value value of element processed when error occured
     * @param string|int|mixed $key key of element processed when error occured
     * @return void
     */
    public function log(\Throwable $error, $value, $key): void;
}