<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Consumer\Adapter\ReducerAdapter;
use FiiSoft\Jackdaw\Consumer\Adapter\RegWriterAdapter;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Registry\RegWriter;

final class Consumers
{
    /**
     * @param ConsumerReady|callable|resource $consumer
     */
    public static function getAdapter($consumer): Consumer
    {
        if ($consumer instanceof Consumer) {
            return $consumer;
        }
    
        if (\is_callable($consumer)) {
            return GenericConsumer::create($consumer);
        }
    
        if (\is_resource($consumer)) {
            return self::resource($consumer);
        }
        
        if ($consumer instanceof Reducer) {
            return new ReducerAdapter($consumer);
        }
        
        if ($consumer instanceof RegWriter) {
            return new RegWriterAdapter($consumer);
        }
    
        throw InvalidParamException::describe('consumer', $consumer);
    }
    
    public static function counter(): Counter
    {
        return new StreamCounter();
    }
    
    public static function printer(int $mode = Check::BOTH): Consumer
    {
        return new Printer($mode);
    }
    
    public static function stdout(string $separator = \PHP_EOL, int $mode = Check::VALUE): Consumer
    {
        return new StdoutWriter($separator, $mode);
    }
    
    /**
     * @param resource $resource
     */
    public static function resource($resource, int $mode = Check::VALUE): Consumer
    {
        return new ResourceWriter($resource, $mode);
    }
    
    public static function usleep(int $microseconds): Consumer
    {
        return new Sleeper($microseconds);
    }
    
    /**
     * @param mixed $value REFERENCE
     */
    public static function sendValueTo(&$value): Consumer
    {
        return new Reference($value, $_);
    }
    
    /**
     * @param mixed $key REFERENCE
     */
    public static function sendKeyTo(&$key): Consumer
    {
        return new Reference($_, $key);
    }
    
    /**
     * @param mixed $value REFERENCE
     * @param mixed $key REFERENCE
     */
    public static function sendValueKeyTo(&$value, &$key): Consumer
    {
        return new Reference($value, $key);
    }
    
    public static function idle(): Consumer
    {
        return new Idle();
    }
}