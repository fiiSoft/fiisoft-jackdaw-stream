<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Comparator\Comparators;
use PHPUnit\Framework\TestCase;

final class ComparatorsTest extends TestCase
{
    public function test_getAdapter_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Comparators::getAdapter(15);
    }
    
    public function test_GenericComparator_throws_exception_when_callable_accepts_wrong_number_of_arguments(): void
    {
        $this->expectException(\LogicException::class);
        
        Comparators::generic(static fn($v, $k, $n) => true);
    }
    
    public function test_GenericComparator_throws_exception_when_wrong_callable_is_used(): void
    {
        try {
            Comparators::generic(static fn($a, $b, $c, $d) => true)->compare(1, 2);
            self::fail('Expected exception was not thrown');
        } catch (\LogicException $e) {
            //ok
        }
        
        try {
            Comparators::generic(static fn($a, $b) => true)->compareAssoc(1, 2, 3, 4);
            self::fail('Expected exception was not thrown');
        } catch (\LogicException $e) {
            //ok
        }
        
        self::assertTrue(true);
    }
    
    public function test_SortBy_throws_exception_when_value_is_not_array(): void
    {
        $comparator = Comparators::sortBy(['id']);
    
        try {
            $comparator->compare(5, [6]);
            self::fail('Expected exception was not thrown');
        } catch (\LogicException $e) {
            //ok
        }
    
        try {
            $comparator->compare([6], 5);
            self::fail('Expected exception was not thrown');
        } catch (\LogicException $e) {
            //ok
        }
        
        self::assertTrue(true);
    }
    
    public function test_SortBy(): void
    {
        self::assertSame(0, Comparators::sortBy(['id'])->compare(['id' => 1], ['id' => 1]));
    }
    
    public function test_SortBy_throws_exception_when_fields_is_empty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Comparators::sortBy([]);
    }
    
    public function test_SortBy_throws_exception_when_fields_contains_not_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Comparators::sortBy([15]);
    }
    
    public function test_SortBy_compareAssoc_is_not_implemented_and_cannot_be_called(): void
    {
        $this->expectException(\LogicException::class);
        
        Comparators::sortBy(['a'])->compareAssoc(1, 2, 3, 4);
    }
}