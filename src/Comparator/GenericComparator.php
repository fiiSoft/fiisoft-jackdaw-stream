<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator;

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
            $this->comparator = static function ($first, $second) use ($comparator) {
                return $comparator($first) <=> $comparator($second);
            };
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
}