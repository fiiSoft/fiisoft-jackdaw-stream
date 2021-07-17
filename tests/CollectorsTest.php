<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Collector\ArrayAccess;
use FiiSoft\Jackdaw\Collector\Collectors;
use PHPUnit\Framework\TestCase;

final class CollectorsTest extends TestCase
{
    public function test_getAdapter_returns_passed_Collector(): void
    {
        $collector = new ArrayAccess(new \ArrayObject());
        self::assertSame($collector, Collectors::getAdapter($collector));
    }
    
    public function test_getAdapter_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Collectors::getAdapter(15);
    }
}