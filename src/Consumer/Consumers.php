<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Internal\Check;

final class Consumers
{
    /**
     * @param Consumer|callable|resource $consumer
     * @return Consumer
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
    
        throw new \InvalidArgumentException('Invalid param consumer');
    }
    
    public static function generic(callable $consumer): GenericConsumer
    {
        return new GenericConsumer($consumer);
    }
    
    public static function counter(): Counter
    {
        return new Counter();
    }
    
    public static function printer(int $mode = Check::BOTH): Printer
    {
        return new Printer($mode);
    }
    
    public static function stdout(string $separator = \PHP_EOL, int $mode = Check::VALUE): StdoutWriter
    {
        return new StdoutWriter($separator, $mode);
    }
    
    /**
     * @param resource $resource
     * @param int $mode
     * @return ResourceWriter
     */
    public static function resource($resource, int $mode = Check::VALUE): ResourceWriter
    {
        return new ResourceWriter($resource, $mode);
    }
    
    public static function usleep(int $microseconds): Sleeper
    {
        return new Sleeper($microseconds);
    }
}