<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Condition;

use FiiSoft\Jackdaw\Internal\Helper;

final class GenericCondition implements Condition
{
    /** @var callable */
    private $condition;
    
    private int $numOfArgs;
    
    public function __construct(callable $condition)
    {
        $this->condition = $condition;
        $this->numOfArgs = Helper::getNumOfArgs($condition);
    }
    
    /**
     * @inheritDoc
     */
    public function isTrueFor($value, $key): bool
    {
        $condition = $this->condition;
    
        switch ($this->numOfArgs) {
            case 1: return $condition($value);
            case 2: return $condition($value, $key);
            case 0: return $condition();
            default:
                throw Helper::wrongNumOfArgsException('Condition', $this->numOfArgs, 1, 2, 0);
        }
    }
}