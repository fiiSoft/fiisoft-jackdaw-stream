<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Producer\Generator\SequentialInt;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\StreamMaker;
use PHPUnit\Framework\TestCase;

class StreamMakerTest extends TestCase
{
    public function test_from_array(): void
    {
        $stream = StreamMaker::from([6,2,4,8]);
        
        self::assertSame([6, 2, 4, 8], $stream->start()->toArray());
        self::assertSame('6,2,4,8', $stream->start()->toString());
    }
    
    public function test_from_Iterator(): void
    {
        $stream = StreamMaker::from(new \ArrayIterator([6, 2, 4, 8]));
    
        self::assertSame([6, 2, 4, 8], $stream->start()->toArray());
        self::assertSame('6,2,4,8', $stream->start()->toString());
    }
    
    public function test_from_Producer(): void
    {
        $producer = new SequentialInt(1, 1, 4);
        $stream = StreamMaker::from($producer);
        
        self::assertSame([1, 2, 3, 4], $stream->start()->toArray());
        self::assertSame('1,2,3,4', $stream->start()->toString());
    }
    
    public function test_from_callable_Stream_factory(): void
    {
        $stream = StreamMaker::from(static fn() => Stream::from([5, 3, 1]));
    
        self::assertSame([5, 3, 1], $stream->start()->toArray());
        self::assertSame('5,3,1', $stream->start()->toString());
    }
    
    public function test_make_with_method_of(): void
    {
        $stream = StreamMaker::of(['a', 'b'], 1, 2, ['c', 'd']);
        
        self::assertSame('a,b,1,2,c,d', $stream->start()->toString());
        self::assertSame(['a','b',1,2,'c','d'], $stream->start()->toArray());
    }
    
    public function test_make_empty_stream(): void
    {
        $stream = StreamMaker::empty();
        
        self::assertSame('', $stream->start()->toString());
        self::assertSame([], $stream->start()->toArray());
    }
    
    public function test_wrog_factory_param(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        StreamMaker::from('yolo');
    }
    
    public function test_from_ResultApi(): void
    {
        $data = Producers::sequentialInt(1, 1, 5)->stream()->chunk(3)->collect();
        self::assertInstanceOf(ResultApi::class, $data);
        
        $stream = StreamMaker::from($data);
        
        self::assertSame(2, $stream->start()->count()->get());
        self::assertSame([1, 2, 3, 4, 5], $stream->start()->flat()->toArray());
    }
}