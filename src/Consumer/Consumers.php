<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Internal\Check;

final class Consumers
{
    /**
     * @param Consumer|callable $consumer
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
}