<?php

namespace FiiSoft\Jackdaw\Handler;

interface ErrorHandler
{
    /**
     * @param \Throwable $error cought error
     * @param string|int $key key of element which caused error
     * @param mixed $value value of element which caused error
     * @return bool|null when true then iteration will immediately continue with next element from stream
     *              when false then iteration will finish immediately without throw $error
     *              when null then next handler will be called, and when there are no handlers left,
     *              cought $error will be re-thrown
     */
    public function handle(\Throwable $error, $key, $value): ?bool;
}