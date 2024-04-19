<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Exception;

use FiiSoft\Jackdaw\Internal\Helper;

final class UnsupportedValueException extends JackdawException
{
    public static function create(string $message): self
    {
        return new self($message);
    }
    
    /**
     * @param mixed $value
     */
    public static function cannotCastNonTimeObjectToString($value): JackdawException
    {
        return self::create('\DateTimeInterface object is expected, but got '.Helper::describe($value));
    }
}