<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Consumer\Adapter\MemoWriterAdapter;
use FiiSoft\Jackdaw\Consumer\Adapter\ReducerAdapter;
use FiiSoft\Jackdaw\Consumer\Reference\ChangeIntRef;
use FiiSoft\Jackdaw\Consumer\Reference\RefKey;
use FiiSoft\Jackdaw\Consumer\Reference\RefValue;
use FiiSoft\Jackdaw\Consumer\Reference\RefValueKey;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Memo\MemoWriter;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\ValueRef\IntProvider;

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
        
        if ($consumer instanceof MemoWriter) {
            return new MemoWriterAdapter($consumer);
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
        return new RefValue($value);
    }
    
    /**
     * @param mixed $key REFERENCE
     */
    public static function sendKeyTo(&$key): Consumer
    {
        return new RefKey($key);
    }
    
    /**
     * @param mixed $value REFERENCE
     * @param mixed $key REFERENCE
     */
    public static function sendValueKeyTo(&$value, &$key): Consumer
    {
        return new RefValueKey($value, $key);
    }
    
    public static function idle(): Consumer
    {
        return new Idle();
    }
    
    /**
     * @param int|null $variable REFERENCE
     * @param IntProvider|\Traversable<int>|iterable<int>|callable|int $value
     */
    public static function changeIntBy(?int &$variable, $value): Consumer
    {
        return ChangeIntRef::create($variable, $value);
    }
}