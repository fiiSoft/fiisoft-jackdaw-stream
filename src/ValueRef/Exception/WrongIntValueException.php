<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\ValueRef\Exception;

use FiiSoft\Jackdaw\Exception\JackdawException;
use FiiSoft\Jackdaw\ValueRef\IntValue;

final class WrongIntValueException extends JackdawException
{
    public static function noMoreIntegersToIterateOver(): self
    {
        return self::create('The number of integers is not enough to iterate over them');
    }
    
    public static function invalidNumber(IntValue $provider, ?int $number = null): self
    {
        return self::create(
            'Integer value ('.($number ?? $provider->int()).') returned from '.\get_class($provider).' cannot be used'
        );
    }
    
    public static function create(string $message): self
    {
        return new self($message);
    }
}