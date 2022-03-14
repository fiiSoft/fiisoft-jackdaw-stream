<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Discriminator\Adapter\ConditionAdapter;
use FiiSoft\Jackdaw\Discriminator\Adapter\FilterAdapter;
use FiiSoft\Jackdaw\Discriminator\Adapter\PredicateAdapter;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class Discriminators
{
    /**
     * @param Discriminator|Condition|Predicate|Filter|string|callable $discriminator
     * @return Discriminator
     */
    public static function getAdapter($discriminator): Discriminator
    {
        if (\is_callable($discriminator)) {
            return self::generic($discriminator);
        }
    
        if ($discriminator instanceof Filter) {
            return self::filter($discriminator);
        }
    
        if ($discriminator instanceof Predicate) {
            return self::predicate($discriminator);
        }
    
        if ($discriminator instanceof Condition) {
            return self::condition($discriminator);
        }
        
        if ($discriminator instanceof Discriminator) {
            return $discriminator;
        }
    
        if (\is_string($discriminator)) {
            return self::byField($discriminator);
        }
        
        throw new \InvalidArgumentException('Invalid param discriminator');
    }
    
    public static function generic(callable $discriminator): GenericDiscriminator
    {
        return new GenericDiscriminator($discriminator);
    }
    
    public static function filter(Filter $filter, int $mode = Check::VALUE): FilterAdapter
    {
        return new FilterAdapter($filter, $mode);
    }
    
    public static function predicate(Predicate $predicate): PredicateAdapter
    {
        return new PredicateAdapter($predicate);
    }
    
    public static function condition(Condition $condition): ConditionAdapter
    {
        return new ConditionAdapter($condition);
    }
    
    public static function evenOdd(int $mode = Check::VALUE): EvenOdd
    {
        return new EvenOdd($mode);
    }
    
    /**
     * @param string|int $field
     * @param string|int|null $orElse
     * @return ByField
     */
    public static function byField($field, $orElse = null): ByField
    {
        return new ByField($field, $orElse);
    }
    
    public static function byKey(): ByKey
    {
        return new ByKey();
    }
}