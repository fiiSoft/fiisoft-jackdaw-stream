<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Reducer\Reducers;
use FiiSoft\Jackdaw\Transformer\Adapter\MapperAdapter;
use FiiSoft\Jackdaw\Transformer\Adapter\PhpSortingFunctionAdapter;
use FiiSoft\Jackdaw\Transformer\Adapter\ReducerAdapter;
use FiiSoft\Jackdaw\Transformer\GenericTransformer;
use FiiSoft\Jackdaw\Transformer\Transformer;
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
    
    /**
     * @dataProvider getDataForTestReducerAdapterWithIterableValue
     */
    public function test_ReducerAdapter_with_iterable_value(Transformer $transformer, $expected): void
    {
        self::assertSame($expected, $transformer->transform([1, 2, 3], 'a'));
    }
    
    public function getDataForTestReducerAdapterWithIterableValue(): array
    {
        $sum = Transformers::getAdapter(Reducers::sum());
        $min = Transformers::getAdapter(Reducers::min());
        $max = Transformers::getAdapter(Reducers::max());
        $minMax = Transformers::getAdapter(Reducers::minMax());
        $average = Transformers::getAdapter(Reducers::average());
        
        return [
            [$sum, 6],
            [$min, 1],
            [$max, 3],
            [$minMax, ['min' => 1, 'max' => 3]],
            [$average, 2],
        ];
    }
    
    public function test_ReducerAdapter_throws_exception_when_value_to_transform_is_not_iterable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Param value must be iterable');
        
        Transformers::getAdapter(Reducers::sum())->transform('a', 5);
    }
    
    public function test_FilterAdapter_throws_exception_when_value_to_transform_is_not_iterable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Param value must be iterable');
        
        Transformers::getAdapter(Filters::greaterThan(1))->transform('a', 5);
    }
    
    public function test_PhpSortingFunctionAdapter_throws_exception_when_value_to_transform_is_not_iterable(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Only arrays can be sorted');
        
        $transformer = new PhpSortingFunctionAdapter('sort');
        $transformer->transform('a', 1);
    }
}