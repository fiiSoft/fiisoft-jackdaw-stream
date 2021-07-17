<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\ResultItem;
use PHPUnit\Framework\TestCase;

final class ResultItemTest extends TestCase
{
    public function test_default_not_null()
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
    
    public function test_default_null()
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
    
    public function test_item_found()
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
}