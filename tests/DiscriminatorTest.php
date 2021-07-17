<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Internal\Check;
use PHPUnit\Framework\TestCase;

final class DiscriminatorTest extends TestCase
{
    public function test_getAdapter_throws_exception_on_invalid_argument()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Discriminators::getAdapter(15);
    }
    
    public function test_GenericDiscriminator_throws_exception_when_callable_accepts_wrong_number_of_arguments()
    {
        $this->expectException(\LogicException::class);
        
        Discriminators::generic(static function ($a, $b, $c) {
            return true;
        })->classify(15, 1);
    }
    
    public function test_GenericDiscriminator_can_call_callable_with_two_arguments()
    {
        self::assertSame('151', Discriminators::generic(static function ($a, $b) {
            return $a.$b;
        })->classify(15, 1));
    }
    
    public function test_EvenOdd_throws_exception_when_checked_value_is_not_integer()
    {
        $this->expectException(\UnexpectedValueException::class);
        
        Discriminators::evenOdd()->classify('a', 5);
    }
    
    public function test_EvenOdd_can_evaluate_key()
    {
        self::assertSame('odd', Discriminators::evenOdd(Check::KEY)->classify('a', 5));
    }
    
    public function test_EvenOdd_can_evaluate_both_value_and_key_together()
    {
        foreach ([Check::BOTH, Check::ANY] as $mode) {
            $evenOdd = Discriminators::evenOdd($mode);
    
            self::assertSame('even', $evenOdd->classify(4, 4));
            self::assertSame('odd', $evenOdd->classify(3, 3));
            self::assertSame('value_even_key_odd', $evenOdd->classify(4, 3));
            self::assertSame('value_odd_key_even', $evenOdd->classify(3, 4));
        }
    }
}