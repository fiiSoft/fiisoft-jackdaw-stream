<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Predicate\Adapter\FilterAdapter;
use FiiSoft\Jackdaw\Predicate\InArray;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Predicate\Predicates;
use PHPUnit\Framework\TestCase;

final class PredicatesTest extends TestCase
{
    /**
     * @dataProvider getPredicates
     */
    public function test_Predicate_can_works_in_various_modes(Predicate $predicate): void
    {
        self::assertTrue($predicate->isSatisfiedBy(5, 5, Check::VALUE));
        self::assertTrue($predicate->isSatisfiedBy(5, 5, Check::KEY));
        self::assertTrue($predicate->isSatisfiedBy(5, 5, Check::BOTH));
        self::assertTrue($predicate->isSatisfiedBy(5, 5, Check::ANY));
    
        self::assertTrue($predicate->isSatisfiedBy(5, 1, Check::VALUE));
        self::assertFalse($predicate->isSatisfiedBy(5, 1, Check::KEY));
        self::assertFalse($predicate->isSatisfiedBy(5, 1, Check::BOTH));
        self::assertTrue($predicate->isSatisfiedBy(5, 1, Check::ANY));
    
        self::assertFalse($predicate->isSatisfiedBy(1, 5, Check::VALUE));
        self::assertTrue($predicate->isSatisfiedBy(1, 5, Check::KEY));
        self::assertFalse($predicate->isSatisfiedBy(1, 5, Check::BOTH));
        self::assertTrue($predicate->isSatisfiedBy(1, 5, Check::ANY));
    
        self::assertFalse($predicate->isSatisfiedBy(1, 1, Check::VALUE));
        self::assertFalse($predicate->isSatisfiedBy(1, 1, Check::KEY));
        self::assertFalse($predicate->isSatisfiedBy(1, 1, Check::BOTH));
        self::assertFalse($predicate->isSatisfiedBy(1, 1, Check::ANY));
    }
    
    /**
     * @dataProvider getPredicates
     */
    public function test_Predicate_throws_exception_when_param_mode_is_invalid(Predicate $predicate): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param mode');
        
        $predicate->isSatisfiedBy(3, 2, 5);
    }
    
    public function getPredicates(): array
    {
        return [
            [Predicates::getAdapter(5)],
            [Predicates::getAdapter([5, 6])],
            [Predicates::getAdapter(Filters::equal(5))],
            [Predicates::getAdapter(static fn(int $value): bool => $value === 5)],
        ];
    }
    
    public function test_Filter_can_be_use_as_Predicate(): void
    {
        self::assertInstanceOf(FilterAdapter::class, Predicates::getAdapter(Filters::equal(5)));
    }
    
    public function test_array_as_InArray_predicate(): void
    {
        self::assertInstanceOf(InArray::class, Predicates::getAdapter([5, 6]));
    }
    
    public function test_callable_with_three_arguments_as_GenericPredicate(): void
    {
        $callable = static fn(int $value, int $key, int $mode): bool => $mode === 1 && $value === 5;
        $predicate = Predicates::getAdapter($callable);
        
        self::assertTrue($predicate->isSatisfiedBy(5, 1, 1));
    }
    
    public function test_callable_without_arguments_as_GenericPredicate(): void
    {
        $predicate = Predicates::getAdapter(static fn(): bool  => true);
        
        self::assertTrue($predicate->isSatisfiedBy(5, 1, 1));
    }
    
    public function test_GenericPredicate_throws_exception_when_callable_requires_wrong_number_of_arguments(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Predicate have to accept 0, 1, 2 or 3 arguments, but requires 4');
        
        Predicates::generic(static fn($a, $b, $c, $d): bool => true)->isSatisfiedBy(5);
    }
}