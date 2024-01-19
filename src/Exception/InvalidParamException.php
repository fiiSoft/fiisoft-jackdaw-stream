<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Exception;

use FiiSoft\Jackdaw\Internal\Helper;

final class InvalidParamException extends JackdawException
{
    public static function create(string $message): self
    {
        return new self($message);
    }
    
    public static function byName(string $name): self
    {
        return new self('Invalid param '.$name);
    }
    
    /**
     * @param mixed $value
     */
    public static function describe(string $name, $value): self
    {
        return new self('Invalid param '.$name.' - '.Helper::describe($value));
    }
}