<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Discriminator\Alternately;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Discriminator\Exception\DiscriminatorExceptionFactory;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Exception\UnsupportedValueException;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mappers;
use PHPUnit\Framework\TestCase;

final class DiscriminatorsTest extends TestCase
{
    /**
     * @dataProvider getDataForTestGetAdapterThrowsExceptionOnInvalidArgument
     */
    public function test_getAdapter_throws_exception_on_invalid_argument($discriminator): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('discriminator'));
        
        Discriminators::getAdapter($discriminator);
    }
    
    public static function getDataForTestGetAdapterThrowsExceptionOnInvalidArgument(): array
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
        $this->expectExceptionObject(DiscriminatorExceptionFactory::invalidParamClassifier(3));
        
        Discriminators::getAdapter(static fn($a,$b,$c): bool => true)->classify(15, 1);
    }
    
    public function test_GenericDiscriminator_can_call_callable_with_two_arguments(): void
    {
        self::assertSame('151', Discriminators::getAdapter(static fn($a, $b): string => $a.$b)->classify(15, 1));
    }
    
    public function test_EvenOdd_can_evaluate_key(): void
    {
        self::assertSame('odd', Discriminators::evenOdd(Check::KEY)->classify('a', 5));
        self::assertSame('even', Discriminators::evenOdd(Check::KEY)->classify('a', 6));
    }
    
    public function test_EvenOdd_can_evaluate_both_value_and_key_together(): void
    {
        foreach ([Check::BOTH, Check::ANY] as $mode) {
            $evenOdd = Discriminators::evenOdd($mode);
    
            self::assertSame('even_even', $evenOdd->classify(4, 4));
            self::assertSame('odd_odd', $evenOdd->classify(3, 3));
            self::assertSame('even_odd', $evenOdd->classify(4, 3));
            self::assertSame('odd_even', $evenOdd->classify(3, 4));
        }
    }
    
    public function test_Condition_can_be_used_as_Discriminator(): void
    {
        $discriminator = Discriminators::getAdapter(Conditions::getAdapter(static fn(int $v): bool => $v === 1));
        
        self::assertTrue($discriminator->classify(1, 1));
        self::assertFalse($discriminator->classify(2, 1));
    }
    
    public function test_field_of_array_can_be_used_as_Discriminator_thanks_to_dedicated_methods(): void
    {
        $discriminator = Discriminators::prepare('sex');
        self::assertSame('male', $discriminator->classify(['sex' => 'male']));
        
        $discriminator = Discriminators::byField('sex');
        self::assertSame('female', $discriminator->classify(['sex' => 'female']));
    }
    
    public function test_key_of_stream_element_can_be_used_as_discriminator(): void
    {
        self::assertSame('bbb', Discriminators::byKey()->classify('aaa', 'bbb'));
    }
    
    public function test_Mapper_can_be_used_as_Discriminator(): void
    {
        $discriminator = Discriminators::getAdapter(Mappers::fieldValue('gender'));
        
        self::assertSame('man', $discriminator->classify(['id' => 5, 'gender' => 'man']));
        self::assertSame('woman', $discriminator->classify(['id' => 7, 'gender' => 'woman']));
    }
    
    public function test_Alternately_throws_exception_when_argument_classifiers_is_empy(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('classifiers'));
        
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
        $this->expectExceptionObject(DiscriminatorExceptionFactory::paramsYesAndNoCannotBeTheSame());
        
        Discriminators::yesNo('is_string', 'foo', 'foo');
    }
    
    public function test_YesNo_throws_exception_when_param_yes_is_empty_string(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('yes'));
        
        Discriminators::yesNo('is_string', '', 'foo');
    }
    
    public function test_YesNo_throws_exception_when_param_no_is_empty_string(): void
    {
        $this->expectExceptionObject(InvalidParamException::byName('no'));
        
        Discriminators::yesNo('is_string', 'foo', '');
    }
    
    public function test_DayOfWeek_throws_exception_when_argument_is_invalid(): void
    {
        $this->expectExceptionObject(UnsupportedValueException::cannotCastNonTimeObjectToString('foo'));
        
        Discriminators::dayOfWeek()->classify('foo');
    }
}