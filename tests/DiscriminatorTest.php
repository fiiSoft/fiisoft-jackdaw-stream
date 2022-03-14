<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Predicate\Predicates;
use PHPUnit\Framework\TestCase;

final class DiscriminatorTest extends TestCase
{
    public function test_getAdapter_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        Discriminators::getAdapter(15);
    }
    
    public function test_GenericDiscriminator_throws_exception_when_callable_accepts_wrong_number_of_arguments(): void
    {
        $this->expectException(\LogicException::class);
        
        Discriminators::generic(static fn($a,$b,$c) => true)->classify(15, 1);
    }
    
    public function test_GenericDiscriminator_can_call_callable_with_two_arguments(): void
    {
        self::assertSame('151', Discriminators::generic(static fn($a, $b) => $a.$b)->classify(15, 1));
    }
    
    public function test_EvenOdd_throws_exception_when_checked_value_is_not_integer(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        
        Discriminators::evenOdd()->classify('a', 5);
    }
    
    public function test_EvenOdd_can_evaluate_key(): void
    {
        self::assertSame('odd', Discriminators::evenOdd(Check::KEY)->classify('a', 5));
    }
    
    public function test_EvenOdd_can_evaluate_both_value_and_key_together(): void
    {
        foreach ([Check::BOTH, Check::ANY] as $mode) {
            $evenOdd = Discriminators::evenOdd($mode);
    
            self::assertSame('even', $evenOdd->classify(4, 4));
            self::assertSame('odd', $evenOdd->classify(3, 3));
            self::assertSame('value_even_key_odd', $evenOdd->classify(4, 3));
            self::assertSame('value_odd_key_even', $evenOdd->classify(3, 4));
        }
    }
    
    public function test_Condition_can_be_used_as_Discriminator(): void
    {
        $discriminator = Discriminators::getAdapter(Conditions::generic(static fn(int $v): bool => $v === 1));
        
        self::assertTrue($discriminator->classify(1, 1));
        self::assertFalse($discriminator->classify(2, 1));
    }
    
    public function test_Predicate_can_be_used_as_Discriminator(): void
    {
        $discriminator = Discriminators::getAdapter(Predicates::getAdapter(static fn(int $v): bool => $v === 1));
        
        self::assertTrue($discriminator->classify(1, 1));
        self::assertFalse($discriminator->classify(2, 1));
    }
    
    public function test_field_of_array_can_be_used_as_Discriminator(): void
    {
        $discriminator = Discriminators::getAdapter('sex');
        
        self::assertSame('male', $discriminator->classify(['sex' => 'male'], 1));
        self::assertSame('female', $discriminator->classify(['sex' => 'female'], 1));
    }
    
    public function test_key_of_stream_element_can_be_used_as_discriminator(): void
    {
        self::assertSame('bbb', Discriminators::byKey()->classify('aaa', 'bbb'));
    }
    
    public function test_ByField_discriminator_throws_exception_when_argument_is_nor_array(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ByField discriminator can handle only arrays-like values');
        
        Discriminators::byField('sex')->classify(5, 1);
    }
}