<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Exception;

final class UnsupportedValueException extends JackdawException
{
    public static function create(string $message): self
    {
        return new self($message);
    }
}