<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Reducer\Internal\BaseReducer;

final class GenericReducer extends BaseReducer
{
    /** @var callable */
    private $reducer;
    
    /** @var mixed|null */
    private $result;
    
    private bool $isFirst = true;
    private bool $hasAny = false;
    
    /**
     * @param callable $reducer This accepts two arguments: accumulator and current value
     */
    public function __construct(callable $reducer)
    {
        $this->reducer = $reducer;
    
        $numOfArgs = Helper::getNumOfArgs($reducer);
        if ($numOfArgs !== 2) {
            throw Helper::wrongNumOfArgsException('Reducer', $numOfArgs, 2);
        }
    }
    
    /**
     * @inheritdoc
     */
    public function consume($value): void
    {
        $this->hasAny = true;
        
        if ($this->isFirst) {
            $this->isFirst = false;
            $this->result = $value;
        } else {
            $this->result = ($this->reducer)($this->result, $value);
        }
    }
    
    public function result()
    {
        return $this->result;
    }
    
    public function hasResult(): bool
    {
        return $this->hasAny;
    }
    
    public function reset(): void
    {
        $this->isFirst = true;
        $this->hasAny = false;
        $this->result = null;
    }
}