<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Discriminator\Adapter\ConditionAdapter;
use FiiSoft\Jackdaw\Discriminator\Adapter\MemoReaderAdapter;
use FiiSoft\Jackdaw\Discriminator\Adapter\FilterAdapter;
use FiiSoft\Jackdaw\Discriminator\Adapter\MapperAdapter;
use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Memo\MemoReader;

final class Discriminators
{
    /**
     * @param DiscriminatorReady|callable|array<string|int> $discriminator
     */
    public static function getAdapter($discriminator): Discriminator
    {
        if ($discriminator instanceof Discriminator) {
            return $discriminator;
        }
        
        if (\is_callable($discriminator)) {
            return GenericDiscriminator::create($discriminator);
        }
        
        if (\is_array($discriminator) && !empty($discriminator)) {
            return self::alternately($discriminator);
        }
    
        if ($discriminator instanceof Mapper) {
            return new MapperAdapter($discriminator);
        }
    
        if ($discriminator instanceof Filter) {
            return new FilterAdapter($discriminator);
        }
    
        if ($discriminator instanceof Condition) {
            return new ConditionAdapter($discriminator);
        }
        
        if ($discriminator instanceof MemoReader) {
            return new MemoReaderAdapter($discriminator);
        }
        
        throw InvalidParamException::describe('discriminator', $discriminator);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     */
    public static function prepare($discriminator): Discriminator
    {
        return (\is_string($discriminator) && !\is_callable($discriminator)) || \is_int($discriminator)
            ? self::byField($discriminator)
            : self::getAdapter($discriminator);
    }
    
    public static function evenOdd(int $mode = Check::VALUE): Discriminator
    {
        return EvenOdd::create($mode);
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
    
    /**
     * @param array<string|int> $classifiers
     */
    public static function alternately(array $classifiers): Discriminator
    {
        return new Alternately($classifiers);
    }
    
    /**
     * @param DiscriminatorReady|callable|array<string|int>|string|int $discriminator
     * @param string|int $yes
     * @param string|int $no value of it must be different than value of $yes
     */
    public static function yesNo($discriminator, $yes = 'yes', $no = 'no'): Discriminator
    {
        return new YesNo($discriminator, $yes, $no);
    }
    
    /**
     * This discriminator requires \DateTimeInterface object on input and returns string with the name of the day,
     * the same as in Day::* constants or as an output of \DateTimeInterface::format('D') method.
     */
    public static function dayOfWeek(): Discriminator
    {
        return new DayOfWeek();
    }
    
    /**
     * @param mixed $variable REFERENCE
     */
    public static function readFrom(&$variable): Discriminator
    {
        return new Reference($variable);
    }
}