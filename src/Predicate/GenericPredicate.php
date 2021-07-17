<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Predicate;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;

final class GenericPredicate implements Predicate
{
    /** @var callable */
    private $predicate;
    
    /** @var int */
    private $numOfArgs;
    
    public function __construct(callable $predicate)
    {
        $this->predicate = $predicate;
        $this->numOfArgs = Helper::getNumOfArgs($predicate);
    }
    
    public function isSatisfiedBy($value, $key = null, int $mode = Check::VALUE): bool
    {
        $isSatisfiedBy = $this->predicate;
    
        switch ($this->numOfArgs) {
            case 1:
                switch ($mode) {
                    case Check::VALUE: return $isSatisfiedBy($value);
                    case Check::KEY: return $isSatisfiedBy($key);
                    case Check::BOTH: return $isSatisfiedBy($value) && $isSatisfiedBy($key);
                    case Check::ANY: return $isSatisfiedBy($value) || $isSatisfiedBy($key);
                    default:
                        throw new \InvalidArgumentException('Invalid param mode');
                }
            case 2: return $isSatisfiedBy($value, $key);
            case 3: return $isSatisfiedBy($value, $key, $mode);
            case 0: return $isSatisfiedBy();
            default:
                throw Helper::wrongNumOfArgsException('Predicate', $this->numOfArgs, 0, 1, 2, 3);
        }
    }
}