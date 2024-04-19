<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Exception;

final class OperationFailedException extends JackdawException
{
    public static function create(string $message): self
    {
        return new self($message);
    }
}