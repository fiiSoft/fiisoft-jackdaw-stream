<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\Exception;

use FiiSoft\Jackdaw\Exception\InvalidOperationException;
use FiiSoft\Jackdaw\Exception\JackdawException;

final class PipeExceptionFactory
{
    public static function cannotClonePipeWithNoneEmptyStack(): JackdawException
    {
        return InvalidOperationException::create('Cannot clone Pipe with non-empty stack');
    }
    
    public static function cannotAddOperationToTheFinalOne(): JackdawException
    {
        return InvalidOperationException::create('Cannot add an operation to the final one');
    }
}