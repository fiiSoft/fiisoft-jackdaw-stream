<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Exception;

abstract class JackdawException extends \RuntimeException
{
    protected function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}