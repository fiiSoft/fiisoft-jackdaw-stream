<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Item;

final class GenericReducer implements Reducer
{
    /** @var callable */
    private $reducer;
    
    /** @var mixed|null */
    private $result;
    
    /** @var bool */
    private $isFirst = true;
    
    /** @var bool */
    private $hasAny = false;
    
    public function __construct(callable $reducer)
    {
        if (Helper::getNumOfArgs($reducer) !== 2) {
            throw new \UnexpectedValueException('Reducer have to accept 2 arguments');
        }
    
        $this->reducer = $reducer;
    }
    
    public function consume($value)
    {
        $this->hasAny = true;
        
        if ($this->isFirst) {
            $this->isFirst = false;
            $this->result = $value;
        } else {
            $reduce = $this->reducer;
            $this->result = $reduce($this->result, $value);
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
    
    public function getResult(): Item
    {
        return new Item(0, $this->result());
    }
}