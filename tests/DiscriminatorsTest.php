<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Discriminator\Alternately;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Predicate\Predicates;
use PHPUnit\Framework\TestCase;

final class DiscriminatorsTest extends TestCase
{
    /**
     * @dataProvider getDataForTestGetAdapterThrowsExceptionOnInvalidArgument
     */
    public function test_getAdapter_throws_exception_on_invalid_argument($discriminator): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param discriminator');
        
        Discriminators::getAdapter($discriminator);
    }
    
    public function getDataForTestGetAdapterThrowsExceptionOnInvalidArgument(): array
    {
        return [
            [[]], //empty array
            ['foo'], //any string
            [1], //any int
        ];
    }
    
    public function test_getAdapter_returns_Alternately_discriminator_when_simple_array_is_passed(): void
    {
        self::assertInstanceOf(Alternately::class, Discriminators::getAdapter(['foo', 'bar']));
    }
    
    public function test_GenericDiscriminator_throws_exception_when_callable_accepts_wrong_number_of_arguments(): void
    {
        $this->expectException(\LogicException::class);
        
        Discriminators::generic(static fn($a,$b,$c): bool => true)->classify(15, 1);
    }
    
    public function test_GenericDiscriminator_can_call_callable_with_two_arguments(): void
    {
        self::assertSame('151', Discriminators::generic(static fn($a, $b): string => $a.$b)->classify(15, 1));
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
    
    public function test_field_of_array_can_be_used_as_Discriminator_thanks_to_dedicated_methods(): void
    {
        $discriminator = Discriminators::prepare('sex');
        self::assertSame('male', $discriminator->classify(['sex' => 'male'], 1));
        
        $discriminator = Discriminators::byField('sex');
        self::assertSame('female', $discriminator->classify(['sex' => 'female'], 1));
    }
    
    public function test_key_of_stream_element_can_be_used_as_discriminator(): void
    {
        self::assertSame('bbb', Discriminators::byKey()->classify('aaa', 'bbb'));
    }
    
    public function test_ByField_discriminator_throws_exception_when_argument_is_not_array(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ByField discriminator can handle only arrays-like values');
        
        Discriminators::byField('sex')->classify(5, 1);
    }
    
    public function test_Mapper_can_be_used_as_Discriminator(): void
    {
        $discriminator = Discriminators::getAdapter(Mappers::fieldValue('gender'));
        
        self::assertSame('man', $discriminator->classify(['id' => 5, 'gender' => 'man'], 0));
        self::assertSame('woman', $discriminator->classify(['id' => 7, 'gender' => 'woman'], 1));
    }
    
    public function test_Alternately_throws_exception_when_argument_classifiers_is_empy(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param classifiers');
        
        Discriminators::alternately([]);
    }
    
    public function test_Alternately_discriminator(): void
    {
        $discriminator = Discriminators::alternately(['one', 'two', 'three']);
        
        $classifiers = [];
        
        for ($i = 0; $i < 8; ++$i) {
            $classifiers[] = $discriminator->classify('any', 'thing');
        }
        
        self::assertSame(['one', 'two', 'three', 'one', 'two', 'three', 'one', 'two'], $classifiers);
    }
    
    public function test_YesNo_throws_exception_when_arguments_yes_and_no_are_the_same(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Params yes and no cannot be the same');
        
        Discriminators::yesNo('is_string', 'foo', 'foo');
    }
    
    public function test_YesNo_throws_exception_when_param_yes_is_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param yes');
        
        Discriminators::yesNo('is_string', '', 'foo');
    }
    
    public function test_YesNo_throws_exception_when_param_no_is_empty_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param no');
        
        Discriminators::yesNo('is_string', 'foo', '');
    }
    
    public function test_YesNo_throws_exception_when_wrapped_discriminator_returns_something_other_than_bool(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('YesNo discriminator can only work with boolean results');
        
        Discriminators::yesNo(static fn($v) => $v)->classify('foo', 1);
    }
}