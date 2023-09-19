<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Registry\Registry;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class RegistryTest extends TestCase
{
    public function test_Registry_allows_to_remember_key_and_value_independently(): void
    {
        //given
        $reg = Registry::new();
        
        //when
        Stream::from(['a', 'b', 'c'])
            ->limit(2)
            ->remember($reg->key('foo'))
            ->remember($reg->value('bar'))
            ->remember($reg->valueKey('value', 'key'))
            ->run();
        
        //then
        self::assertSame(1, $reg->get('foo'));
        self::assertSame('b', $reg->get('bar'));
        self::assertSame(1, $reg->get('key'));
        self::assertSame('b', $reg->get('value'));
    }
    
    public function test_Registry_allows_to_remember_anything_directly(): void
    {
        //given
        $reg = Registry::new();
        
        //when
        $reg->set('foo', 'bar');
        
        //then
        $result = Stream::from([1, 2, 3])
            ->map(static fn(int $value): string => $value.$reg->get('foo'))
            ->toString();
        
        self::assertSame('1bar,2bar,3bar', $result);
    }
    
    public function test_exception_is_thrown_when_name_of_value_and_key_are_the_same(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameters value and key cannot be the same');
        
        Registry::new()->valueKey('foo', 'foo');
    }
    
    public function test_Registry_allows_to_create_value_reader_writer(): void
    {
        $currentValue = Registry::new()->entry(Check::VALUE, 3);
        
        $result = Stream::from($currentValue)
            ->map(static fn(int $v): int => $v - 1)
            ->remember($currentValue)
            ->limit(5)
            ->toArrayAssoc();
        
        self::assertSame([2, 1, 0, -1, -2], $result);
    }
    
    public function test_Registry_allows_to_create_key_reader_writer(): void
    {
        $currentKey = Registry::new()->entry(Check::KEY, 0);
        $producer = Producers::getAdapter(['a', 'B', 'c', 'd', 'E', 'F', 'g']);
        
        $result = Stream::from($currentKey)
            ->map($producer)
            ->while('is_string')
            ->callWhen(
                static fn(string $v): bool => \strtoupper($v) === $v,
                static function () use ($currentKey) {
                    $currentKey->set(0);
                },
                static function () use ($currentKey) {
                    $currentKey->set($currentKey->get() + 1);
                }
            )
            ->mapKey($currentKey)
            ->toArrayAssoc();
        
        self::assertSame([1 => 'g', 0 => 'F', 2 => 'd'], $result);
    }
    
    public function test_Registry_allows_to_create_keyValue_tuple_reader_writer(): void
    {
        $tuple = Registry::new()->entry(Check::BOTH, ['c', 5]);
        $result = [];
        
        Stream::from($tuple)
            ->unpackTuple()
            ->storeIn($result)
            ->until('a', Check::KEY)
            ->mapKey(static fn(int $_, string $key): string => \ord($key) > \ord('a') ? \chr(\ord($key) - 1) : $key)
            ->map(Mappers::increment())
            ->remember($tuple)
            ->run();
            
        self::assertSame(['c' => 5, 'b' => 6, 'a' => 7], $result);
    }
    
    public function test_Registry_treats_mode_ANY_as_BOTH(): void
    {
        $entry = Registry::new()->entry(Check::ANY, ['c', 5]);
        
        $result = Stream::from($entry)->unpackTuple()->limit(1)->toArrayAssoc();
        
        self::assertSame(['c' => 5], $result);
    }
    
    public function test_TupleWriter_throws_exception_when_set_value_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param value - null or tuple [key,value] is required');
        
        Registry::new()->entry(Check::BOTH, 'wrong value');
    }
    
    public function test_FullWriter_allows_to_set_value_directly(): void
    {
        //given
        $registry = Registry::new();
        
        $writer = $registry->valueKey('value', 'key');
        $valueReader = $registry->read('value');
        $keyReader = $registry->read('key');
        
        //when
        $writer->write('foo', 'bar');
        //then
        self::assertSame('foo', $valueReader->read());
        self::assertSame('bar', $keyReader->read());
        
        //when
        $writer->set(null);
        //then
        self::assertNull($valueReader->read());
        self::assertNull($keyReader->read());
        
        //when
        $writer->set(['a', 'b']);
        //then
        self::assertSame('b', $valueReader->read());
        self::assertSame('a', $keyReader->read());
    }
    
    public function test_FullWriter_throws_exception_when_set_value_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('FullWriter requires null or tuple [key,value] to set directly');
        
        Registry::new()->valueKey('value', 'key')->set('wrong value');
    }
}