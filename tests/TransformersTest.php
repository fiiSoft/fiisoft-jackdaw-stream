<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Transformer\Adapter\MapperAdapter;
use FiiSoft\Jackdaw\Transformer\Adapter\ReducerAdapter;
use FiiSoft\Jackdaw\Transformer\GenericTransformer;
use FiiSoft\Jackdaw\Transformer\Transformers;
use PHPUnit\Framework\TestCase;

final class TransformersTest extends TestCase
{
    public function test_getAdapter_can_create_Transformer_from_various_params(): void
    {
        $closure = static fn(int $v): int => $v;
        
        self::assertInstanceOf(GenericTransformer::class, Transformers::getAdapter($closure));
        self::assertInstanceOf(GenericTransformer::class, Transformers::getAdapter(new GenericTransformer($closure)));
        
        self::assertInstanceOf(ReducerAdapter::class, Transformers::getAdapter(Reducers::sum()));
        self::assertInstanceOf(MapperAdapter::class, Transformers::getAdapter(Mappers::reverse()));
    }
    
    public function test_getAdapter_returns_null_when_param_is_null(): void
    {
        self::assertNull(Transformers::getAdapter(null));
    }
    
    public function test_getAdapter_throws_exception_when_param_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param transformer');
        
        Transformers::getAdapter('this is not valid transformer');
    }
    
    public function test_GenericTransformer_can_accept_one_or_two_arguments(): void
    {
        self::assertSame(10, Transformers::getAdapter(static fn(int $v): int => $v * 2)->transform(5, 3));
        
        self::assertSame(8, Transformers::getAdapter(static fn(int $v, int $k): int => $v + $k)->transform(5, 3));
    }
    
    public function test_GenericTransformer_throws_exception_when_callable_requires_wrong_number_of_arguments(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Transformer have to accept 1 or 2 arguments, but requires 0');
        
        Transformers::getAdapter(static fn(): int => 2)->transform(5, 3);
    }
    
    public function test_MapperAdapter(): void
    {
        self::assertSame('5', Transformers::getAdapter(Mappers::toString())->transform(5, 3));
    }
    
    public function test_ReducerAdapter_with_iterable_value(): void
    {
        $transformer = Transformers::getAdapter(Reducers::sum());
        self::assertSame(6, $transformer->transform([1, 2, 3], 'a'));
    }
    
    public function test_ReducerAdapter_throws_exception_when_value_to_transform_is_not_iterable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Param value must be iterable');
        
        Transformers::getAdapter(Reducers::sum())->transform('a', 5);
    }
}