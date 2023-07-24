<?php declare(strict_types=1);

namespace FiiSoft\Test\Jackdaw;

use FiiSoft\Jackdaw\Condition\Conditions;
use FiiSoft\Jackdaw\Filter\Filters;
use PHPUnit\Framework\TestCase;

final class ConditionsTest extends TestCase
{
    public function test_getAdapter_throws_exception_on_invalid_argument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid param condition');
        
        Conditions::getAdapter('this is not callback');
    }
    
    public function test_callable_can_accept_no_arguments(): void
    {
        $condition = Conditions::getAdapter(static fn(): bool => true);
        
        self::assertTrue($condition->isTrueFor('any', 'any'));
    }
    
    public function test_it_throws_exception_when_callable_requires_unsupported_number_of_arguments(): void
    {
        $this->expectException(\LogicException::class);
        
        $condition = Conditions::getAdapter(static fn($a, $b, $c): bool => true);
        $condition->isTrueFor('any', 'any');
    }
    
    public function test_any_Filter_can_be_use_as_Condition(): void
    {
        $condition = Conditions::getAdapter(Filters::isInt());
        
        self::assertTrue($condition->isTrueFor(15, 1));
        self::assertFalse($condition->isTrueFor('b', 2));
    }
}