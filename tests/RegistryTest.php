<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Registry\Registry;
use FiiSoft\Jackdaw\Stream;
use PHPUnit\Framework\TestCase;

final class RegistryTest extends TestCase
{
    public function test_registry_allows_to_remember_key_and_value_independently(): void
    {
        $reg = Registry::new();
        
        Stream::from(['a', 'b', 'c'])
            ->limit(2)
            ->remember($reg->key('foo'))
            ->remember($reg->value('bar'))
            ->remember($reg->valueKey('value', 'key'))
            ->run();
        
        self::assertSame(1, $reg->get('foo'));
        self::assertSame('b', $reg->get('bar'));
        self::assertSame(1, $reg->get('key'));
        self::assertSame('b', $reg->get('value'));
    }
    
    public function test_registry_allows_to_remember_anything_directly(): void
    {
        $reg = Registry::new();
        
        $reg->set('foo', 'bar');
        
        $result = Stream::from([1, 2, 3])
            ->map(static fn(int $value): string => $value.$reg->get('foo'))
            ->toString();
        
        self::assertSame('1bar,2bar,3bar', $result);
    }
    
    public function test_exception_is_thrown_when_name_of_value_and_key_are_the_same(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameters value and key cannot be the same');
        
        Stream::empty()->remember(Registry::new()->valueKey('foo', 'foo'));
    }
}