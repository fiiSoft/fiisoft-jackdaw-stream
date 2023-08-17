<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Consumer\Adapter\ReducerAdapter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class Consumers
{
    /**
     * @param Consumer|Reducer|callable|resource $consumer
     */
    public static function getAdapter($consumer): Consumer
    {
        if ($consumer instanceof Consumer) {
            return $consumer;
        }
    
        if (\is_callable($consumer)) {
            return self::generic($consumer);
        }
    
        if (\is_resource($consumer)) {
            return self::resource($consumer);
        }
        
        if ($consumer instanceof Reducer) {
            return new ReducerAdapter($consumer);
        }
    
        throw new \InvalidArgumentException('Invalid param consumer');
    }
    
    public static function generic(callable $consumer): Consumer
    {
        return new GenericConsumer($consumer);
    }
    
    public static function counter(): Counter
    {
        return new Counter();
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
}