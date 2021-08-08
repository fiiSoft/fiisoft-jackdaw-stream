<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Internal\Check;
use PHPUnit\Framework\TestCase;
use SplMinHeap;

final class OtherTest extends TestCase
{
    public function test_iterate_over_nested_arrays_without_oryginal_keys(): void
    {
        $arr = ['a', ['b', 'c',], 'd', ['e', ['f', ['g', 'h',], 'i'], [[['j'], 'k',], 'l',], 'm',], 'n'];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($arr),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        $flatten = \iterator_to_array($iterator, false);
        $asString = \implode(',', $flatten);
        
        self::assertSame('a,b,c,d,e,f,g,h,i,j,k,l,m,n', $asString);
    }
    
    public function test_Check_can_validate_param_mode(): void
    {
        self::assertSame(Check::VALUE, Check::getMode(Check::VALUE));
        self::assertSame(Check::KEY, Check::getMode(Check::KEY));
        self::assertSame(Check::BOTH, Check::getMode(Check::BOTH));
        self::assertSame(Check::ANY, Check::getMode(Check::ANY));
    }
    
    public function test_Check_throws_exception_when_param_mode_is_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Check::getMode(0);
    }
    
    public function test_how_ArrayObject_handles_isset_on_null_values(): void
    {
        $data = ['a' => 5, 'b' => null];
        $obj = new \ArrayObject($data);
        
        self::assertTrue(isset($obj['a']));
        self::assertFalse(isset($obj['b']));
        self::assertFalse(isset($obj['c']));
        
        self::assertNotEmpty($obj['a']);
        self::assertEmpty($obj['b']);
        self::assertTrue(empty($obj['c']));
        
        self::assertTrue($obj->offsetExists('a'));
        self::assertTrue($obj->offsetExists('b'));
        self::assertFalse($obj->offsetExists('c'));
    }
    
    public function test_how_SplMinHeap_acts(): void
    {
        $heap = new class extends SplMinHeap {
            protected function compare($value1, $value2) {
                return $value2 <=> $value1;
            }
        };
    
        foreach ([6,2,8,4,5,9,1,7,3] as $value) {
            $heap->insert($value);
        }
        
        self::assertSame(9, $heap->count());
        self::assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9], \iterator_to_array($heap, false));
        
        self::assertEmpty(\iterator_to_array($heap, false));
        
        $heap->rewind();
        self::assertEmpty(\iterator_to_array($heap, false));
    
        self::assertFalse($heap->isCorrupted());
        
        foreach ([6,2,8,4,5,9,1,7,3] as $value) {
            $heap->insert($value);
        }
    
        self::assertSame(9, $heap->count());
        self::assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9], \iterator_to_array($heap, false));
    
        self::assertFalse($heap->isCorrupted());
    
    
        foreach ([6,2,8,4,5,9,1,7,3] as $value) {
            $heap->insert($value);
        }
    
        self::assertSame(9, $heap->count());
        
        self::assertSame(1, $heap->top());
        self::assertSame(9, $heap->count());
        
        self::assertSame(1, $heap->extract());
        self::assertSame(8, $heap->count());
    }
    
    public function test_what_is_type_of_php_int_max_divided_by_small_number(): void
    {
        $divisors = [
            1 => 'integer', 2 => 'double', 3 => 'double', 4 => 'double', 5 => 'double', 6 => 'double',
            7 => 'integer', 8 => 'double', 9 => 'double', 10 => 'double', 11 => 'double', 12 => 'double',
            13 => 'double',  14 => 'double', 15 => 'double', 16 => 'double', 17 => 'double', 18 => 'double',
            19 => 'double',  20 => 'double', 21 => 'double', 22 => 'double', 23 => 'double', 24 => 'double',
            49  => 'integer', 73 => 'integer', 127 => 'integer', 337 => 'integer', 511 => 'integer',
            889 => 'integer', 2359 => 'integer', 3576 => 'double', 3577 => 'integer', 3578 => 'double',
        ];
    
        foreach ($divisors as $divisor => $type) {
            $actual = \PHP_INT_MAX / $divisor;
            self::assertSame($type, \gettype($actual), 'number: '.$divisor);
    
            if ($type === 'integer') {
                self::assertIsInt($actual);
            } else {
                self::assertIsFloat($actual);
            }
        }
    }
}