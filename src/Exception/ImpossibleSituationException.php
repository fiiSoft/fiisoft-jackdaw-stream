<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Exception;

final class ImpossibleSituationException extends JackdawException
{
    public static function called(string $method, ?object $object = null): self
    {
        if ($object !== null) {
            $method = \get_class($object).'::'.$method;
        }
        
        return self::create('Method '.$method.' should never be called');
    }
    
    /**
     * @codeCoverageIgnore
     */
    public static function create(string $message): self
    {
        return new self($message);
    }
}