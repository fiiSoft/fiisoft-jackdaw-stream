<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Consumer\Consumers;
use PHPUnit\Framework\TestCase;

final class ConsumersTest extends TestCase
{
    public function test_getAdapter_throws_exception_on_invalid_argument()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Consumers::getAdapter(15);
    }
    
    public function test_GenericConsumer_can_call_callable_with_one_argument()
    {
        $collector = [];
        
        Consumers::generic(static function ($v) use (&$collector) {
            $collector[] = $v;
        })->consume(15, 2);
        
        self::assertSame([15], $collector);
    }
    
    public function test_GenericConsumer_throws_exception_when_callable_accepts_wrong_number_of_params()
    {
        $this->expectException(\LogicException::class);
        
        Consumers::generic(static function ($a, $b, $c) {
            return true;
        })->consume(2, 1);
    }
}