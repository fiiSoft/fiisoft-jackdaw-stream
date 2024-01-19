<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic;

use FiiSoft\Jackdaw\Comparator\Basic\Generic\FourArgsGenericComparator;
use FiiSoft\Jackdaw\Comparator\Basic\Generic\TwoArgsGenericComparator;
use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Exception\ComparatorExceptionFactory;
use FiiSoft\Jackdaw\Internal\Helper;

abstract class GenericComparator implements Comparator
{
    /** @var callable */
    protected $comparator;
    
    final public static function create(callable $comparator): self
    {
        $numOfArgs = Helper::getNumOfArgs($comparator);
        
        if ($numOfArgs === 1) {
            $numOfArgs = 2;
            $comparator = static fn($first, $second): int
                => \gettype($first) <=> \gettype($second) ?: $comparator($first) <=> $comparator($second);
        } elseif ($numOfArgs !== 2 && $numOfArgs !== 4) {
            throw ComparatorExceptionFactory::invalidParamComparator($numOfArgs);
        }
        
        return $numOfArgs === 2
            ? new TwoArgsGenericComparator($comparator)
            : new FourArgsGenericComparator($comparator);
    }
    
    final protected function __construct(callable $comparator)
    {
        $this->comparator = $comparator;
    }
    
    final public function comparator(): Comparator
    {
        return $this;
    }
    
    final public function getWrappedCallable(): callable
    {
        return $this->comparator;
    }
    
    abstract public function isFullAssoc(): bool;
}