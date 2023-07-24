<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Discriminator\Adapter\ConditionAdapter;
use FiiSoft\Jackdaw\Discriminator\Adapter\FilterAdapter;
use FiiSoft\Jackdaw\Discriminator\Adapter\MapperAdapter;
use FiiSoft\Jackdaw\Discriminator\Adapter\PredicateAdapter;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class Discriminators
{
    /**
     * @param Discriminator|Condition|Predicate|Filter|Mapper|callable|string|int $discriminator
     */
    public static function getAdapter($discriminator, int $mode = Check::VALUE): Discriminator
    {
        if (\is_callable($discriminator)) {
            return self::generic($discriminator);
        }
    
        if ($discriminator instanceof Discriminator) {
            return $discriminator;
        }
        
        if ($discriminator instanceof Mapper) {
            return self::mapper($discriminator);
        }
    
        if ($discriminator instanceof Filter) {
            return self::filter($discriminator, $mode);
        }
    
        if ($discriminator instanceof Predicate) {
            return self::predicate($discriminator, $mode);
        }
    
        if ($discriminator instanceof Condition) {
            return self::condition($discriminator);
        }
        
        if (\is_string($discriminator) || \is_int($discriminator)) {
            return self::byField($discriminator);
        }
        
        throw new \InvalidArgumentException('Invalid param discriminator');
    }
    
    public static function generic(callable $discriminator): Discriminator
    {
        return new GenericDiscriminator($discriminator);
    }
    
    public static function mapper(Mapper $mapper): Discriminator
    {
        return new MapperAdapter($mapper);
    }
    
    public static function filter(Filter $filter, int $mode = Check::VALUE): Discriminator
    {
        return new FilterAdapter($filter, $mode);
    }
    
    public static function predicate(Predicate $predicate, int $mode = Check::VALUE): Discriminator
    {
        return new PredicateAdapter($predicate, $mode);
    }
    
    public static function condition(Condition $condition): Discriminator
    {
        return new ConditionAdapter($condition);
    }
    
    public static function evenOdd(int $mode = Check::VALUE): Discriminator
    {
        return new EvenOdd($mode);
    }
    
    /**
     * @param string|int $field
     * @param string|int|null $orElse
     */
    public static function byField($field, $orElse = null): Discriminator
    {
        return new ByField($field, $orElse);
    }
    
    public static function byKey(): Discriminator
    {
        return new ByKey();
    }
    
    public static function byValue(): Discriminator
    {
        return new ByValue();
    }
    
    public static function alternately(array $classifiers): Discriminator
    {
        return new Alternately($classifiers);
    }
}