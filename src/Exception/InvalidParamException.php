<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Exception;

use FiiSoft\Jackdaw\Internal\Helper;

final class InvalidParamException extends JackdawException
{
    /**
     * @param mixed $value
     */
    public static function describe(string $name, $value): self
    {
        return self::byName($name.' - '.Helper::describe($value));
    }
    
    public static function byName(string $name): self
    {
        return self::create('Invalid param '.$name);
    }
    
    public static function create(string $message): self
    {
        return new self($message);
    }
}