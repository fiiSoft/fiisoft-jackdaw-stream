<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Exception;

final class StreamExceptionFactory
{
    public static function invalidModeForPutInOperation(): JackdawException
    {
        return InvalidParamException::create('Only simple VALUE or KEY mode is supported');
    }
    
    public static function feedOperationCanHandleStreamPipeOnly(): JackdawException
    {
        return InvalidParamException::create('Only StreamPipe is supported');
    }
    
    public static function dispatchOperationCannotHandleLoops(): JackdawException
    {
        return InvalidParamException::create('Looped message sending is not supported in Dispatch operation');
    }
    
    public static function forkOperationRequiresForkCollaborator(): JackdawException
    {
        return InvalidParamException::create('Only ForkCollaborator prototype is supported');
    }
    
    public static function cannotExecuteStreamMoreThanOnce(): JackdawException
    {
        return InvalidOperationException::create('Stream can be executed only once!');
    }
    
    public static function cannotReuseUtilizedStream(): JackdawException
    {
        return InvalidOperationException::create('Cannot reuse utilized stream');
    }
}