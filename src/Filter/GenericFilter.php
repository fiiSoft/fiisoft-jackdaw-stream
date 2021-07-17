<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Helper;

final class GenericFilter implements Filter
{
    /** @var callable */
    private $filter;
    
    /** @var int */
    private $numOfArgs;
    
    public function __construct(callable $filter)
    {
        $this->filter = $filter;
        $this->numOfArgs = Helper::getNumOfArgs($filter);
    }
    
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        $isAllowed = $this->filter;
    
        switch ($this->numOfArgs) {
            case 1:
                switch ($mode) {
                    case Check::VALUE: return $isAllowed($value);
                    case Check::KEY: return $isAllowed($key);
                    case Check::BOTH: return $isAllowed($value) && $isAllowed($key);
                    case Check::ANY: return $isAllowed($value) || $isAllowed($key);
                    default:
                        throw new \InvalidArgumentException('Invalid param mode');
                }
            case 2: return $isAllowed($value, $key);
            case 3: return $isAllowed($value, $key, $mode);
            case 0: return $isAllowed();
            default:
                throw Helper::wrongNumOfArgsException('Filter', $this->numOfArgs, 0, 1, 2, 3);
        }
    }
}