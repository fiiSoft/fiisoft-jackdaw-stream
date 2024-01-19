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
    
    public static function cannotAddOperationToStartedStream(): JackdawException
    {
        return InvalidOperationException::create('Cannot add operation to a stream that has already started');
    }
    
    public static function cannotAddOperationToFinalOne(): JackdawException
    {
        return InvalidOperationException::create('You cannot add another operation to the final one');
    }
}