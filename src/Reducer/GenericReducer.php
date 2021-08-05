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
    
    private bool $isFirst = true;
    private bool $hasAny = false;
    
    private int $numOfArgs;
    
    public function __construct(callable $reducer)
    {
        $this->reducer = $reducer;
        $this->numOfArgs = Helper::getNumOfArgs($reducer);
    }
    
    public function consume($value, $key = null): void
    {
        $this->hasAny = true;
        
        if ($this->isFirst) {
            $this->isFirst = false;
            $this->result = $value;
        } else {
            $reduce = $this->reducer;
            switch ($this->numOfArgs) {
                case 2: $this->result = $reduce($this->result, $value); break;
                case 3: $this->result = $reduce($this->result, $value, $key); break;
                default:
                    throw Helper::wrongNumOfArgsException('Reducer', $this->numOfArgs, 2, 3);
            }
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