<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Reducer\Max;
use FiiSoft\Jackdaw\Reducer\Min;
use FiiSoft\Jackdaw\Reducer\Reducers;
use PHPUnit\Framework\TestCase;

final class ReducersTest extends TestCase
{
    public function test_getAdapter_throws_exception_when_arg_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Reducers::getAdapter(15);
    }
    
    public function test_getAdapter_returns_Reducer_instance_for_some_functions(): void
    {
        self::assertInstanceOf(Min::class, Reducers::getAdapter('min'));
        self::assertInstanceOf(Max::class, Reducers::getAdapter('max'));
    }
    
    public function test_Concat_reducer_accumulates_simple_values_as_string(): void
    {
        $reducer = Reducers::concat();
    
        foreach ([4, 'a', 2, '5', 'z'] as $value) {
            $reducer->consume($value);
        }
        
        self::assertSame('4a25z', $reducer->result());
    }
}