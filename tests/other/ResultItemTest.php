<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\ResultItem;
use PHPUnit\Framework\TestCase;

final class ResultItemTest extends TestCase
{
    public function test_default_not_null(): void
    {
        $item = ResultItem::createNotFound('a');
        
        self::assertTrue($item->notFound());
        self::assertFalse($item->found());
        
        self::assertSame('a', $item->get());
        self::assertSame(0, $item->key());
        
        self::assertSame(['a'], $item->toArray());
        self::assertSame(['a'], $item->toArrayAssoc());
        
        self::assertSame('"a"', $item->toJson());
        self::assertSame('["a"]', $item->toJsonAssoc());
        
        self::assertSame('a', $item->toString());
        
        self::assertSame([0, 'a'], $item->tuple());
    }
    
    public function test_default_null(): void
    {
        $item = ResultItem::createNotFound();
        
        self::assertTrue($item->notFound());
        self::assertFalse($item->found());
        
        self::assertNull($item->get());
        self::assertNull($item->key());
        
        self::assertSame([], $item->toArray());
        self::assertSame([], $item->toArrayAssoc());
        
        self::assertSame('null', $item->toJson());
        self::assertSame('null', $item->toJsonAssoc());
        
        self::assertSame('', $item->toString());
        
        self::assertSame([], $item->tuple());
    }
    
    public function test_item_found(): void
    {
        $item = ResultItem::createFound(new Item('a', 15));
    
        self::assertTrue($item->found());
        self::assertFalse($item->notFound());
    
        self::assertSame(15, $item->get());
        self::assertSame('a', $item->key());
    
        self::assertSame([15], $item->toArray());
        self::assertSame(['a' => 15], $item->toArrayAssoc());
    
        self::assertSame('15', $item->toJson());
        self::assertSame('{"a":15}', $item->toJsonAssoc());
    
        self::assertSame('15', $item->toString());
    
        self::assertSame(['a', 15], $item->tuple());
    }
    
    public function test_convert_array_to_string(): void
    {
        $item = ResultItem::createFound(new Item(0, [1, 2, 3]));
        
        self::assertSame('1,2,3', $item->toString());
        self::assertSame('1 2 3', $item->toString(' '));
    }
    
    public function test_item_with_array_as_value_always_returns_oryginal_array_and_prevents_its_keys(): void
    {
        $item = ResultItem::createFound(new Item(0, ['a' => 1, 'b' => 2, 'c' => 3]));
        
        self::assertSame('1,2,3', $item->toString());
        self::assertSame('[1,2,3]', $item->toJson());
        self::assertSame('{"a":1,"b":2,"c":3}', $item->toJsonAssoc());
        self::assertSame([1, 2, 3], $item->toArray());
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $item->toArrayAssoc());
    }
    
    public function test_stream_from_non_iterable_item_returns_stream_with_one_element(): void
    {
        $item = ResultItem::createFound(new Item(5, 'foo'));
        
        self::assertSame('"foo"', $item->toJson());
        self::assertSame('{"5":"foo"}', $item->toJsonAssoc());
        
        self::assertSame(1, $item->stream()->count()->get());
        self::assertSame([5 => 'foo'], $item->stream()->toArrayAssoc());
    }
    
    public function test_result_with_non_iterable_item_contains_1_element(): void
    {
        $item = ResultItem::createFound(new Item(5, 'foo'));
    
        self::assertCount(1, $item);
        self::assertSame(1, $item->count());
    }
    
    public function test_item_not_found_contains_1_element_when_default_value_was_provided(): void
    {
        $item = ResultItem::createNotFound('foo');
        
        self::assertCount(1, $item);
        self::assertSame(1, $item->count());
        self::assertSame('foo', $item->get());
        
        self::assertSame(['foo'], $item->stream()->toArray());
    }
    
    public function test_item_not_found_contains_0_elements_when_default_value_was_not_provided(): void
    {
        $item = ResultItem::createNotFound();
        
        self::assertCount(0, $item);
        self::assertSame(0, $item->count());
        self::assertNull($item->get());
        
        self::assertEmpty($item->stream()->toArray());
    }
}