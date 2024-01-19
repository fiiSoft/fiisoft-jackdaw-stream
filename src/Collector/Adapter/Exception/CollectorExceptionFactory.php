<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter\Exception;

use FiiSoft\Jackdaw\Exception\InvalidOperationException;
use FiiSoft\Jackdaw\Exception\JackdawException;

final class CollectorExceptionFactory
{
    public static function cannotSetKeys(object $object): JackdawException
    {
        return InvalidOperationException::create('You cannot assign keys to '.\get_class($object));
    }
}