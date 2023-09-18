<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Basic;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;

final class GenericComparator implements Comparator
{
    /** @var callable */
    private $comparator;
    
    private int $numOfArgs;
    
    public function __construct(callable $comparator)
    {
        $this->numOfArgs = Helper::getNumOfArgs($comparator);
        
        if ($this->numOfArgs === 1) {
            $this->numOfArgs = 2;
            $this->comparator = static fn($first, $second): int
                => \gettype($first) <=> \gettype($second) ?: $comparator($first) <=> $comparator($second);
        } elseif ($this->numOfArgs === 2 || $this->numOfArgs === 4) {
            $this->comparator = $comparator;
        } else {
            throw Helper::wrongNumOfArgsException('Comparator', $this->numOfArgs, 1, 2, 4);
        }
    }
    
    public function compare($value1, $value2): int
    {
        if ($this->numOfArgs === 2) {
            $compare = $this->comparator;
            return $compare($value1, $value2);
        }
    
        throw new \LogicException('Cannot compare two values because comparator requires 4 arguments');
    }
    
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        $compare = $this->comparator;
        
        if ($this->numOfArgs === 2) {
            return $compare($value1, $value2) ?: $compare($key1, $key2);
        }
        
        return $compare($value1, $value2, $key1, $key2);
    }
    
    public function isFullAssoc(): bool
    {
        return $this->numOfArgs === 4;
    }
    
    public function comparator(): Comparator
    {
        return $this;
    }
    
    public function mode(): int
    {
        return $this->numOfArgs === 4 ? Check::BOTH : Check::VALUE;
    }
    
    public function getWrappedCallable(): callable
    {
        return $this->comparator;
    }
}